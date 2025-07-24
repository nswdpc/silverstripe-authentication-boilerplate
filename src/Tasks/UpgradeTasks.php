<?php

namespace NSWDPC\Authentication\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class UpgradeTasks extends BuildTask
{
    /**
     * @inheritdoc
     */
    protected string $title = 'Auth Upgrade Tasks';

    /**
     * @inheritdoc
     */
    protected static string $description = 'Handle upgrade changes to support deprecations / new features';

    /**
     * @inheritdoc
     */
    private static bool $is_enabled = false;

    /**
     * @inheritdoc
     */
    private static string $segment = 'AuthUpgradeTasks';

    private bool $commit = false;

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->commit = $input->getOption('commit') == '1';
        $upgrade = $input->getOption('upgrade');
        $method = "task{$upgrade}";
        if (method_exists($this, $method)) {
            $this->{$method}($input, $output);
        } else {
            DB::alteration_message("", "error");
            $output->writeln("The upgrade does not exist. Provide an upgrade=name param");
        }

        return Command::SUCCESS;
    }

    private function taskRemoveIsPendingField(PolyOutput $output)
    {
        if ($this->commit) {
            DB::query('ALTER TABLE "Member" DROP COLUMN "IsPending"');
            $output->writeln("Dropped column 'IsPending'");
        } else {
            $output->writeln("Would drop column 'IsPending'");
        }
    }
}
