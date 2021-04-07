<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDF;
use Mail;

use App\Models\Transaction;
use App\Models\Vendor;
use App\Models\Lead;
use App\Models\Notification;
use App\Models\Payout;
use App\Models\ServiceCommission;
use App\Models\Withdrawal;
use App\Models\WithdrawalStatus;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;

class TransactionController extends Controller
{
    public function index() {
        $vendor = Vendor::all();
        return view('admin.transaction.index', compact('vendor'));
    }

    public function store(Request $request) {
        if ($request->ajax()) {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'vendor_id' => 'required',
                'service_commission_id' => 'required',
                'email' => 'required|email|unique:leads',
            ], [
                'user_id.required' => 'User Affiliate Field Required',
                'vendor_id.required' => 'Vendor Field Required',
                'email.required' => 'Email Field Required',
                'email.email' => 'Email not valid',
                'email.unique' => 'Email has been registered',
                'service_commission_id.required' => 'Product/Service Field Required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $value) {
                    $error[] = $value[0];
                }

                $stringError = implode("\n", $error);

                return $this->sendResponse($stringError, '', 221);
            }

            // INSERT LEAD FIRST
            DB::beginTransaction();

            try {
                $dataLead = [
                    'user_id' => $request->user_id,
                    'vendor_id' => $request->vendor_id,
                    'customer_name' => $request->customer_name,
                    'email' => $request->email,
                    'no_telepon' => $request->no_telepon,
                    'date' => $request->date,
                    'status' => Lead::SUCCESS,
                    'cancel' => 0,
                ];
    
                $leadCreated = Lead::create($dataLead);
    
                if ($leadCreated) {
                    $service = ServiceCommission::where('id', $request->service_commission_id)->first();
                    $commission = NULL;
                    if ($request->addCommission == 1) {
                        if ($request->commission == NULL) {
                            $getCommission = Transaction::getCommissionValue($request->service_commission_id, $request->amount);
                            $commission = $getCommission > $service->max_commission ? $service->max_commission : $getCommission;
                        } else {
                            if (($service->max_commission != NULL || $service->max_commission > 0) && $request->commission > $service->max_commission) {
                                DB::rollback();
                                return $this->sendResponse("Maximal Commission : ".$this->currencyView($service->max_commission), [], 401);
                            }

                            $commission = $request->commission;
                        }
                    }

                    $dataTransaction = [
                        'lead_id' => $leadCreated->id,
                        'transaction_date' => $request->date,
                        'service_commission_id' => $request->service_commission_id,
                        'amount' => $request->amount,
                        'commission' => $commission,
                        'created_by_system' => 0,
                    ];

                    $transactionCreated = Transaction::create($dataTransaction);

                    if ($transactionCreated) {
                        $data = [
                            'customer_name' => $transactionCreated->lead->customer_name,
                            'email' => $this->hideEmail($transactionCreated->lead->email),
                            'no_telepon' => $this->hidePhoneNumber($transactionCreated->lead->no_telepon),
                            'transaction_date' => $this->convertDateView($transactionCreated->transaction_date),
                            'amount' => $this->currencyView($transactionCreated->amount),
                            'commission' => $this->currencyView($transactionCreated->commission)
                        ];

                        $affiliateEmail = $transactionCreated->lead->user->email;

                        Mail::send('admin.transaction._email', $data, function($message) use ($affiliateEmail) {
                            $message->to($affiliateEmail)->subject('Affiliate Transaction Success');
                        });

                        DB::commit();
                        return $this->sendResponse('Transaction Created Successfully');
                    }
                }
            } catch (Exception $e) {
                DB::rollback();
                return $this->sendResponse('Transaction Failed', $e->getErrors());
            }
        }
    }

    public function downloadPdf($status, $start_date, $end_date) {
        $transaction = Transaction::leftJoin('leads', 'leads.id', '=', 'transactions.lead_id')->where('leads.user_id', Auth::id());
        if ($status !== 'all') {
            if ($status == Lead::ON_PROCESS) {
                $transaction->where('leads.status', Lead::ON_PROCESS);
            } elseif ($status == Lead::SUCCESS) {
                $transaction->where('leads.status', Lead::SUCCESS);
            } else {
                $transaction->where('leads.status', Lead::CANCELED);
            }
        }

        if ($start_date !== 'all' && $end_date !== 'all') {
            $transaction->whereBetween('transactions.date', [$start_date, $end_date]);
        }

        $pdf = PDF::loadView('admin.transaction._printPdfTransaction', ['transaction' => $transaction->get()]);
        return $pdf->setPaper('A4', 'landscape')->stream();
    }

    public function cancel(Request $request) {
        if ($request->ajax()) {
            $transaction = Transaction::findOrfail($request->id_transaction);

            $dataCancel = [
                'cancel' => 1,
                'cancel_date' => Carbon::NOW(),
                'cancel_reason' => $request->cancel_reason
            ];

            $transaction->cancel = 1;
            $transaction->cancel_date = Carbon::NOW();
            $transaction->cancel_reason = $request->cancel_reason;

            if ($transaction->save()) {
                return $this->sendResponse('Transaction Canceled');
            }
        }
    }

    public function downloadTemplate() {
        $date = Carbon::now()->format("Ymd");
        $fileName = "template_transaction_$date.csv";

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $listColumn = ['Email', 'Tanggal Transaksi', 'Jumlah Bayar'];

        $callback = function() use ($listColumn) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $listColumn);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function uploadTransaction(Request $request) {
        if ($request->ajax()) {
            if ($request->file('transactionFile')) {
                $validator = Validator::make($request->all(), [
                    'transactionFile' => 'required'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $value) {
                        return $this->sendResponse($value[0], '', 400);
                    }
                }

                $mustExtension = ['csv'];

                $file = $request->file('transactionFile');
                $originalExtension = $file->getClientOriginalExtension();

                if (in_array($originalExtension, $mustExtension)) {
                    $dataCsv = Reader::createFromPath($file, 'r');
                    $dataCsv->setHeaderOffset(0);
                    
                    $createSuccess = 0;
                    $failed = 0;

                    foreach ($dataCsv as $data) {
                        $leadExists = Lead::where('email', $data['Email'])->first();
                        
                        $transactionDate = Carbon::parse($data['Tanggal Transaksi'])->format('Y-m-d');

                        if ($leadExists) {
                            try {
                                $failedMessage = '';
                                $commissionType = $leadExists->service->commission_type_id;
                                $commissionValue = $leadExists->service->commission_value;
                                $jumlahBayar = (int) $data['Jumlah Bayar'];
                                $commission = $commissionType == CommissionType::FIXED ? $commissionValue : ($commissionValue/100) * $jumlahBayar;
                                
                                $dataTransaction = [
                                    'lead_id' => $leadExists->id,
                                    'service_commission_id' => $leadExists->service_id,
                                    'transaction_date' => $transactionDate,
                                    'amount' => $jumlahBayar,
                                    'commission' => $commission,
                                ];

                                $transactionCreated = Transaction::create($dataTransaction);
                            } catch (\Exception $e) {
                                $failedMessage = $e->getMessage();
                            }
                        }

                        if ($transactionCreated) {
                            $leadExists->update(['status' => Lead::SUCCESS]);

                            $dataEmail = [
                                'customer_name' => $transactionCreated->lead->customer_name,
                                'email' => $this->hideEmail($transactionCreated->lead->email),
                                'no_telepon' => $this->hidePhoneNumber($transactionCreated->lead->no_telepon),
                                'transaction_date' => $this->convertDateView($transactionCreated->transaction_date),
                                'amount' => $this->currencyView($transactionCreated->amount),
                                'commission' => $this->currencyView($transactionCreated->commission)
                            ];

                            $affiliateEmail = $transactionCreated->lead->user->email;

                            Mail::send('admin.transaction._email', $dataEmail, function ($message) use ($affiliateEmail) {
                                $message->to($affiliateEmail)->subject('Affiliate Transaction Success');
                            });

                            $dataWithdrawal = [
                                'date' => Carbon::NOW(),
                                'user_id' => $transactionCreated->lead->user->id,
                                'total' => $transactionCreated->commission,
                                'withdrawal_status_id' => WithdrawalStatus::REQUEST,
                            ];

                            $withdrawalCreated = Withdrawal::create($dataWithdrawal);
                            if ($withdrawalCreated) {
                                $notificationData = [
                                    'user_id' => $transactionCreated->lead->user->id,
                                    'notification_type' => Notification::WITHDRAW,
                                    'withdraw_id' => $withdrawalCreated->id,
                                ];
            
                                Notification::create($notificationData);
                            }

                            $createSuccess++;
                        } else {
                            $failed++;
                            $failedData[] = [
                                'email' => $data['Email'],
                                'message' => $failedMessage
                            ];
                        }
                    }

                    if ($createSuccess == $dataCsv->count()) {
                        $resultMessage = "$createSuccess Data Transaksi berhasil diupload";
                        return $this->sendResponse($resultMessage, '', 200);
                    } else if ($failed > 0 && $createSuccess > 0) {
                        $resultMessage = "$createSuccess Data Transaksi berhasil diupload, $failed Data Transaksi gagal diupload";
                        return $this->sendResponse($resultMessage, $failedData, 200);
                    } else {
                        $resultMessage = "$failed Transaksi gagal diupload";
                        return $this->sendResponse($resultMessage, $failedData, 400);
                    }
                }

                $message = 'File upload must be csv type';
                return $this->sendResponse($message, '', 400);
            }
        }
    }
}
