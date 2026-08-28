<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ConsultationPayment extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logAll()
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'consultation_id',
        'amount',
        'payment_method',
        'paid',
        'paid_at',
        'comments',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
