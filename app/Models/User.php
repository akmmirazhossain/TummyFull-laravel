<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'mrd_user_id';
    protected $table = 'mrd_user';

    // Add these lines to customize the email and password columns
    public function getAuthIdentifierName()
    {
        return 'mrd_user_id';
    }

    public function getAuthIdentifier()
    {
        return $this->mrd_user_id;
    }

    public function getAuthPassword()
    {
        return $this->mrd_user_password;
    }

    public function getEmailForPasswordReset()
    {
        return $this->mrd_user_email;
    }

    protected $fillable = [
        'mrd_user_id',
        'mrd_user_group_id',
        'mrd_user_first_name',
        'mrd_user_last_name',
        'mrd_user_image_file_name',
        'mrd_user_phone',
        'mrd_user_verified',
        'mrd_user_email',
        'mrd_user_password',
        'mrd_user_address',
        'mrd_user_payment_phone',
        'mrd_user_status',
        'mrd_user_mealbox',
        'mrd_user_total_meal',
        'mrd_user_delivery_ask',
        'mrd_user_meal_size',
        'mrd_user_credit',
        'mrd_user_credit_to_pay',
        'mrd_user_date_added',
    ];

    protected $hidden = [
        'mrd_user_password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'mrd_user_password' => 'hashed',
    ];
}
