<?php
namespace App\Enums;

enum PaymentStatus : string {
    case REDIRECT_REQUIRED = 'redirect';
    case OTP_REQUIRED = 'otp';
    case AWS_REQUIRED = 'avs_noauth';
    case PIN_REQUIRED = 'pin';

    case CHARGED = 'charged';
    case FAILED = 'failed';
    case PENDING = 'pending';

    public function message(): string
    {
        return match ($this) {
            self::REDIRECT_REQUIRED => 'Please redirect to the below link to continue with payment',
            self::OTP_REQUIRED => 'Please provide OTP sent to your number to complete this transaction',
            self::AWS_REQUIRED => 'Please provide the below details to continue with the charge',
            self::PIN_REQUIRED => 'Please provide your Card Pin to proceed with transaction',
        };
    }

    public function is($value) : bool
    {
        $value = $value instanceof self ? $value : self::tryFrom($value);
        return $this == $value;
    }
}
