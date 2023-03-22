<?php

namespace NSWDPC\Authentication;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

class UpgradeTasks extends BuildTask {

    protected $title = 'Auth Upgrade Tasks';
    protected $description = 'Handle upgrade changes to support deprecations / new features';

    /**
     * @config
     */
    private static $segment = 'AuthUpgradeTasks';

    private $commit = false;

    public function run($request) {
        $this->commit = $request->getVar('commit') == 1;
        $upgrade = $request->getVar('upgrade');
        $method = "task{$upgrade}";
        if(method_exists($this, $method)) {
            $this->{$method}($request);
        } else {
            DB::alteration_message("The upgrade does not exist. Provide an upgrade=name param", "error");
        }
    }

    private function taskRemoveIsPendingField($request) {
        if($this->commit) {
            DB::query("ALTER TABLE `Member` DROP COLUMN `IsPending`");
            DB::alteration_message("Dropped column `IsPending`", "change");
        } else {
            DB::alteration_message("Would drop column `IsPending`", "info");
        }
    }
}
