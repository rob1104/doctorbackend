<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAgentLog extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'action',
        'tool',
        'input',
        'output',
        'status',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
    ];

    public function conversation()
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}
