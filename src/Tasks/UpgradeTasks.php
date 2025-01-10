<?php

namespace NSWDPC\Authentication\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

class UpgradeTasks extends BuildTask
{
    /**
     * @inheritdoc
     */
    protected $title = 'Auth Upgrade Tasks';

    /**
     * @inheritdoc
     */
    protected $description = 'Handle upgrade changes to support deprecations / new features';

    /**
     * @inheritdoc
     */
    protected $enabled = false;

    /**
     * @inheritdoc
     */
    private static string $segment = 'AuthUpgradeTasks';

    private bool $commit = false;

    /**
     * @inheritdoc
     */
    public function run($request)
    {
        $this->commit = $request->getVar('commit') == 1;
        $upgrade = $request->getVar('upgrade');
        $method = "task{$upgrade}";
        if (method_exists($this, $method)) {
            $this->{$method}($request);
        } else {
            DB::alteration_message("The upgrade does not exist. Provide an upgrade=name param", "error");
        }
    }

    private function taskRemoveIsPendingField()
    {
        if ($this->commit) {
            DB::query('ALTER TABLE "Member" DROP COLUMN "IsPending"');
            DB::alteration_message("Dropped column 'IsPending'", "change");
        } else {
            DB::alteration_message("Would drop column 'IsPending'", "info");
        }
    }
}
