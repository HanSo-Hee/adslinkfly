<?php

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;

class DeletePlanLinksCommand extends Command
{
    /**
     * php bin/cake.php schedule
     * php bin/cake.php queue runworker -q
     * bin/cake schedule
     *
     * @param Arguments $args
     * @param ConsoleIo $io
     * @return int|void|null
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $plan_id = (int)$args->getArgumentAt(0);
        if (!$plan_id) {
            $io->abort('Add the plan id');
        }

        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = TableRegistry::getTableLocator()->get('Users');
        /** @var \App\Model\Table\LinksTable $linksTable */
        $linksTable = TableRegistry::getTableLocator()->get('Links');
        /** @var \App\Model\Table\StatisticsTable $statisticsTable */
        $statisticsTable = TableRegistry::getTableLocator()->get('Statistics');

        $users_ids = $usersTable->find('list')->select(['id'])->where(['plan_id' => $plan_id])->toArray();

        if (count($users_ids) < 1) {
            $io->abort('No users assigned to this plan');
        }

        $connection = ConnectionManager::get('default');

        foreach ($users_ids as $user_id) {
            try {
                $connection->transactional(function ($connection) use ($user_id, $linksTable, $statisticsTable) {
                    //$linksTable->query()->delete()->where(['user_id' => $user_id])->execute();
                    $connection->delete('links', ['user_id' => $user_id]);
                    //$statisticsTable->query()->delete()->where(['user_id' => $user_id])->execute();
                    $connection->delete('statistics', ['user_id' => $user_id]);
                });
            } catch (\Exception $exception) {
                $io->err($exception->getMessage());
            }
        }

        $io->out('Done');
    }
}
