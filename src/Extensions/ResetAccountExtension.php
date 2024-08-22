<?php

namespace NSWDPC\Authentication\Extensions;

use NSWDPC\Authentication\Models\Notifier;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;

/**
 * Handle reset account notifications when that occurs
 */
class ResetAccountExtension extends Extension
{
    /**
     * @param Member $member
     */
    public function handleAccountReset(Member $member)
    {
        try {

            // notify a group with a relevant permission
            $notifier = Notifier::create();
            return $notifier->sendMfaAccountResetNotification( $member,  'completed' );

        } catch (\Exception $e) {

        }

        return false;
    }
}
