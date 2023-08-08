<?php  declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Manager;

/**
 * Interface for completing the verification process
 */
interface Completer
{
    /**
     * Completes the verification process by checking the correctness of the provided OTP for the given phone number
     *
     * @param string  $phone
     * @param integer $otp
     * @return $this
     */
    public function complete(string $phone, int $otp): self;
}
