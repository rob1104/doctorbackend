<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Patient extends Model
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
        'first_name',
        'last_name',
        'phone',
        'email',
        'notes',
        'blood_type',
        'allergies',
        'skin_type',
        'family_history',
        'chronic_conditions',
        'is_patient',
        'gender',
        'date_of_birth',
        'address',
        'neighborhood',
        'zip_code',
        'city',
        'state',
        'country',
        'place_of_birth',
        'emergency_contact_name',
        'emergency_contact_phone',
        'marital_status',
        'occupation',
        'current_medications',
        'surgical_history',
        'skin_tendency',
        'sun_exposure_level',
        'previous_skin_conditions',
        'skincare_routine',
        'non_pathological_history',
        'gyneco_obstetric_history',
        'user_id'
    ];

    protected $casts = [
        'is_patient' => 'boolean',
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }

    /**
     * Get the documents/files associated with the patient.
     */
    public function documents()
    {
        return $this->hasMany(PatientDocument::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
