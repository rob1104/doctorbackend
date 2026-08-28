<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PatientDocument extends Model
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
        'patient_id',
        'file_name',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
    ];

    protected $appends = ['url'];

    /**
     * Relación: el documento pertenece a un paciente.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Accessor: URL pública del archivo.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Determina si el archivo es una imagen.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Determina si el archivo es un video.
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Determina si el archivo es un documento (PDF, Word, Excel).
     */
    public function isDocument(): bool
    {
        return !$this->isImage() && !$this->isVideo();
    }
}
