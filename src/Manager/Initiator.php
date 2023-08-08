<?php  declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Manager;

use AlexGeno\PhoneVerification\Sender\I as ISender;

/**
 * Interface for initiating the verification process
 */
interface Initiator
{
    /**
     * Initiates the verification process by sending an OTP to a phone
     *
     * @param string $phone
     * @return $this
     */
    public function initiate(string $phone);

    /**
     * Sets the sender
     * Must be called before the initiate method
     *
     * @param ISender $sender
     * @return $this
     */
    public function sender(ISender $sender): self;
}
