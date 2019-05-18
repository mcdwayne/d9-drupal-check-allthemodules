<?php

/**
 * @file
 * Contains \Drupal\direct_queue\Command\RunCommand.
 */

namespace Drupal\direct_queue\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Core\Queue\SuspendQueueException;

/**
 * Class RunCommand.
 *
 * @package Drupal\direct_queue
 */
class RunCommand extends Command {
  use ContainerAwareCommandTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    // Register our command.
    $this
      ->setName('direct_queue:run')
      ->setDescription($this->trans('commands.direct_queue_run.description'))
      ->addArgument('item_id', InputArgument::REQUIRED, $this->trans('commands.direct_queue_run.arguments.item_id'))
      ->addArgument('expire', InputArgument::REQUIRED, $this->trans('commands.direct_queue_run.arguments.expire'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Get some basic services.
    $connection = \Drupal::getContainer()->get('database');
    $queue_manager = \Drupal::getContainer()->get('plugin.manager.queue_worker');

    // DrupalStyle is a helper to generate console output.
    $io = new DrupalStyle($input, $output);

    // Try to fetch our item from the queue.
    // Make sure the expire value matches ours.
    $item = $connection->queryRange('SELECT data, created, item_id, name FROM {queue} q WHERE item_id = :item_id AND expire = :expire ORDER BY created, item_id ASC', 0, 1, array(':item_id' => $input->getArgument('item_id'), ':expire' => $input->getArgument('expire')))->fetchObject();

    // Check if we were able to fetch our item.
    if (!$item) {
      $e = new \Exception("Could not load queue item");
      watchdog_exception('direct_queue', $e);
      $io->error($e->getMessage());
      return;
    }

    // Unserialize the data from the database.
    $item->data = unserialize($item->data);

    // Get the queue for this queue item.
    $queue = \Drupal::queue($item->name);

    try {

      // Try to start a queue worker and process the queue item.
      $queue_worker = $queue_manager->createInstance($item->name);
      $queue_worker->processItem($item->data);

      // Remove the item from the queue.
      $queue->deleteItem($item);

      // Tell the backend we had success.
      $io->info('Success');
    }
    catch (SuspendQueueException $e) {
      // If the worker indicates there is a problem with the whole queue,
      // release the item and skip to the next queue.
      $queue->releaseItem($item);
      watchdog_exception('direct_queue', $e);
      $io->error($e->getMessage());
    }
    catch (\Exception $e) {
      // In case of any other kind of exception, log it and leave the item
      // in the queue to be processed again later.
      // Drupal Cron will clean up when expire < time().
      watchdog_exception('direct_queue', $e);
      $io->error($e->getMessage());
    }
  }
}
