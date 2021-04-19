<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Transaction;
use App\Models\Click;
use App\Models\Lead;
use App\Models\Media;
use App\Models\ServiceCommission;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Withdrawal;
use App\Models\WithdrawalStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;

class AffiliateController extends Controller
{
    public function index() {
        return view('admin.affiliate.index');
    }

    public function vendor() {
        return view('admin.affiliate.vendor');
    }

    public function detail(Request $request) {
        if ($request->ajax()) {
            $transaction = Transaction::leftJoin('leads', 'leads.id', '=', 'transactions.lead_id')->where('leads.user_id', $request->user_id)->get();
            
            $user = User::findOrfail($request->user_id);
            
            $lead = Lead::where('user_id', $request->user_id);

            $click = Click::where(['user_id' => $request->user_id])->get();
            
            $withdrawal = Withdrawal::where(['user_id' => $request->user_id, 'withdrawal_status_id' => WithdrawalStatus::APPROVE])->get();
            
            $data = [
                'user_id' => $request->user_id,
                'username_aff' => $user->username,
                'no_telepon' => $user->no_telepon,
                'email' => $user->email,
                'balance' => $this->currencyView($user->saldo_awal + $transaction->sum('commission') - $withdrawal->sum('total')),
                'commission' => $this->currencyView($transaction->sum('commission')),
                'transaction_count' => $transaction->count(),
                'click' => count($click) > 0 ? $click->sum('click') : 0,
                'signup' => $lead->count(),
                'conversion' => count($click) > 0 ? round($lead->count() / $click->sum('click') * 100, 2) : 0 .'%'
            ];

            return response()->json(['detail' => $data]);
        }
    }

    public function detailVendor(Request $request) {
        if ($request->ajax()) {
            $transaction = Transaction::leftJoin('leads', 'leads.id', '=', 'transactions.lead_id')->where(['leads.user_id' => $request->user_id, 'leads.vendor_id' => $request->vendor_id])->get();
            
            $user = User::findOrfail($request->user_id);
            
            $lead = Lead::where(['user_id' => $request->user_id, 'vendor_id' => $request->vendor_id]);

            $click = Click::where(['user_id' => $request->user_id, 'vendor_id' => $request->vendor_id])->get();
            
            $withdrawal = Withdrawal::where(['user_id' => $request->user_id, 'withdrawal_status_id' => WithdrawalStatus::APPROVE])->get();
            
            $data = [
                'user_id' => $request->user_id,
                'username_aff' => $user->username,
                'no_telepon' => $user->no_telepon,
                'email' => $user->email,
                'balance' => !empty($transaction) ? $user->saldo_awal + $this->currencyView($transaction->sum('commission') - $withdrawal->sum('total')) : $user->saldo_awal,
                'commission' => $this->currencyView($transaction->sum('commission')),
                'transaction_count' => $transaction->count(),
                'click' => count($click) > 0 ? $click->sum('click') : 0,
                'signup' => $lead->count(),
                'conversion' => count($click) > 0 ? round($lead->count() / $click->sum('click') * 100, 2) : 0 .'%'
            ];

            return response()->json(['detail' => $data]);
        }
    }

    public function downloadTemplate() {
        $date = Carbon::now()->format("Ymd");
        $fileName = "template_affiliate_$date.csv";

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $listColumn = ['Nama Depan', 'Nama Belakang', 'Email', 'Nomor Telepon', 'Nomor Rekening', 'Atasnama Rekening', 'Nama Bank'];

        $callback = function() use ($listColumn) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $listColumn);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function uploadAffiliate(Request $request) {
        if ($request->ajax()) {
            if ($request->file('affiliateFile')) {
                $validator = Validator::make($request->all(), [
                    'affiliateFile' => 'required'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $value) {
                        return $this->sendResponse($value[0], '', 400);
                    }
                }

                $mustExtension = ['csv'];

                $file = $request->file('affiliateFile');
                $originalExtension = $file->getClientOriginalExtension();
                
                if(in_array($originalExtension, $mustExtension)){
                    $dataCsv = Reader::createFromPath($file, 'r');
                    $dataCsv->setHeaderOffset(0);

                    $createUpdateSuccess = 0;
                    $failed = 0;
                    $failedMessage = 0;
                    $userUpdated = $userCreated = NULL;

                    $url = URL::to('/').'/share/';
                    $media = Media::get()->keyBy('name');

                    $serviceCommission = ServiceCommission::whereHas('vendor', function($query) {
                        $query->where('active', true);
                    })->get();

                    foreach ($dataCsv as $data) {
                        $userAvailable = User::where('email', $data['Email'])->first();
                        if ($userAvailable) {
                            $userUpdated = $userAvailable->update([
                                'nama_depan'     => $data['Nama Depan'],
                                'nama_belakang'  => $data['Nama Belakang'],
                                'no_telepon'     => $data['Nomor Telepon'],
                                'nomor_rekening' => $data['Nomor Rekening'],
                                'atasnama_bank'  => $data['Atasnama Rekening'],
                                'nama_bank'      => $data['Nama Bank']
                            ]);
                        } else {
                            try {
                                $userCreated = User::create([
                                    'nama_depan'     => $data['Nama Depan'],
                                    'nama_belakang'  => $data['Nama Belakang'],
                                    'email'          => $data['Email'],
                                    'no_telepon'     => $data['Nomor Telepon'],
                                    'nomor_rekening' => $data['Nomor Rekening'],
                                    'atasnama_bank'  => $data['Atasnama Rekening'],
                                    'nama_bank'      => $data['Nama Bank'],
                                    'join_date'      => Carbon::NOW()
                                ]);
                            } catch (\Exception $e) {
                                $failedMessage = $e->getMessage();
                            }
                        }

                        if ($userCreated || $userUpdated) {
                            $createUpdateSuccess++;
                            if ($userCreated) {
                                $dataService = [];
                                $vendorHasService = [];

                                foreach ($serviceCommission as $idxVendor => $service) {
                                    if (!in_array($service->vendor_id, $vendorHasService)) {$vendorHasService[] = $service->vendor_id;} else {continue;}
                                    $marketing_text[$service->id] = "'".$service->marketing_text."'";

                                    foreach ($media as $key => $value) {
                                        $link[$key] = $url.$service->id.'.'.$userCreated->id.'.'.$value->id;
                                    }

                                    $dataService[] = [
                                        'service_id' => $service->id,
                                        'commission' => $service->commission_type_id == 1 ? 'Rp. '.$service->commission_value : $service->commission_value.'%',
                                        'service_name' => $service->title,
                                        'link' => $link,
                                        'marketing_text' => $marketing_text
                                    ];
                                }

                                $dataMail = [
                                    'nama_lengkap' => $data['Nama Depan'].' '.$data['Nama Belakang'],
                                    'serviceList' => $dataService,
                                ];

                                Mail::send(['html' => 'admin.affiliate.email_link_affiliate'], $dataMail, function($message) use ($data) {
                                    $message->to($data['Email'])->subject('Email Link Affiliate');
                                });
                            }
                        } else {
                            $failed++;
                            $failedData[] = [
                                'email' => $data['Email'],
                                'message' => $failedMessage
                            ];
                        }
                    }

                    if ($createUpdateSuccess == $dataCsv->count()) {
                        $resultMessage = "$createUpdateSuccess User berhasil diupload";
                        return $this->sendResponse($resultMessage, '', 200);
                    } elseif ($failed > 0 && $createUpdateSuccess > 0) {
                        $resultMessage = "$createUpdateSuccess User berhasil ditambahkan, $failed User gagal ditambahkan";
                        return $this->sendResponse($resultMessage, $failedData, 200);
                    } else {
                        $resultMessage = "$failed User gagal ditambahkan";
                        return $this->sendResponse($resultMessage, $failedData, 500);
                    }
                }

                $messages = 'File upload must be csv type';
                return $this->sendResponse($messages, '', 400);
            }
        }
    }

    public function resendEmailLinkAffiliate(Request $request) {
        if ($request->ajax()) {
            $url = URL::to('/').'/share/';
            $media = Media::get()->keyBy('name');

            $userList = User::whereIn('id', $request->arrayID)->get(['id','nama_depan','nama_belakang','email']);

            $serviceCommission = ServiceCommission::whereHas('vendor', function ($query) {
                $query->where('active', true);
            })->get();

            foreach ($userList as $key => $user) {
                foreach ($serviceCommission as $idxVendor => $service) {
                    $marketing_text[$service->id] = $service->marketing_text;
    
                    foreach ($media as $key => $value) {
                        $link[$key] = $url.$service->id.'.'.$user->id.'.'.$value->id;
                    }
    
                    $dataService[] = [
                        'service_id' => $service->id,
                        'link' => $link,
                        'marketing_text' => $marketing_text
                    ];
                }
    
                $dataMail = [
                    'nama_lengkap' => $user->nama_depan.' '.$user->nama_belakang,
                    'serviceList' => $dataService,
                ];
    
                
                Mail::send(['html' => 'admin.affiliate.email_link_affiliate'], $dataMail, function($message) use ($user) {
                    $message->to($user->email)->subject('Email Link Affiliate');
                });
            }

            $messages = 'Email Link Affiliate Resend Successfully';
            return $this->sendResponse($messages, '', 200);
        }
    }
}