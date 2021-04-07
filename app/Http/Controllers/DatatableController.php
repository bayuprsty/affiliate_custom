<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use DataTables;
use URL;

use App\Models\Transaction;
use App\Models\Lead;
use App\Models\Vendor;
use App\Models\Withdrawal;
use App\Models\WithdrawalStatus;
use App\Models\ServiceCommission;
use App\Models\Payout;

use App\Models\User;
use App\Models\Click;
use App\Models\Media;

class DatatableController extends Controller
{
    ############################################### ADMIN ####################################################
    public function topAffiliateAdmin(Request $request) {
        if ($request->ajax()) {
            $user = User::select(
                        'users.id as user_id',
                        'users.username as username',
                        'saldo_awal',
                        DB::raw('count(leads.id) as signup')
                        // DB::raw('SUM(transactions.commission) - SUM(withdrawals.total) as balance'),
                    )
                    ->leftJoin('leads', 'users.id', '=', 'leads.user_id')
                    ->where('users.role', 'affiliator')->whereNotNull('leads.email')
                    ->groupBy('leads.user_id')->orderBy('signup', 'DESC');
                    
            if ($request->filter_periode) {
                if ($request->filter_periode == 'custom') {
                    if (!empty($request->dateStart && !empty($request->dateEnd))) {
                        $user->whereBetween('leads.date', [$request->dateStart, $request->dateEnd]);
                    }
                } else {
                    $filter_periode = now()->subDays($request->filter_periode)->toDateString();
                    $user->where('leads.date', '>=', $filter_periode);
                }
            }

            $user = $user->get()->where('signup', '>', 0);

            $commission = Transaction::select(
                'leads.user_id as user_id',
                DB::raw('SUM(transactions.commission) as total_commission')
            )->leftJoin('leads', 'leads.id', '=', 'transactions.lead_id')
            ->groupBy('leads.user_id')->get()->keyBy('user_id')->toArray();

            $withdrawals = Withdrawal::select(
                            'withdrawals.user_id as user_id',
                            DB::raw('SUM(withdrawals.total) as total')
                        )
                        ->where('withdrawals.withdrawal_status_id', WithdrawalStatus::APPROVE)
                        ->groupBy('withdrawals.user_id')->get()->keyBy('user_id')->toArray();

            $click = Click::select(
                        'clicks.user_id as user_id',
                        DB::raw('SUM(click) as click')
                    )->groupBy('clicks.user_id')->get()->keyBy('user_id')->toArray();

            foreach ($user as $key => $value) {
                $withdrawRequest = isset($withdrawals[$value->user_id]) ? $withdrawals[$value->user_id]['total'] : 0;
                $allCommission = isset($commission[$value->user_id]) ? $commission[$value->user_id]['total_commission'] : 0;
                $balance = $value->saldo_awal + $allCommission - $withdrawRequest;

                $conversion = isset($click[$value->user_id]) ? round($value->signup / $click[$value->user_id]['click'] * 100, 2) : 0;

                if (!isset($rows)) {
                    $rows[$key] = [
                        'user_id' => $value->user_id,
                        'username' => $value->username,
                        'commission' => isset($commission[$value->user_id]['total_commission'])?$this->currencyView($commission[$value->user_id]['total_commission']) : 0,
                        'signup' => $value->signup,
                        'balance' => $this->currencyView($balance),
                        'click' => isset($click[$value->user_id]) ? $click[$value->user_id]['click'] : 0,
                        'conversion' => $conversion
                    ];
                } else {
                    $temp = [
                        'user_id' => $value->user_id,
                        'username' => $value->username,
                        'commission' => isset($commission[$value->user_id]['total_commission'])?$this->currencyView($commission[$value->user_id]['total_commission']) : 0,
                        'signup' => $value->signup,
                        'balance' => $this->currencyView($balance),
                        'click' => isset($click[$value->user_id]) ? $click[$value->user_id]['click'] : 0,
                        'conversion' => $conversion
                    ];
                }

                if(isset($temp)) {
                    if ($rows[$key-1]['signup'] == $temp['signup']) {
                        if ($rows[$key-1]['conversion'] > $temp['conversion']) {
                            $rows[$key] = $temp;
                        } else {
                            $temp2 = $rows[$key-1];
                            $rows[$key-1] = $temp;
                            $rows[$key] = $temp2;
                        }
                    } else {
                        $rows[$key] = $temp;
                    }
                }
            }

            if (isset($rows)) {
                return DataTables::of($rows)
                        ->addIndexColumn()
                        ->editColumn('conversion', function($row){
                            return $this->percentageView($row['conversion']);
                        })
                        ->make(true);
            } else {
                return DataTables::of($user)
                        ->addIndexColumn()
                        ->make(true);
            }
        }
    }

    public function affiliateAdmin(Request $request) {
        if ($request->ajax()) {
            $user = User::select(
                            'users.id as user_id',
                            'users.username as username',
                            'users.nama_depan as nama_depan',
                            'users.nama_belakang as nama_belakang',
                            'saldo_awal'
                        )
                        ->where('role', 'affiliator')
                        ->latest('id')->get();
            
            $lead = Lead::select(
                                'leads.user_id as user_id',
                                DB::raw('SUM(transactions.commission) as commission'),
                                DB::raw('count(leads.id) as signup')
                            )
                            ->leftJoin('transactions', 'transactions.lead_id', '=', 'leads.id')
                            ->whereNotNull('leads.email')
                            ->groupBy('user_id')->get()->keyBy('user_id')->toArray();
            
            $withdrawals = Withdrawal::select(
                'withdrawals.user_id as user_id',
                DB::raw('SUM(withdrawals.total) as total')
            )
            ->where('withdrawals.withdrawal_status_id', WithdrawalStatus::APPROVE)
            ->groupBy('withdrawals.user_id')->get()->keyBy('user_id')->toArray();

            $click = Click::select(
                'clicks.user_id as user_id',
                DB::raw('SUM(click) as click')
            )->groupBy('clicks.user_id')->get()->keyBy('user_id')->toArray();

            return DataTables::of($user)
                        ->addIndexColumn()
                        ->editColumn('username', function($row) {
                            return '<a href="'.route('user.detailUser', $row->user_id).'">'.$row->nama_depan.' '.$row->nama_belakang.'</a>';
                        })
                        ->addColumn('balance', function($row) use ($withdrawals, $lead){
                            $commission = isset($lead[$row->user_id]) ? $lead[$row->user_id]['commission'] : 0;
                            if (isset($withdrawals[$row->user_id])) {
                                $balance = $row->saldo_awal + $commission - $withdrawals[$row->user_id]['total'];
                                return $this->currencyView($balance);
                            }

                            return $this->currencyView($row->saldo_awal + $commission);
                        })
                        ->editColumn('commission', function($row) use ($lead) {
                            $commission = isset($lead[$row->user_id]) ? $lead[$row->user_id]['commission'] : 0;
                            return $this->currencyView($commission);
                        })
                        ->addColumn('click', function($row) use ($click){
                            return isset($click[$row->user_id]) ? $click[$row->user_id]['click'] : 0;
                        })
                        ->addColumn('signup', function($row) use ($lead) {
                            return isset($lead[$row->user_id]) ? $lead[$row->user_id]['signup'] : 0;
                        })
                        ->addColumn('conversion', function($row) use ($click, $lead){
                            $signup = isset($lead[$row->user_id]) ? $lead[$row->user_id]['signup'] : 0;
                            $click = isset($click[$row->user_id]) ? $click[$row->user_id]['click'] : 0;

                            $conversion = $click > 0 ? round($signup / $click * 100, 2) : 0;
                            return $conversion.'%';
                        })
                        ->addColumn('action', function($row) {
                            $btn = '<div class="form-inline row">
                                        <center>
                                            <button id="detailAffiliate" class="btn btn-info btn-sm btn-circle mr-2" title="Detail" data-id="'.$row->user_id.'"><i class="fa fa-eye"></i></button>
                                            <button id="resendEmailLink" class="btn btn-danger btn-sm btnlink_'.$row['user_id'].'" title="Resend Email Link Affiliate" data-id="'.$row['user_id'].'"><i class="fas fa-envelope mr-1"></i>Resend Link</button>
                                        </center>
                                    </div>';

                            return $btn;
                        })
                        ->rawColumns(['username', 'action'])
                        ->make(true);
        }
    }

    public function affiliateByVendor(Request $request) {
        if ($request->ajax()) {
            $dataUser = User::where('users.role', 'affiliator')->latest('id')->get();

            $dataVendor = Vendor::all();

            $dataLead = Lead::select(
                'user_id',
                'vendor_id',
                DB::raw('SUM(transactions.commission) as commission'),
                DB::raw('count(leads.id) as signup')
            )->leftJoin('transactions', 'transactions.lead_id', '=', 'leads.id')
            ->whereNotNull('leads.email')
            ->groupBy('user_id', 'vendor_id')->get();

            foreach ($dataLead as $key => $lead) {
                $allLead[$lead->user_id][$lead->vendor_id] = [
                    'commission' => $lead->commission,
                    'signup' => $lead->signup
                ];
            }

            $click = Click::select(
                'clicks.user_id as user_id',
                'clicks.vendor_id as vendor_id',
                DB::raw('SUM(click) as click')
            )->groupBy('clicks.user_id', 'clicks.vendor_id')->get();

            foreach ($click as $key => $value) {
                $allClick[$value->user_id][$value->vendor_id] = $value->click;
            }

            foreach ($dataUser as $key => $user) {
                foreach ($dataVendor as $key => $vendor) {
                    $signup = isset($allLead[$user->id][$vendor->id]) ? $allLead[$user->id][$vendor->id]['signup'] : 0;
                    $click = isset($allClick[$user->id][$vendor->id]) ? $allClick[$user->id][$vendor->id] : 0;
                    
                    $result[] = [
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'vendor_id' => $vendor->id,
                        'vendor_name' => $vendor->name,
                        'commission' => isset($allLead[$user->id][$vendor->id]) ? $allLead[$user->id][$vendor->id]['commission'] : 0,
                        'signup' => $signup,
                        'click' => $click,
                        'conversion' => $click > 0 ? round($signup / $click * 100, 2) : 0,
                    ];
                }
            }

            if (isset($result)) {
                return Datatables::of($result)
                        ->addIndexColumn()
                        ->editColumn('username', function ($row) {
                            return '<a href="'.route('user.detailUser', $row['user_id']).'">'.$row['username'].'</a>';
                        })
                        ->editColumn('commission', function($row) {
                            return $this->currencyView($row['commission']);
                        })
                        ->editColumn('conversion', function($row){
                            return $this->percentageView($row['conversion']);
                        })
                        ->addColumn('action', function($row) {
                            $btn = '<div class="form-inline row">
                                        <center>
                                            <button id="detailAffiliate" class="btn btn-info btn-sm btn-circle" title="Detail" data-id="'.$row['user_id'].'" data-vendor="'.$row['vendor_id'].'"><i class="fa fa-eye"></i></button>
                                        </center>
                                    </div>';

                            return $btn;
                        })
                        ->rawColumns(['username', 'action'])
                        ->make(true);
            } else {
                return Datatables::of($dataLead)
                        ->addIndexColumn()
                        ->make(true);
            }
        }
    }

    public function detailAffiliateTransaction(Request $request) {
        if ($request->ajax()) {
            $transaction = Transaction::leftJoin('leads', 'leads.id', '=', 'transactions.lead_id')->where('leads.user_id', $request->user_id);

            if ($request->vendor_id) {
                $transaction->where('leads.vendor_id', $request->vendor_id);
            }
            
            return DataTables::of($transaction->latest('transactions.created_at')->get())
                        ->addIndexColumn()
                        ->addColumn('customer_name', function($row) {
                            return $row->lead->customer_name;
                        })
                        ->addColumn('vendor_name', function($row) {
                            return $row->lead->vendor->name;
                        })
                        ->addColumn('product_service', function($row) {
                            return $row->service_commission->title;
                        })
                        ->editColumn('transaction_date', function($row) {
                            return $this->convertDateView($row->transaction_date);
                        })
                        ->editColumn('amount', function($row) {
                            return $this->currencyView($row->amount);
                        })
                        ->editColumn('commission', function($row) {
                            return $this->currencyView($row->commission);
                        })
                        ->editColumn('status', function($row){
                            if ($row->lead->status == Lead::ON_PROCESS) {
                                $statusLead = '<span class="badge badge-primary">ON PROCESS</span>';
                            } else if ($row->lead->status == Lead::SUCCESS) {
                                $statusLead = '<div class="badge badge-success">SUCCESS</div>';
                            } else {
                                $statusLead = '<span class="badge badge-danger">CANCELED</span>';
                            }

                            return $statusLead;
                        })
                        ->rawColumns(['status'])
                        ->make(true);
        }
    }

    public function transactionAdmin(Request $request) {
        if ($request->ajax()) {
            $transaction = Transaction::orderBy('transactions.id', 'DESC');
            if ($request->status !== 'all') {
                $transaction->leftJoin('leads', 'leads.id', '=', 'transactions.lead_id');
                if ($request->status == Lead::ON_PROCESS) {
                    $transaction->where('leads.status', Lead::ON_PROCESS);
                } elseif ($request->status == Lead::SUCCESS) {
                    $transaction->where('leads.status', Lead::SUCCESS);
                } else {
                    $transaction->where('leads.status', Lead::CANCELED);
                }
            }

            if (!empty($request->dateStart && !empty($request->dateEnd))) {
                $transaction->whereBetween('transactions.transaction_date', [$request->dateStart, $request->dateEnd]);
            }

            return DataTables::of($transaction->get())
                        ->addIndexColumn()
                        ->addColumn('username_aff', function($row) {
                            $id = $row->lead->user->id;
                            $namaDepan = $row->lead->user->nama_depan;
                            $namaBelakang = $row->lead->user->nama_belakang;

                            return '<a href="'.route('user.detailUser', $id).'">'.$namaDepan.' '.$namaBelakang.'</a>';
                        })
                        ->addColumn('customer_name', function($row) {
                            return $row->lead->customer_name;
                        })
                        ->addColumn('vendor_name', function($row) {
                            return $row->lead->vendor->name;
                        })
                        ->addColumn('product_service', function($row) {
                            return $row->service_commission->title;
                        })
                        ->editColumn('transaction_date', function($row) {
                            return $this->convertDateView($row->transaction_date);
                        })
                        ->editColumn('amount', function($row) {
                            return $this->currencyView($row->amount);
                        })
                        ->editColumn('commission', function($row) {
                            return $this->currencyView($row->commission);
                        })
                        ->editColumn('created_by', function($row) {
                            if ($row->created_by_system == 1) {
                                return 'System';
                            } else {
                                return 'Manual';
                            }
                        })
                        ->editColumn('status', function($row){
                            if ($row->cancel == 1) {
                                // $statusLead = '<span id="button" aria-describedby="tooltip">CANCELED</span><div id="tooltip" role="tooltip">TES 123<div id="arrow" data-popper-arrow></div></div>';
                                $statusLead = '<span class="badge badge-danger" data-toggle="tooltip" data-placement="top" title="" data-original-title="'.$row->cancel_reason.'">CANCELED</span>';
                            } else {
                                if ($row->lead->status == Lead::ON_PROCESS) {
                                    $statusLead = '<span class="badge badge-primary">ON PROCESS</span>';
                                } else if ($row->lead->status == Lead::SUCCESS) {
                                    $statusLead = '<div class="badge badge-success">SUCCESS</div>';
                                } else {
                                    $statusLead = '<span class="badge badge-danger">CANCELED</span>';
                                }
                            }

                            return $statusLead;
                        })
                        ->addColumn('action', function($row) {
                            $btn = '';
                            if ($row->cancel == 0) {
                                $btn = '<div class="form-inline row">
                                        <center>
                                            <button id="cancelTransaction" class="btn btn-danger btn-sm btn-circle" title="Cancel" data-id="'.$row->id.'"><i class="fa fa-times"></i></button>
                                        </center>
                                    </div>';
                            }   

                            return $btn;
                        })
                        ->rawColumns(['username_aff', 'status', 'action'])
                        ->make(true);
        }
    }

    public function leadAdmin(Request $request) {
        if (request()->ajax()) {
            $lead = Lead::whereNotNull('email')->latest('id');
            if ($request->status !== 'all') {
                if ($request->status == Lead::ON_PROCESS) {
                    $lead->where('status', Lead::ON_PROCESS);
                } elseif ($request->status == Lead::SUCCESS) {
                    $lead->where('status', Lead::SUCCESS);
                } else {
                    $lead->where('status', Lead::CANCELED);
                }
            }

            if (!empty($request->dateStart && !empty($request->dateEnd))) {
                $lead->whereBetween('date', [$request->dateStart, $request->dateEnd]);
            }

            return DataTables::of($lead->get())
                        ->addIndexColumn()
                        ->editColumn('user_id', function($row) {
                            $namaDepan = $row->user->nama_depan;
                            $namaBelakang = $row->user->nama_belakang;
                            return $namaDepan.' '.$namaBelakang;
                        })
                        ->editColumn('vendor_id', function($row) {
                            $vendorName = $row->vendor->name;
                            return $vendorName;
                        })
                        ->editColumn('date', function($row) {
                            return $this->convertDateView($row->date);
                        })
                        ->editColumn('status', function($row){
                            if ($row->status == Lead::ON_PROCESS) {
                                $statusLead = '<span class="badge badge-primary">ON PROCESS</span>';
                            } else if ($row->status == Lead::SUCCESS) {
                                $statusLead = '<span class="badge badge-success">SUCCESS</span>';
                            } else {
                                $statusLead = '<span class="badge badge-danger">CANCELED</span>';
                            }

                            return $statusLead;
                        })
                        ->addColumn('action', function($row) {
                            $btn = '';

                            if ($row->status == Lead::ON_PROCESS) {
                                $btn = '<div class="form-inline row">
                                            <center>
                                                <button id="leadProcess" class="btn btn-warning btn-sm btn-circle" title="Edit" data-id="'.$row->id.'"><i class="fa fa-pencil-alt"></i></button>
                                                <button id="cancelLead" class="btn btn-danger btn-sm btn-circle" title="Delete" data-id="'.$row->id.'"><i class="fa fa-times"></i></button>
                                            </center>
                                        </div>';
                            }

                            return $btn;
                        })
                        ->rawColumns(['status', 'action'])
                        ->make(true);
        }
    }

    public function vendorAdmin(Request $request) {
        if (request()->ajax()) {
            $vendor = Vendor::latest()->get();

            return DataTables::of($vendor)
                        ->addIndexColumn()
                        ->addColumn('alamat', function($row) {
                            $alamat = $row->jalan.', '.$row->kecamatan.', '.$row->kabupaten_kota.', '.$row->provinsi.' '.$row->kodepos;
                            return $alamat;
                        })
                        ->addColumn('action', function($row) {
                            if ($row->active == false) {
                                $btn = '<button id="button-activate" class="btn btn-primary btn-sm btn-circle" title="Activate" data-id="'. $row->id .'"><i class="fas fa-check"></i></button>';
                            } else {
                                $btn = '<div class="form-inline row text-center">
                                            <a href="'.route("vendor.edit", $row->id).'" class="btn btn-warning btn-sm btn-circle" title="Edit"><i class="fa fa-pencil-alt"></i></a>&nbsp;
                                            <button id="button-delete" class="btn btn-danger btn-sm btn-circle" title="Deactivate" data-id="'. $row->id .'"><i class="fas fa-times"></i></button>
                                        </div>';
                            }

                            return $btn;
                        })
                        ->rawColumns(['action'])
                        ->make(true);
        }
    }

    public function withdrawalAdmin(Request $request) {
        if (request()->ajax()) {
            $withdrawal = Withdrawal::latest()->get();

            return DataTables::of($withdrawal)
                        ->addIndexColumn()
                        ->editColumn('user_id', function($row) {
                            $namaDepan = $row->user->nama_depan;
                            $namaBelakang = $row->user->nama_belakang;

                            return "$namaDepan $namaBelakang";
                        })
                        ->addColumn('no_rekening', function($row) {
                            return $row->user->nomor_rekening;
                        })
                        ->addColumn('atasnama_rekening', function($row) {
                            return $row->user->atasnama_bank;
                        })
                        ->addColumn('nama_bank', function($row) {
                            return $row->user->nama_bank;
                        })
                        ->editColumn('total', function($row) {
                            return $this->currencyView($row->total);
                        })
                        ->editColumn('status', function($row) {
                            if ($row->withdrawal_status_id == WithdrawalStatus::REQUEST) {
                                return '<span class="badge badge-primary">Requested</span>';
                            } else {
                                return '<span class="badge badge-success">Approved</span>';
                            }
                        })
                        ->addColumn('action', function($row) {
                            $btn = '';
                            if ($row->withdrawal_status_id == WithdrawalStatus::REQUEST) {
                                $btn = '<div class="form-inline row">
                                        <center>
                                            <button id="prosesWithdrawal" data-id="'.$row->id.'" class="btn btn-warning btn-sm" title="Edit">Proses</button>
                                            <button id="deleteWithdrawal" data-id="'.$row->id.'" class="btn btn-danger btn-sm" title="Delete">Delete</button>
                                        </center>
                                    </div>';
                            }

                            return $btn;
                        })
                        ->rawColumns(['status','action'])
                        ->make(true);
        }
    }

    public function userAdmin(Request $request) {
        if ($request->ajax()) {
            $user = User::all();

            return DataTables::of($user)
                            ->addIndexColumn()
                            ->addColumn('nama_lengkap', function($row) {
                                return $row->nama_depan.' '.$row->nama_belakang;
                            })
                            ->editColumn('role', function($row) {
                                if ($row->role == 'admin') {
                                    return 'Administrator';
                                } else {
                                    return 'Affiliate';
                                }
                            })
                            ->editColumn('join_date', function($row) {
                                return $this->convertDateView($row->join_date);
                            })
                            ->addColumn('action', function($row) {
                                $btn = '<div>
                                            <center>
                                                <a href="'.route("user.detailUser", $row->id).'" class="btn btn-info btn-sm btn-circle" title="Detail User"><i class="fa fa-eye"></i></a>
                                                <a href="'.route("user.editUser", $row->id).'" class="btn btn-warning btn-sm btn-circle" title="Edit User"><i class="fa fa-pencil-alt"></i></a>
                                                <button id="deleteUser" data-id="'.$row->id.'" class="btn btn-danger btn-sm btn-circle" title="Delete User"><i class="fas fa-times"></i></button>
                                            </center>
                                        </div>';
                                
                                return $btn;
                            })
                            ->rawColumns(['action'])
                            ->make(true);
        }
    }

    public function komisiAdmin(Request $request) {
        if (request()->ajax()) {
            $komisi = ServiceCommission::latest()->get();

            return DataTables::of($komisi)
                        ->editColumn('vendor_id', function($row) {
                            return $row->vendor->name;
                        })
                        ->addColumn('commission', function($row) {
                            if ($row->commission_type_id == ServiceCommission::FIXED) {
                                return $this->currencyView($row->commission_value);
                            } else {
                                return $this->percentageView($row->commission_value);
                            }
                        })
                        ->addColumn('action', function($row) {
                            $btn = '<div class="form-inline row">
                                        <center>
                                            <button id="editCommission" data-id="'.$row->id.'" class="btn btn-warning btn-sm" title="Edit"><i class="fa fa-pencil-alt"></i></button>
                                            <button id="deleteCommission" data-id="'.$row->id.'" class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-times"></i></button>
                                        </center>
                                    </div>';

                            return $btn;
                        })
                        ->rawColumns(['action'])
                        ->make(true);
        }
    }

    public function payoutAdmin(Request $request) {
        if ($request->ajax()) {
            $payout = Payout::latest()->get();

            return DataTables::of($payout)
                        ->addIndexColumn()
                        ->editColumn('minimum_payout', function($row) {
                            return $this->currencyView($row->minimum_payout);
                        })
                        ->addColumn('action', function($row) {
                            $btn = '<div class="form-inline row">
                                        <center>
                                            <button id="editPayout" data-id="'.$row->id.'" class="btn btn-warning btn-sm">Edit</button>
                                        </center>
                                    </div>';

                            return $btn;
                        })
                        ->rawColumns(['action'])
                        ->make(true);
        }
    }

    ################################################ USER ####################################################
    public function vendorAffiliate(Request $request) {
        if ($request->ajax()) {
            $vendorActive = Vendor::where('active', true)->get();
            
            foreach ($vendorActive as $key => $value) {
                $vendorId[] = $value->id;
            }

            $serviceCommission = ServiceCommission::whereIn('vendor_id', $vendorId)->whereNotNull('service_link')->get();
            $userId = Auth::id();

            $media = Media::get()->keyBy('name');

            $url = Url::to('/').'/share/';

            return DataTables::of($serviceCommission)
                        ->addIndexColumn()
                        ->editColumn('vendor_id', function($row) {
                            return $row->vendor->name;
                        })
                        ->editColumn('commission', function($row) {
                            if ($row->commission_type_id == ServiceCommission::FIXED) {
                                $stringCommission = "You WIll Get : ".$this->currencyView($row->commission_value)." Per Sale";
                            } else {
                                $stringCommission = "You WIll Get : ".$this->percentageView($row->commission_value)." Per Sale"; 
                            }

                            if (!is_null($row->max_commission)) {
                                $stringCommission .= "<br><br>Max Commission Per Sale : ".$this->currencyView($row->max_commission);
                            }

                            return $stringCommission;
                        })
                        ->addColumn('link', function($row) use ($userId, $url, $media){
                            $link_website = $url.$row->id.'.'.$userId.'.'.$media['Website']->id;
                            $imagePath = url('/uploads').'/'.$row->vendor->name.'/'.$row->img_upload;
                            $linkEmbed = '<a href='.$link_website.'>'.'<img src="'.$imagePath.'" class="img-fluid" style="width: 200px; height: 200px; border: 1px solid">'."<br>".$row->title.'</a>';
                            
                            $marketingText = $row->marketing_text;

                            $dataLink = '<span>Link Affiliate</span>
                                        <div class="form-group">
                                            <div class="controls">
                                                <div class="input-group">
                                                    <input type="text" id="link'.$row->id.'1" class="form-control form-control-sm bg-white" value="'.$link_website.'" readonly>
                                                    <span class="input-group-append">
                                                        <button class="btn btn-light" onclick="copyLink('.$row->id.'1)" title="Copy Link"><i class="fa fa-clone"></i></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <label for="">Copy This Script Into Your Website</label>
                                        <div class="form-group">
                                            <div class="controls">
                                                <div class="input-group">
                                                    <textarea id="link'.$row->id.'2" class="form-control form-control-sm bg-white" readonly>'.$linkEmbed.'</textarea>
                                                    <span class="input-group-append">
                                                        <button class="btn btn-light" onclick="copyLink('.$row->id.'2)" title="Copy Link"><i class="fa fa-clone"></i></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <label for="" class="col-form-label">Marketing Text</label>
                                        <div class="form-group">
                                            <div class="controls">
                                                <div class="input-group">
                                                    <textarea type="text" id="link'.$row->id.'3" class="form-control form-control-sm bg-white" rows="5" readonly>'.$marketingText.'</textarea>
                                                    <span class="input-group-append">
                                                        <button class="btn btn-light" onclick="copyLink('.$row->id.'3)" title="Copy Link"><i class="fa fa-clone"></i></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>';
                            
                            $dataImage = is_null($row->img_upload) ? '' : '<div class="form-group">
                                                                                <div class="controls">
                                                                                    <div class="input-group">
                                                                                        <img src="/uploads/'.$row->vendor->name.'/'.$row->img_upload.'" class="img-fluid" style="width: 200px; height: 200px; border: 1px solid">
                                                                                    </div><br>
                                                                                    <a href="'.route("service.downloadImage", $row->id).'" class="btn btn-outline-danger btn-sm">Download Image</a>
                                                                                </div>
                                                                            </div>';

                            return $dataLink.$dataImage;
                        })
                        ->addColumn('action', function($row) use ($userId, $url, $media) {
                            $marketing_text = "'".$row->marketing_text."'";
                            $link['facebook'] = $url.$row->id.'.'.$userId.'.'.$media['Facebook']->id." ".$marketing_text;
                            $link['email'] = $row->marketing_text." ".$url.$row->id.'.'.$userId.'.'.$media['Email']->id;
                            $link['telegram'] = $row->marketing_text." ".$url.$row->id.'.'.$userId.'.'.$media['Telegram']->id;
                            $link['whatsapp'] = $row->marketing_text." ".$url.$row->id.'.'.$userId.'.'.$media['Whatsapp']->id;
                            $link['linkedin'] = $row->marketing_text." ".$url.$row->id.'.'.$userId.'.'.$media['LinkedIn']->id;
                            $link['twitter'] = $row->marketing_text." ".$url.$row->id.'.'.$userId.'.'.$media['Twitter']->id;
                            
                            $btn = '
                                <a href="https://www.facebook.com/sharer/sharer.php?u='.$link['facebook'].'&text='.$marketing_text.'" target="_blank" class="btn btn-sm btn-circle btn-facebook" title="Share On Facebook"><i class="fab fa-facebook-f"></i></a>
                                <a href="mailto:?subject=[SUBJECT]&body='.$link['email'].'" target="_blank" class="btn btn-sm btn-circle btn-danger" title="Share On Email"><i class="fa fa-envelope"></i></a>
                                <a href="https://t.me/share/url?url='.$link['telegram'].'" target="_blank" class="btn btn-sm btn-circle btn-info" title="Share On Telegram"><i class="fab fa-telegram"></i></a>
                                <a href="https://api.whatsapp.com/send?text='.$link['whatsapp'].'" data-action="share/whatsapp/share" target="_blank" class="btn btn-sm btn-circle btn-success" title="Share On Whatsapp"><i class="fab fa-whatsapp"></i></a>
                                <a href="https://www.linkedin.com/shareArticle?mini=true&url='.$link['linkedin'].'" target="_blank" class="btn btn-sm btn-circle btn-linkedin" title="Share On LinkedIn"><i class="fab fa-linkedin"></i></a>
                                <a href="https://twitter.com/share?url='.$link['twitter'].'" target="_blank" class="btn btn-sm btn-circle btn-twitter" title="Share On Twitter"><i class="fab fa-twitter"></i></a>
                                ';
                                
                            return $btn;
                        })
                        ->rawColumns(['link', 'commission', 'action'])
                        ->make(true);
        }
    }

    public function transactionAffiliate(Request $request) {
        if (request()->ajax()) {
            $transaction = Transaction::leftJoin('leads', 'leads.id', '=', 'transactions.lead_id')->where('leads.user_id', Auth::id());
            if ($request->status !== 'all') {
                if ($request->status == Lead::ON_PROCESS) {
                    $transaction->where('leads.status', Lead::ON_PROCESS);
                } elseif ($request->status == Lead::SUCCESS) {
                    $transaction->where('leads.status', Lead::SUCCESS);
                } else {
                    $transaction->where('leads.status', Lead::CANCELED);
                }
            }

            if (!empty($request->dateStart && !empty($request->dateEnd))) {
                $transaction->whereBetween('transaction_date', [$request->dateStart, $request->dateEnd]);
            }

            return DataTables::of($transaction->latest('transactions.created_at')->get())
                        ->addIndexColumn()
                        ->addColumn('customer_name', function($row) {
                            return $row->lead->customer_name;
                        })
                        ->addColumn('vendor_name', function($row) {
                            return $row->lead->vendor->name;
                        })
                        ->addColumn('signup_date', function($row) {
                            return $this->convertDateView($row->lead->date);
                        })
                        ->addColumn('product_service', function($row) {
                            return $row->service_commission->title;
                        })
                        ->editColumn('transaction_date', function($row) {
                            return $this->convertDateView($row->transaction_date);
                        })
                        ->editColumn('amount', function($row) {
                            return $this->currencyView($row->amount);
                        })
                        ->editColumn('commission', function($row) {
                            return $this->currencyView($row->commission);
                        })
                        ->editColumn('status', function($row){
                            if ($row->cancel == 1) {
                                $statusLead = '<span class="badge badge-danger" data-toggle="tooltip" data-placement="top" title="" data-original-title="'.$row->cancel_reason.'">CANCELED</span>';
                            } else {
                                if ($row->lead->status == Lead::ON_PROCESS) {
                                    $statusLead = '<span class="badge badge-primary">ON PROCESS</span>';
                                } else if ($row->lead->status == Lead::SUCCESS) {
                                    $statusLead = '<div class="badge badge-success">SUCCESS</div>';
                                } else {
                                    $statusLead = '<span class="badge badge-danger">CANCELED</span>';
                                }
                            }

                            return $statusLead;
                        })
                        ->rawColumns(['status'])
                        ->make(true);
        }
    }

    public function leadAffiliate(Request $request) {
        if (request()->ajax()) {
            $lead = Lead::where('user_id', Auth::id())->whereNotNull('email')->latest();
            if ($request->status !== 'all') {
                if ($request->status == Lead::ON_PROCESS) {
                    $lead->where('status', Lead::ON_PROCESS);
                } elseif ($request->status == Lead::SUCCESS) {
                    $lead->where('status', Lead::SUCCESS);
                } else {
                    $lead->where('status', Lead::CANCELED);
                }
            }

            if (!empty($request->dateStart && !empty($request->dateEnd))) {
                $lead->whereBetween('date', [$request->dateStart, $request->dateEnd]);
            }

            return DataTables::of($lead->get())
                        ->addIndexColumn()
                        ->editColumn('vendor_id', function($row) {
                            return $row->vendor->name;
                        })
                        ->editColumn('email', function($row) {
                            return $this->hideEmail($row->email);
                        })
                        ->editColumn('no_telepon', function($row) {
                            return $this->hidePhoneNumber($row->no_telepon);
                        })
                        ->editColumn('date', function($row) {
                            return $this->convertDateView($row->date);
                        })
                        ->editColumn('status', function($row){
                            if ($row->status == Lead::ON_PROCESS) {
                                $statusLead = '<span class="badge badge-primary">ON PROCESS</span>';
                            } else if ($row->status == Lead::SUCCESS) {
                                $statusLead = '<div class="badge badge-success">SUCCESS</div>';
                            } else {
                                $statusLead = '<span class="badge badge-danger">CANCELED</span>';
                            }

                            return $statusLead;
                        })
                        ->rawColumns(['status'])
                        ->make(true);
        }
    }
    
    public function walletAffiliate(Request $request) {
        if ($request->ajax()) {
            $withdrawal = Withdrawal::where('user_id', Auth::id())->latest()->get();

            return DataTables::of($withdrawal)
                        ->addIndexColumn()
                        ->editColumn('date', function($row) {
                            return $this->convertDateView($row->date);
                        })
                        ->editColumn('total', function($row) {
                            return $this->currencyView($row->total);
                        })
                        ->editColumn('status', function($row) {
                            if ($row->withdrawal_status_id == WithdrawalStatus::REQUEST) {
                                return '<span class="badge badge-primary text-uppercase font-weight-bold text-xl">Requested</span>';
                            } else {
                                return '<span class="badge badge-success text-uppercase font-weight-bold text-xl">Approved</span>';
                            }
                        })
                        ->addColumn('action', function($row) {
                            $btn = '<div class="form-inline row">
                                        <center>
                                            <button id="detailWithdrawal" data-id="'.$row->id.'" class="btn btn-info btn-sm" title="Edit">Detail</button>
                                        </center>
                                    </div>';

                            return $btn;
                        })
                        ->rawColumns(['status','action'])
                        ->make(true);
        }
    }

    public function clickAffiliate(Request $request) {
        if ($request->ajax()) {
            $click = Click::where('user_id', Auth::id())->orderBy('click', 'DESC');

            return DataTables::of($click)
                        ->addIndexColumn()
                        ->editColumn('vendor_id', function($row) {
                            return $row->vendor->name;
                        })
                        ->editColumn('media_id', function($row) {
                            return $row->media->name;
                        })
                        ->make(true);
        }
    }
}
