<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Desa extends Model
{
    protected $guarded = [];

    public function user_info()
    {
        return $this->hasMany(UserInfo::class);
    }

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function komoditas()
    {
        return $this->hasMany(Komoditas::class);
    }

    public function user_gudang()
    {
        return $this->hasOne(UserGudang::class);
    }
}
