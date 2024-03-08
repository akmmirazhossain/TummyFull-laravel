<?php

// app/Models/SettingMod.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingMod extends Model
{
    protected $table = 'mrd_setting'; // Specify the table name

    protected $primaryKey = 'mrd_setting_id'; // Specify the primary key if different

    protected $fillable = [
        'mrd_setting_time_limit_lunch',
        'mrd_setting_time_limit_dinner',
        'mrd_setting_delivery_time_lunch',
        'mrd_setting_delivery_time_dinner',
        // Add other fillable columns as needed
    ];

    // If you have timestamps (created_at and updated_at) in your table
    public $timestamps = false;
}
