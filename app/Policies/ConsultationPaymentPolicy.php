<?php

namespace App\Policies;

use App\Models\ConsultationPayment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConsultationPaymentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ConsultationPayment $consultationPayment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ConsultationPayment $consultationPayment): bool
    {
        return true;
    }

    public function delete(User $user, ConsultationPayment $consultationPayment): bool
    {
        return false;
    }

    public function restore(User $user, ConsultationPayment $consultationPayment): bool
    {
        return false;
    }

    public function forceDelete(User $user, ConsultationPayment $consultationPayment): bool
    {
        return false;
    }
}
