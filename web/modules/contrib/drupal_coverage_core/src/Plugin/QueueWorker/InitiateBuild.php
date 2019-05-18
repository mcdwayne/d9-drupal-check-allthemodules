<?php
/**
 * @file
 * Contains
 */

namespace Drupal\drupal_coverage_core\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Initiates a build for analysing the code coverage.
 *
 * @QueueWorker(
 *   id = "drupal_coverage_core_build",
 *   title = @Translation("Analyse Code Coverage"),
 *   cron = {"time" = 60}
 * )
 */
class InitiateBuild extends QueueWorkerBase
{

    /**
     * {@inheritdoc}
     */
    public function processItem($data) {

    }

}