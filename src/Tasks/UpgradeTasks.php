<?php

namespace NSWDPC\Authentication\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

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
    protected static string $commandName = 'AuthUpgradeTasks';

    private bool $commit = false;

    public function getOptions(): array
    {
        return [
            new InputOption('commit', null, InputOption::VALUE_OPTIONAL, 'Specify --commit=1 to make the changes'),
            new InputOption('upgrade', null, InputOption::VALUE_OPTIONAL, 'Specify the upgrade to take place')
        ];
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->commit = $input->getOption('commit') == '1';
        $upgrade = $input->getOption('upgrade');
        $method = "task{$upgrade}";
        if (method_exists($this, $method)) {
            $result = $this->{$method}($output);
            return $result ? Command::SUCCESS : Command::FAILURE;
        } else {
            $output->writeln("The upgrade does not exist. Provide an upgrade=name param");
            return Command::FAILURE;
        }

    }

    private function taskRemoveIsPendingField(PolyOutput $output): bool
    {
        if ($this->commit) {
            try {
                DB::query('ALTER TABLE "Member" DROP COLUMN "IsPending"');
                $output->writeln("Dropped column 'IsPending'");
                return true;
            } catch (\Exception $exception) {
                $output->writeln("Failed: this upgrade may have already taken place");
                return false;
            }
        } else {
            $output->writeln("Would drop column 'IsPending'");
            return true;
        }
    }
}
