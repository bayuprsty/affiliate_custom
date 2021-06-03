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

        $listColumn = ['nama depan', 'nama belakang', 'email', 'nomor telepon', 'nomor rekening', 'atasnama rekening', 'nama bank'];

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
                        $userAvailable = User::where('email', $data['email'])->first();
                        if ($userAvailable) {
                            $userUpdated = $userAvailable->update([
                                'nama_depan'     => $data['nama depan'],
                                'nama_belakang'  => $data['nama belakang'],
                                'no_telepon'     => $data['nomor telepon'],
                                'nomor_rekening' => $data['nomor rekening'],
                                'atasnama_bank'  => $data['atasnama rekening'],
                                'nama_bank'      => $data['nama bank']
                            ]);
                        } else {
                            try {
                                $userCreated = User::create([
                                    'nama_depan'     => $data['nama depan'],
                                    'nama_belakang'  => $data['nama belakang'],
                                    'email'          => $data['email'],
                                    'no_telepon'     => $data['nomor telepon'],
                                    'nomor_rekening' => $data['nomor rekening'],
                                    'atasnama_bank'  => $data['atasnama rekening'],
                                    'nama_bank'      => $data['nama bank'],
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

                                foreach ($serviceCommission as $idxVendor => $service) {
                                    $arrText = explode("\r\n", $service->marketing_text);
                                    $stringMarketingText = implode("\n", $arrText);
                                    $twitterMarketingText = $service->twitter_marketing_text;

                                    foreach ($media as $key => $value) {
                                        $link[$key] = $url.$service->id.'.'.$userCreated->id.'.'.$value->id;

                                        $replacedMarketingText = str_replace('{{link}}', $link[$key], $stringMarketingText);
                                        $replacedTwitterMarketingText = str_replace('{{link}}', $link[$key], $twitterMarketingText);
                                        
                                        if ($key !== 'Website' && $key == 'Twitter') {
                                            $marketingText[$key] = rawurlencode($replacedTwitterMarketingText);
                                        } else {
                                            $marketingText[$key] = rawurlencode($replacedMarketingText);
                                        }
                                    }

                                    $dataService[] = [
                                        'service_id' => $service->id,
                                        'link' => $link,
                                        'marketing_text' => $marketingText
                                    ];
                                }

                                $dataMail = [
                                    'nama_lengkap' => $data['nama depan'].' '.$data['nama belakang'],
                                    'serviceList' => $dataService,
                                ];

                                // if ($data['email'] != '' || !empty($data['email'] || !is_null($data['email']))) {
                                //     Mail::send(['html' => 'admin.affiliate.email_link_affiliate'], $dataMail, function($message) use ($data) {
                                //         $message->to($data['email'])->subject('Email Link Affiliate');
                                //     });
                                // }
                            }
                        } else {
                            $failed++;
                            $failedData[] = [
                                'email' => $data['email'],
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

    public function uploadData(Request $request) {
        if ($request->ajax()) {
            $userUpdated = $userCreated = NULL;

            $url = URL::to('/').'/share/';
            $media = Media::get()->keyBy('name');

            $serviceCommission = ServiceCommission::whereHas('vendor', function($query) {
                $query->where('active', true);
            })->get();

            $userAvailable = User::where('email', $request->email)->first();
            if ($userAvailable) {
                $userUpdated = $userAvailable->update([
                    'nama_depan'     => $request->nama_depan,
                    'nama_belakang'  => $request->nama_belakang,
                    'no_telepon'     => $request->nomor_telepon,
                    'nomor_rekening' => $request->nomor_rekening,
                    'atasnama_bank'  => $request->atasnama_bank,
                    'nama_bank'      => $request->nama_bank
                ]);
            } else {
                try {
                    $userCreated = User::create([
                        'nama_depan'     => $request->nama_depan,
                        'nama_belakang'  => $request->nama_belakang,
                        'email'          => $request->email,
                        'no_telepon'     => $request->nomor_telepon,
                        'nomor_rekening' => $request->nomor_rekening,
                        'atasnama_bank'  => $request->atasnama_bank,
                        'nama_bank'      => $request->nama_bank,
                        'join_date'      => Carbon::NOW()
                    ]);
                } catch (\Exception $e) {
                    $failedMessage = $e->getMessage();
                }

                if ($userCreated || $userUpdated) {
                    if ($userCreated) {
                        $dataService = [];

                        foreach ($serviceCommission as $idxVendor => $service) {
                            $arrText = explode("\r\n", $service->marketing_text);
                            $stringMarketingText = implode("\n", $arrText);
                            $twitterMarketingText = $service->twitter_marketing_text;

                            foreach ($media as $key => $value) {
                                $link[$key] = $url.$service->id.'.'.$userCreated->id.'.'.$value->id;

                                $replacedMarketingText = str_replace('{{link}}', $link[$key], $stringMarketingText);
                                $replacedTwitterMarketingText = str_replace('{{link}}', $link[$key], $twitterMarketingText);
                                
                                if ($key !== 'Website' && $key == 'Twitter') {
                                    $marketingText[$key] = rawurlencode($replacedTwitterMarketingText);
                                } else {
                                    $marketingText[$key] = rawurlencode($replacedMarketingText);
                                }
                            }

                            $dataService[] = [
                                'service_id' => $service->id,
                                'link' => $link,
                                'marketing_text' => $marketingText
                            ];
                        }

                        $dataMail = [
                            'nama_lengkap' => $request->nama_depan.' '.$request->nama_belakang,
                            'serviceList' => $dataService,
                        ];

                        if ($request->email != '' || !empty($request->email || !is_null($request->email))) {
                            Mail::send(['html' => 'admin.affiliate.email_link_affiliate'], $dataMail, function($message) use ($request) {
                                $message->to($request->email)->subject('Email Link Affiliate');
                            });
                        }
                    }
                }
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
                    $arrText = explode("\r\n", $service->marketing_text);
                    $stringMarketingText = implode("\n", $arrText);
                    $twitterMarketingText = $service->twitter_marketing_text;
                    
                    foreach ($media as $key => $value) {
                        $link[$key] = $url.$service->id.'.'.$user->id.'.'.$value->id;

                        $replacedMarketingText = str_replace('{{link}}', $link[$key], $stringMarketingText);
                        $replacedTwitterMarketingText = str_replace('{{link}}', $link[$key], $twitterMarketingText);
                        
                        if ($key !== 'Website' && $key == 'Twitter') {
                            $marketingText[$key] = rawurlencode($replacedTwitterMarketingText);
                        } else {
                            $marketingText[$key] = rawurlencode($replacedMarketingText);
                        }
                    }

                    $dataService[] = [
                        'service_id' => $service->id,
                        'link' => $link,
                        'marketing_text' => $marketingText
                    ];
                    // dd($dataService);
                    // dd(nl2br($marketing_text[$service->id]));
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

    public function exportCsv() {
        $affiliateList = User::query()->where('role', 'affiliator')->latest()->get();

        $date = Carbon::now()->format("Ymd");
        $fileName = "data_affiliate_$date.csv";

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $listColumn = ['Nama Depan', 'Nama Belakang', 'Email', 'Nomor Telepon', 'Nomor Rekening', 'Atasnama Rekening', 'Nama Bank'];

        $callback = function() use ($listColumn, $affiliateList) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $listColumn);

            foreach ($affiliateList as $indexAffiliate => $affiliate) {
                $namaDepan = $affiliate->nama_depan;
                $namaBelakang = $affiliate->nama_belakang;
                $nomorTelepon = $affiliate->no_telepon;
                $email = $affiliate->email;
                $nomorRekening = $affiliate->nomor_rekening;
                $namaRekening = $affiliate->atasnama_bank;
                $namaBank = $affiliate->nama_bank;

                fputcsv($file, [$namaDepan, $namaBelakang, $nomorTelepon, $email, $nomorRekening, $namaRekening, $namaBank]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}