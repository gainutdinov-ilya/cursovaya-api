<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Calendar;

class Notes extends Model
{
    use HasFactory;

    protected $table = 'notes';

    protected $fillable = [
        'client',
        'calendar',
        'visited'
    ];

    protected $casts = [
        'visited' => 'bool'
    ];

    function client()
    {
        return $this->belongsTo(User::class, 'client')->first();
    }

    function calendar()
    {
        return $this->belongsTo(Calendar::class, 'calendar')->first();
    }

    public $timestamps = false;
}
