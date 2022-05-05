<?php

namespace NSWDPC\Authentication;

use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;

/**
 * Handle reset account notifications when that occurs
 * @author James
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
