<?php  declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Manager;

/**
 * The completion process interface
 * @package AlexGeno\PhoneVerification\Manager
 */
interface Completer
{
    /**
     * Completes the verification process by checking if the otp is correct for the phone
     *
     * @param string  $phone
     * @param integer $otp
     * @return $this
     */
    public function complete(string $phone, int $otp): self;
}
