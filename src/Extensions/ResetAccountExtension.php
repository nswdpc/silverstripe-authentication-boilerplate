<?php

namespace NSWDPC\Authentication\Extensions;

use NSWDPC\Authentication\Models\Notifier;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;

/**
 * Handle reset account notifications when that occurs
 * @extends \SilverStripe\Core\Extension<(\SilverStripe\MFA\Extension\AccountReset\SecurityExtension & static)>
 */
class ResetAccountExtension extends Extension
{
    public function handleAccountReset(Member $member)
    {
        try {

            // notify a group with a relevant permission
            $notifier = Notifier::create();
            return $notifier->sendMfaAccountResetNotification($member, 'completed');

        } catch (\Exception) {

        }

        return false;
    }
}
