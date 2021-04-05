<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Media;
use App\Models\Vendor;

class Click extends Model
{
    protected $fillable = ['user_id', 'vendor_id', 'media_id', 'click'];

    public function vendor() {
        return $this->belongsTo(Vendor::class);
    }

    public function media() {
        return $this->belongsTo(Media::class);
    }
}
