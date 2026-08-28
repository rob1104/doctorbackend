<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Consultation extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logAll()
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
    }

    protected $guarded = [];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class);
    }

    public function payments()
    {
        return $this->hasMany(ConsultationPayment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
