<?php
$latest = \App\Models\OtpVerification::latest('id')->first();
echo "OTP: " . $latest->code . "\n";
echo "Phone: " . $latest->phone . "\n";
echo "Expires At: " . $latest->expires_at . "\n";
echo "Current Time: " . \Carbon\Carbon::now() . "\n";
echo "Is Valid: " . ($latest->expires_at > \Carbon\Carbon::now() ? 'Yes' : 'No') . "\n";
