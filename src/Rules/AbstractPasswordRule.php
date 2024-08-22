<?php

namespace NSWDPC\Authentication\Rules;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Security\Member;

/**
 * Checks a password for sequential characters
 */
abstract class AbstractPasswordRule {

    use Configurable;

    abstract public function check($password, Member $member = null): bool;

    /**
     * By default, allow rule checks can run
     * @return boolean
     */
    public function canRun() {
        return true;
    }

}
