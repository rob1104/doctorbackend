<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiConversation extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'patient_id',
        'session_id',
        'messages',
        'context',
        'status',
    ];

    protected $casts = [
        'messages' => 'array',
        'context' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
