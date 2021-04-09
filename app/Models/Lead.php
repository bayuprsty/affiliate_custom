<?php

namespace App\Models;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Lead extends Model
{
    const ON_PROCESS = 1;
    const SUCCESS = 2;
    const CANCELED = 3;

    protected $fillable = ['user_id', 'vendor_id', 'service_id', 'customer_name', 'email', 'no_telepon', 'date', 'status', 'ip_address'];
    
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function vendor() {
        return $this->belongsTo(Vendor::class);
    }

    public function service() {
        return $this->belongsTo(ServiceCommission::class);
    }

    public static function createNewLead($request, $lead) {
        $data = [
            'customer_name' => $request->customer_name,
            'email' => $request->email,
            'no_telepon' => $request->no_telepon,
            'date' => Carbon::NOW(),
            'status' => Lead::ON_PROCESS,
            'user_id' => $lead->user_id,
            'vendor_id' => $lead->vendor_id,
            'service_id' => $lead->service_id,
            'ip_address' => Controller::getIpAddress()
        ];

        DB::beginTransaction();

        try {
            $leadCreated = Lead::create($data);

            if ($leadCreated) {
                unset($data['user_id']);
                unset($data['vendor_id']);
                unset($data['service_id']);
                unset($data['ip_address']);

                return ApiResponse::send('Lead Created', $data, 200);
            }
        } catch (\Exception $e) {
            return ApiResponse::send($e->getMessage(), $data, 400);
        }
    }
}
