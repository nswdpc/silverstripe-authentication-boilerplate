<?php

namespace NSWDPC\Passwords;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Security\Member;

/**
 * Checks a password for sequential characters
 * @author James <james.ellis@dpc.nsw.gov.au>
 */
abstract class AbstractPasswordRule {

    use Configurable;

    abstract public function check($password, Member $member = null);

    /**
     * By default, allow rule checks can run
     * @return boolean
     */
    public function canRun() {
        return true;
    }

}
