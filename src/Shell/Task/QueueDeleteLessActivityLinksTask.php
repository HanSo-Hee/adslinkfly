<?php

namespace App\Shell\Task;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Queue\Shell\Task\QueueTask;
use Queue\Shell\Task\QueueTaskInterface;

class QueueDeleteLessActivityLinksTask extends QueueTask implements QueueTaskInterface
{
    /**
     * Timeout for this task in seconds, after which the task is reassigned to a new worker.
     *
     * @var int
     */
    public $timeout = 20;

    /**
     * Number of times a failed instance of this task should be restarted before giving up.
     *
     * @var int
     */
    public $retries = 0;

    /**
     * @param array $data The array passed to QueuedJobsTable::createJob()
     * @param int $jobId The id of the QueuedJob entity
     * @return void
     */
    public function run(array $data, $jobId)
    {
        /** @var \App\Model\Table\LinksTable $linksTable */
        $linksTable = TableRegistry::getTableLocator()->get('Links');

        $months = (int)get_option('delete_links_without_activity_months', 0);
        $views = (bool)get_option('delete_links_without_activity_views', 0);

        if ($months <= 0) {
            return;
        }

        $linksCount = $linksTable->find()
            ->select(['id', 'last_activity'])
            ->where([
                'last_activity <' => Time::now()->subMonths($months)->toDateTimeString(),
                'last_activity IS NOT NULL',
            ])
            ->count();

        $loopSize = 100;
        $loops = \ceil($linksCount / $loopSize);

        $startTime = \time();

        while ($loops > 0) {
            /** @var \App\Model\Entity\Link[] $links */
            $links = $linksTable->find()
                ->select(['id', 'last_activity'])
                ->where([
                    'last_activity <' => Time::now()->subMonths($months)->toDateTimeString(),
                    'last_activity IS NOT NULL',
                ])
                ->limit($loopSize);

            $ids = [];
            foreach ($links as $link) {
                $ids[] = $link->id;
            }

            if (count($ids)) {
                if ($views) {
                    $linksTable->Statistics->deleteAll(['link_id IN' => $ids]);
                }

                $linksTable->deleteAll(['id IN' => $ids]);

                \sleep(1);
            }

            $loops = $loops - 1;

            if (\time() - $startTime > 1 * 60 * 60) {
                /*
                $queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
                $queuedJobsTable->createJob('DeleteLessActivityLinks', []);
                */
                break;
            }
        }

//        $links = $linksTable->find()
//            ->select(['id', 'last_activity'])
//            ->where([
//                'last_activity <' => Time::now()->subMonths($months)->toDateTimeString(),
//                'last_activity IS NOT NULL',
//            ]);

//        foreach ($links as $link) {
//            if ($views) {
//                $linksTable->Statistics->deleteAll(['link_id' => $link->id]);
//            }
//
//            $linksTable->delete($link);
//        }

//        $connection = \Cake\Datasource\ConnectionManager::get('default');
//
//        foreach ($links as $link) {
//            try {
//                $connection->transactional(function ($connection) use ($link, $linksTable, $views) {
//                    if ($views) {
//                        $linksTable->Statistics->deleteAll(['link_id' => $link->id]);
//                    }
//
//                    $linksTable->delete($link);
//                });
//            } catch (\Exception $exception) {
//                \Cake\Log\Log::error($exception->getMessage());
//            }
//        }

        //throw new \Queue\Model\QueueException('Could not do that.');
    }

    protected function processLock()
    {
        $file_path = TMP . 'QueueDeleteLessActivityLinksTask.lock';

        if (!file_exists($file_path)) {
            if (!touch($file_path)) {
                //echo "Can't create the file\n";
                return false;
            }
        }

        $fp = fopen($file_path, "r+");
        return flock($fp, LOCK_EX | LOCK_NB, $wouldBlock);
    }

    protected function processRelease()
    {
        $file_path = TMP . 'QueueDeleteLessActivityLinksTask.lock';

        if (!file_exists($file_path)) {
            if (!touch($file_path)) {
                //echo "Can't create the file\n";
                return false;
            }
        }

        $fp = fopen($file_path, "r+");

        flock($fp, LOCK_UN); // release the lock

        fclose($fp);

        return true;
    }
}
