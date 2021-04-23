<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDF;
use Validator;
use Mail;
use Carbon\Carbon;

use App\Models\Lead;
use App\Models\ServiceCommission;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Vendor;

class LeadController extends Controller
{
    public function index(Request $request) {
        $lead = Lead::paginate(10);
        $vendor = Vendor::all();

        return view('admin.lead.index', compact('lead', 'vendor'));
    }

    public function store(Request $request) {
        if ($request->ajax()) {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'vendor_id' => 'required'
            ], [
                'user_id.required' => 'Please Select User Affiliate',
                'vendor_id.required' => 'Please Select Vendor'
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $value) {
                    $error[] = $value[0];
                }

                $stringError = implode("\n", $error);

                return $this->sendResponse($stringError, '', 221);
            }

            $data = $request->all();
            $data['status'] = 1;

            $saveSuccess = Lead::create($data);

            if ($saveSuccess) {
                return $this->sendResponse('Lead Created Successfully');
            }
        }
    }

    public function prosesLead(Request $request) {
        $service = '<option value="">-- Select Product/Service --</option>';
        if($request->ajax()) {
            $lead = Lead::findOrfail($request->id);

            $user = User::findOrfail($lead->user_id);

            $dataService = ServiceCommission::where('vendor_id', $lead->vendor_id)->get();

            foreach ($dataService as $key => $value) {
                $service .= "<option value='".$value->id."'>".$value->title."</option>";
            }

            return response()->json([
                'lead' => $lead,
                'user' => $user,
                'service' => $service
            ]);
        }
    }

    public function saveProses (Request $request) {
        if (request()->ajax()) {
            $messages = [
                'transaction_date.requried' => 'Transaction Date Required',
                'amount.required' => 'Amount Required',
                'service_commission_id.required' => 'Service Commission Required'
            ];

            $validator = Validator::make($request->all(), [
                'transaction_date' => 'required|string',
                'amount' => 'required|string',
                'service_commission_id' => 'required'
            ], $messages);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $value) {
                    $error[] = $value[0];
                }

                $stringError = implode("\n", $error);

                return $this->sendResponse($stringError, '', 221);
            }
    
            $service = ServiceCommission::findOrfail($request->service_commission_id);

            try {
                if ($request->commission == NULL) {
                    $getCommission = Transaction::getCommissionValue($request->service_commission_id, $request->amount);
                    $commission = $getCommission > $service->max_commission ? $service->max_commission : $getCommission;
                } else {
                    if (($service->max_commission != NULL || $service->max_commission > 0) && $request->commission > $service->max_commission) {
                        return $this->sendResponse("Maximal Commission : ".$this->currencyView($service->max_commission), [], 401);
                    }
    
                    $commission = $request->commission;
                }
        
                $transaction = Transaction::create([
                    'lead_id' => $request->lead_id,
                    'service_commission_id' => $request->service_commission_id,
                    'transaction_date' => $this->convertDateSave($request->transaction_date),
                    'amount' => $request->amount,
                    'commission' => $commission
                ]);
        
                if ($transaction) {
                    Lead::where('id', $request->lead_id)->update(['status' => Lead::SUCCESS]);
                    
                    $dataEmail = [
                        'customer_name' => $transaction->lead->customer_name,
                        'email' => $this->hideEMail($transaction->lead->email),
                        'no_telepon' => $this->hidePhoneNumber($transaction->lead->no_telepon),
                        'transaction_date' => $this->convertDateView($transaction->transaction_date),
                        'amount' => $this->currencyView($transaction->amount),
                        'commission' => $this->currencyView($transaction->commission)
                    ];
    
                    $affiliateEmail = $transaction->lead->user->email;
    
                    Mail::send('admin.transaction._email', $dataEmail, function($message) use ($affiliateEmail) {
                        $message->to($affiliateEmail)->subject('Affiliate Transaction Success');
                    });
    
                    return $this->sendResponse("Lead Processed Successfully");
                } else {
                    return $this->sendResponse("Lead Processed Fail", "", 221);
                }
            } catch (\Exception $e) {
                return $this->sendResponse("Lead Processed Fail", $e->getMessages(), 501);
            }

            
        }
    }

    public function cancel($id) {
        Lead::where('id', $id)->update(['status' => Lead::CANCELED]);
        
        return back()->with(['success' => 'Lead Canceled']);
    }

    public function downloadPdf($status, $start_date, $end_date) {
        $lead = Lead::latest();
        if ($status !== 'all') {
            if ($status == Lead::ON_PROCESS) {
                $lead->where('status', Lead::ON_PROCESS);
            } elseif ($status == Lead::SUCCESS) {
                $lead->where('status', Lead::SUCCESS);
            } else {
                $lead->where('status', Lead::CANCELED);
            }
        }

        if ($start_date !== 'all' && $end_date !== 'all') {
            $lead->whereBetween('date', [$start_date, $end_date]);
        }

        $pdf = PDF::loadView('admin.lead._printPdfLead', ['lead' => $lead->get()]);
        return $pdf->setPaper('A4', 'landscape')->stream();
    }

    public function downloadCsv($status, $startDate, $endDate) {
        $leadList = Lead::whereNotNull('email')->latest();

        if ($status !== 'all') {
            if ($status == Lead::ON_PROCESS) {
                $leadList->where('status', Lead::ON_PROCESS);
            } elseif ($status == Lead::SUCCESS) {
                $leadList->where('status', Lead::SUCCESS);
            } else {
                $leadList->where('status', Lead::CANCELED);
            }
        }

        if ($startDate !== 'all' && $endDate !== 'all') {
            $leadList->whereBetween('date', [$startDate, $endDate]);
        }

        $date = Carbon::now()->format("Ymd");
        $fileName = "data_lead_$date.csv";

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $listColumn = ['Affiliate', 'Customer Name', 'Vendor', 'Email', 'Nomor Telepon', 'Tanggal', 'Status'];

        $callback = function() use ($listColumn, $leadList) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $listColumn);

            foreach ($leadList->get() as $indexLead => $lead) {
                $affiliate = $lead->user->nama_depan.' '.$lead->user->nama_belakang;
                $customerName = $lead->customer_name;
                $vendor = $lead->vendor->name;
                $email = $lead->email;
                $nomorTelepon = $lead->no_telepon;
                $tanggal = $this->convertDateView($lead->date);
                
                if ($lead->status == Lead::ON_PROCESS) {
                    $status = 'ON PROCESS';
                } else if ($lead->status == Lead::SUCCESS) {
                    $status = 'SUCCESS';
                } else {
                    $status = 'CANCELED';
                }

                fputcsv($file, [$affiliate, $customerName, $vendor, $email, $nomorTelepon, $tanggal, $status]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
