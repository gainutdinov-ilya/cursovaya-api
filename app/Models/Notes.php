<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notes extends Model
{
    use HasFactory;

    protected $table = 'notes';

    protected $fillable = [
      'client',
      'calendar'
    ];

    protected $casts = [
        'visited' => 'bool'
    ];

    function client(){
        $this->belongsTo(User::class,'client')->first();
    }

    public $timestamps = false;
}
