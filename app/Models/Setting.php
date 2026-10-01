<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    public static function get(string $key, $default = null)
    {
        $v = static::find($key)?->value;
        if ($v === null) { return $default; }
        return str_ends_with($key, 'secret') ? \Illuminate\Support\Facades\Crypt::decryptString($v) : $v;
    }

    public static function put(string $key, $value): void
    {
        if ($value !== null && $value !== '' && str_ends_with($key, 'secret')) { $value = \Illuminate\Support\Facades\Crypt::encryptString($value); }
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
