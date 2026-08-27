<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgendaSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_time',
        'end_time',
        'slot_duration',
        'consultation_price',
        'working_days',
        'break_start_time',
        'break_end_time'
    ];

    protected $casts = [
        'working_days' => 'array',
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
                'break_end_time' => null
            ]);
        }
        return $settings;
    }
}
