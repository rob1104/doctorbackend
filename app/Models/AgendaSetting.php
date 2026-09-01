<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AgendaSetting extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logAll()
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'start_time',
        'end_time',
        'slot_duration',
        'consultation_price',
        'working_days',
        'break_start_time',
        'break_end_time',
        'require_otp'
    ];

    protected $casts = [
        'working_days' => 'array',
        'require_otp' => 'boolean',
    ];

    public static function getSettings()
    {
        $settings = self::first();
        if (!$settings) {
            $settings = self::create([
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'slot_duration' => 30,
                'consultation_price' => 1500,
                'working_days' => [1, 2, 3, 4, 5, 6], // Lunes a Sabado por defecto
                'break_start_time' => null,
                'break_end_time' => null,
                'require_otp' => true
            ]);
        }
        return $settings;
    }
}
