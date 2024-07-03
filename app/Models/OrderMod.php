<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class OrderMod extends Model
{
    use HasFactory;

    protected $table = 'mrd_order';
    protected $primaryKey = 'mrd_order_id';
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'mrd_order_user_id',
        'mrd_order_menu_id',
        'mrd_order_quantity',
        'mrd_order_feedback',
        'mrd_order_status',
        'mrd_order_date',
        'mrd_order_date_added'
    ];

    protected $casts = [
        'mrd_order_date_added' => 'datetime',
    ];
}
