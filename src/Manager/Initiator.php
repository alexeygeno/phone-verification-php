<?php  declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Manager;

use AlexGeno\PhoneVerification\Sender\I as ISender;

/**
 * The initiation process interface
 * @package AlexGeno\PhoneVerification\Manager
 */
interface Initiator
{
    /**
     * Initiates the verification process by sending an otp to a phone.
     * Returns sender's API response
     *
     * @param string $phone
     * @return mixed
     */
    public function initiate(string $phone);

    /**
     * Sets sender
     * Must be called before the initiate method
     *
     * @param ISender $sender
     * @return $this
     */
    public function sender(ISender $sender): self;
}