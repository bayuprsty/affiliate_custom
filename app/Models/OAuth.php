<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OAuth extends Model
{
    protected $table = 'oauth_access_tokens';

    protected $fillable = ['revoked'];
}
