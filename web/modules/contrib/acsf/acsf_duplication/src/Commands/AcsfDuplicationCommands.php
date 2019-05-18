<?php

namespace Drupal\acsf_duplication\Commands;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Drupal\acsf\AcsfException;
use Drupal\acsf\Event\AcsfDuplicationScrubCommentHandler;
use Drupal\acsf\Event\AcsfDuplicationScrubNodeHandler;
use Drupal\acsf\Event\AcsfDuplicationScrubUserHandler;
use Drupal\acsf\Event\AcsfEvent;
use Drush\Commands\DrushCommands;

/**
 * Provides drush commands necessary for site duplication.
 */
class AcsfDuplicationCommands extends DrushCommands {

  /**
   * Runs one iteration of the batch scrubbing process on the duplicated site.
   *
   * @command acsf-duplication-scrub-batch
   *
   * @option exact-copy Indicates that the duplicated site is intended to be an
   *   exact copy of the source site (i.e. retain all content, users, and
   *   required configuration).
   * @option avoid-oom The command should run just a single iteration of the
   *   batch process (the default is to loop until it runs out of memory). This
   *   is useful to keep the memory footprint low but you are expected to handle
   *   the looping externally.
   * @option batch The number of items to process each iteration (defaults to
   *   1000).
   * @option batch-comment The number of comments to delete each iteration
   *   (defaults to --batch).
   * @option batch-node The number of nodes to delete each iteration (defaults
   *   to --batch).
   * @option batch-user The number of users to delete each iteration (defaults
   *   to --batch).
   * @option retain-content Retain nodes and comments (defaults to
   *   --exact-copy).
   * @option retain-users Retain users (defaults to --exact-copy).
   *
   * @param string $site_name
   *   The new name of the duplicated site.
   * @param string $standard_domain
   *   The standard domain of the duplicated site.
   * @param array $options
   *   The command options supplied to the executed command.
   *
   * @throws \Drupal\acsf\AcsfException
   *   If the scrub process was not successful or the acsf module is not
   *   enabled.
   * @throws \InvalidArgumentException
   *   If one or more arguments are missing.
   */
  public function duplicationScrubBatch($site_name, $standard_domain, array $options = [
    'exact-copy' => NULL,
    'avoid-oom' => NULL,
    'batch' => NULL,
    'batch-comment' => NULL,
    'batch-node' => NULL,
    'batch-user' => NULL,
    'retain-content' => NULL,
    'retain-users' => NULL,
  ]) {
    if (empty($site_name)) {
      throw new \InvalidArgumentException(dt('You must provide the site name of the duplicated site as the first argument.'));
    }
    if (empty($standard_domain)) {
      throw new \InvalidArgumentException(dt('You must provide the standard domain of the duplicated site as the second argument.'));
    }

    if (!\Drupal::moduleHandler()->moduleExists('acsf')) {
      throw new AcsfException(dt('The ACSF module must be enabled.'));
    }

    $context = [
      'site_name' => $site_name,
      'standard_domain' => $standard_domain,
      'scrub_options' => [
        'avoid_oom' => $options['avoid-oom'],
      ],
    ];

    \Drupal::moduleHandler()->alter('acsf_duplication_scrub_context', $context, $options);
    ksort($context['scrub_options']);

    // Load and execute the site duplication scrub event handlers.
    $event = AcsfEvent::create('site_duplication_scrub', $context, $this->output());
    $event->run();

    // Return an error code if the process is incomplete.
    if (\Drupal::state()->get('acsf_duplication_scrub_status', NULL) !== 'complete') {
      throw new AcsfException(dt('The scrubbing of this site is incomplete. Please re-run the command to resume processing.'));
    }
    else {
      $this->output()->writeln(dt('The scrubbing of this site is complete.'));
    }
  }

  /**
   * Returns information about the progress of the batch scrubbing process.
   *
   * @command acsf-duplication-scrub-progress
   *
   * @throws \Drupal\acsf\AcsfException
   *   If the scrub process was not successful or the acsf module is not
   *   enabled.
   * @throws \InvalidArgumentException
   *   If one or more arguments are missing.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
   *   Description of remaining comments to scrub.
   */
  public function duplicationScrubProgress() {
    if (!\Drupal::moduleHandler()->moduleExists('acsf')) {
      throw new AcsfException(dt('The ACSF module must be enabled.'));
    }

    // Get the remaining count from the handlers. (Note this code highlights the
    // fact that the countRemaining() method does not return different results
    // depending on whether we pass in a proper event/context; if it does, we
    // will return bogus results.)
    $empty_event = AcsfEvent::create('site_duplication_scrub', [], $this->output());
    $data = [];

    $handler = new AcsfDuplicationScrubCommentHandler($empty_event);
    $data['comment_count'] = $handler->countRemaining();
    $handler = new AcsfDuplicationScrubNodeHandler($empty_event);
    $data['node_count'] = $handler->countRemaining();
    $handler = new AcsfDuplicationScrubUserHandler($empty_event);
    $data['user_count'] = $handler->countRemaining();

    \Drupal::moduleHandler()->alter('acsf_duplication_scrub_remaining_counts', $data);
    return new PropertyList($data);
  }

}
