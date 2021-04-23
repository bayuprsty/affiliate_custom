<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Withdrawal;
use App\Models\WithdrawalPayout;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;

class WithdrawController extends Controller
{
    public function index () {
        $notification = Notification::where('admin_read', 0)->whereNotNull('withdraw_id')->update(['admin_read' => 1]);

        return view('admin.withdraw.index');
    }

    public function proses (Request $request) {
        if (request()->ajax()) {
            $withdrawalRequest = Withdrawal::findOrFail($request->id);
            $withdrawalPayout = $withdrawalRequest->total;

            //CUSTOM FORMAT TOTAL WITHDRAW
            $withdrawalRequest->total = 'Rp. '.number_format($withdrawalRequest->total, 0, ',', '.');
            
            $user = User::findOrfail($withdrawalRequest->user_id);

            return response()->json(['request' => $withdrawalRequest, 'user' => $user, 'payout' => $withdrawalPayout]);
        }
    }

    public function payout (Request $request) {
        if ($request->ajax()) {
            $this->validate($request, [
                'withdrawal_id' => 'required|integer',
                'payout' => 'required|integer'
            ]);
    
            if (WithdrawalPayout::create($request->all())) {
                $withdrawal = Withdrawal::where('id', $request->withdrawal_id)->first();
                $isUpdated = $withdrawal->update(['withdrawal_status_id' => 2, 'date_approve' => Carbon::NOW()]);
                
                if ($isUpdated) {
                    $dataEmail = [
                        'customer_name' => $withdrawal->transaction->lead->customer_name,
                        'email' => $this->hideEmail($withdrawal->transaction->lead->email),
                        'no_telepon' => $this->hidePhoneNumber($withdrawal->transaction->lead->no_telepon),
                        'transaction_date' => $this->convertDateView($withdrawal->transaction->transaction_date),
                        'amount' => $this->currencyView($withdrawal->transaction->amount),
                        'commission' => $this->currencyView($withdrawal->transaction->commission)
                    ];

                    $affiliateEmail = $withdrawal->transaction->lead->user->email;

                    Mail::send('admin.transaction._email', $dataEmail, function ($message) use ($affiliateEmail) {
                        $message->to($affiliateEmail)->subject('Affiliate Transaction Success');
                    });
                }
                
                return $this->sendResponse('Withdrawal Request Approved');
            }
        }
    }

    public function destroy ($id) {
        if ((WithdrawalPayout::findOrfail($id))) {
            # code...
        }
    }
}
