<?php

namespace Drupal\vde_drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\DrushCommands;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\views\Views;
use Drupal\views\ViewExecutable;
use Drush\Utils\StringUtils;
use Drupal\vde_drush\FormatManipulatorLoader;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;

/**
 * Class VdeDrushCommands.
 *
 * This is the Drush 9 command for exporting views.
 *
 * @package Drupal\vde_drush\Commands.
 */
class VdeDrushCommands extends DrushCommands {

  /**
   * The account switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * Format manipulator service.
   *
   * @var \Drupal\vde_drush\FormatManipulatorLoader
   */
  protected $formatManipulatorLoader;

  /**
   * Queue Factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Queue manager.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * VdeDrushCommands constructor.
   *
   * @param \Drupal\Core\Session\AccountSwitcherInterface $account_switcher
   *   The account switching service.
   * @param \Drupal\vde_drush\FormatManipulatorLoader $formatManipulatorLoader
   *   Format manipulator loader service.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue service.
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queue_manager
   *   The queue_manager service.
   */
  public function __construct(
    AccountSwitcherInterface $account_switcher,
    FormatManipulatorLoader $formatManipulatorLoader,
    QueueFactory $queueFactory,
    QueueWorkerManagerInterface $queue_manager
  ) {
    $this->accountSwitcher = $account_switcher;
    $this->formatManipulatorLoader = $formatManipulatorLoader;
    $this->queueFactory = $queueFactory->get('vde_drush_queue');
    $this->queueManager = $queue_manager;
  }

  /**
   * Implements views_data_export command arguments validation.
   *
   * @hook validate vde_drush:views-data-export
   */
  public function viewsDataExportValidate(CommandData $commandData) {
    // Extract argument values.
    $input = $commandData->input();
    $view_name = $input->getArgument('view_name');
    $display_id = $input->getArgument('display_id');
    $output_file = $input->getArgument('output_file');
    $options = $commandData->options();

    // Verify view existence.
    $view = Views::getView($view_name);
    if (!is_object($view)) {
      throw new \Exception(dt('The view !view does not exist.', ['!view' => $view_name]));
    }

    // Verify existence of the display.
    if (empty($view->setDisplay($display_id))) {
      throw new \Exception(dt('The view !view does not have the !display display.', [
        '!view' => $view_name,
        '!display' => $display_id,
      ]));
    }

    // Verify the display type.
    $view_display = $view->getDisplay();
    if ($view_display->getPluginId() !== 'data_export') {
      throw new \Exception(dt('Incorrect display_id provided, expected a views data export display, found !display instead.', [
        '!display' => $view_display->getPluginId(),
      ]));
    }

    // Handle relative paths.
    $output_path = [];
    preg_match('/(.*\/)*([^\/]*)$/', $output_file, $output_path);

    // Attempt to resolve the directory.
    $output_path[1] = realpath($output_path[1]);
    if (empty($output_path[1])) {
      throw new \Exception('No such directory.');
    }

    // Validate filename.
    if (empty($output_path[2])) {
      // Set default filename.
      $output_path[2] = implode('_', [
        'views_export',
        $view_name,
        $display_id,
      ]);

      $this->logger()->notice(dt('No file name has been provided, using "!default" instead.', [
        '!default' => $output_path[2],
      ]));
    }

    // Validate filename extension.
    if (strpos($output_path[2], '.') === FALSE) {
      // Extract current style format.
      $export_format = reset($view_display->getOption('style')['options']['formats']);

      // Apply output file extension.
      $output_path[2] = StringUtils::interpolate('!filename.!format', [
        '!filename' => $output_path[2],
        '!format' => $export_format,
      ]);

      $this->logger()->notice(dt('No file format has been provided, using "!format" instead.', [
        '!format' => $export_format,
      ]));
    }

    // Update the output file path.
    $input->setArgument('output_file', implode('/', [
      $output_path[1], $output_path[2],
    ]));
  }

  /**
   * Executes views_data_export display of a view and writes the output to file.
   *
   * @param string $view_name
   *   The name of the view.
   * @param string $display_id
   *   The id of the views_data_export display to execute on the view.
   * @param string $output_file
   *   The file to write the results to - will be overwritten if it already
   *   exists.
   *
   * @usage vde_drush:views-data-export my_view_name views_data_export_display_id
   *   output.csv Export my_view_name:views_data_export_display_id and write the
   *   output to output.csv in the current directory.
   *
   * @command vde_drush:views-data-export
   * @aliases vde
   *
   * @throws \Exception
   *   If view does not exist.
   */
  public function viewsDataExport($view_name, $display_id, $output_file) {
    $view = Views::getView($view_name);
    $view->setDisplay($display_id);

    // Switch to root user (--user option was removed from drush 9).
    $this->accountSwitcher->switchTo(new UserSession(['uid' => 1]));

    if ($this->isBatched($view)) {
      $this->performBatchExport($view, $output_file);
      $this->logger()->success(dt(
        'Data export saved to !output_file',
        ['!output_file' => $output_file]
      ));
    }
    else {
      @ob_end_clean();
      ob_start();
      // This export isn't batched.
      $res = $view->executeDisplay($display_id);
      // Get the results, and clean the output buffer.
      echo $res["#markup"]->__toString();

      // Save the results to file.
      // Copy file over.
      if (file_put_contents($output_file, ob_get_clean())) {
        $this->logger()
          ->success(dt('Data export saved to !output_file', [
            '!output_file' => $output_file,
          ]));
      }
      else {
        throw new \Exception(dt('The file could not be copied to the selected destination'));
      }
    }

    // Switch account back.
    $this->accountSwitcher->switchBack();
  }

  /**
   * Determine if this view should run as a batch or not.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View to check if it's batched.
   *
   * @return bool
   *   TRUE if view is batched, FALSE otherwise.
   */
  public function isBatched(ViewExecutable $view) {
    return ($view->display_handler->getOption('export_method') == 'batch')
      && empty($view->live_preview);
  }

  /**
   * Performs batch exporting routine.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View the data of which must be exported.
   * @param string $output_file
   *   Output file path.
   *
   * @throws \Drupal\Core\Queue\SuspendQueueException
   * @throws \Exception
   */
  private function performBatchExport(ViewExecutable $view, $output_file) {
    $handler = $view->getDisplay();
    $view->preExecute();
    $view->build();

    $items_per_batch = $handler->getOption('export_batch_size');
    $export_limit = $handler->getOption('export_limit');
    $export_count = 0;

    // Perform total rows count query separately from the view.
    $count_query = clone $view->query;
    $count_query = $count_query->query(TRUE)->execute();
    $count_query->allowRowCount = TRUE;
    $export_items = $count_query->rowCount();

    // Apply export limit.
    if (!empty($export_limit)) {
      $export_items = min($export_items, $export_limit);
    }

    $output_format = reset($view->getStyle()->options['formats']);
    $format_manipulator = $this->formatManipulatorLoader->createInstance($output_format);

    // Disable both views and entity storage cache before executing the
    // rendering procedures.
    $this->entityCacheDisable($view);

    $queue = $this->queueFactory;

    // We need to multiply on $items_per_batch and substract
    // $items_per_batch to get the number close to $export_items.
    // Because the last item in $queue->numberOfItems() could be
    // lesser than $items_per_batch and by acting this way we ensure
    // that we have the exact number of operations and don't have
    // any duplicates. For sure there could be duplicates if we
    // repopulate queue, but it won't do any harm to an export
    // or at least it shouldn't.
    $total_queue_items = $queue->numberOfItems() * $items_per_batch - $items_per_batch;

    // In case if queue was filled multiple times with the same
    // items we must delete queue and start a new one.
    if ($total_queue_items > $export_items) {
      $queue->deleteQueue();
    }

    // If there are some items in queue ask if to create new queue or
    // to keep this one.
    if ($queue->numberOfItems() > 0
      && $this->confirm(dt(
        'There are @items items in queue. Do you want to create new queue(y) or use existing one(n)?',
        ['@items' => $queue->numberOfItems()]
      ))) {
      $queue->deleteQueue();
    }

    $proceed = FALSE;
    // If there are some items in queue ask if to populate queue or
    // to execute existing.
    if ($queue->numberOfItems() > 0) {
      $proceed = $this->confirm(dt(
        'Do you want to execute them(y) or proceed to populating queue(n)?'
      ));
    }

    // If there is no items in queue or previous answer was to populate
    // queue(n) we must populate queue.
    if (!$proceed) {
      $this->logger()->info(dt('Adding data to queue...'));

      // Perform per chunk view rendering.
      while ($export_count < $export_items) {
        $queue->createItem([
          'view' => $view,
          'export_count' => $export_count,
          'items_per_batch' => $items_per_batch,
          'format_manipulator' => $format_manipulator,
          'output_file' => $output_file,
          'export_items' => $export_items,
        ]);

        // Shift rendering start position.
        $export_count += $items_per_batch;
      }
    }

    $this->logger()->info(dt('Queue filled. Starting executing items'));
    $queue_worker = $this->queueManager->createInstance('vde_drush_queue');

    while ($item = $queue->claimItem()) {
      if ($item->data['export_count'] != ($export_items - $item->data['items_per_batch'])) {
        try {
          $queue_worker->processItem($item->data);

          $this->logger()->info(dt('Exporting records !from to !to.', [
            '!from' => $item->data['export_count'],
            '!to' => $item->data['export_count'] + $item->data['items_per_batch'],
          ]));

          $queue->deleteItem($item);
        }
        catch (SuspendQueueException $e) {
          $queue->releaseItem($item);
          break;
        }
        catch (\Exception $e) {
          $this->logger()->error(dt('An error occured. !error', [
            '!error' => $e,
          ]));
        }
      }
    }

    $this->logger()->info(dt('Executed'));
    $queue->deleteQueue();
  }

  /**
   * Disables entity related views cache.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   A view object the cache for which must be disabled.
   */
  private function entityCacheDisable(ViewExecutable &$view) {
    $entity_types = $view->query->getEntityTableInfo();
    $entity_type_manager = \Drupal::entityTypeManager();

    foreach ($entity_types as $entity_type => $entity_description) {
      $entity_type_definition = $entity_type_manager->getDefinition($entity_type);

      // Set the static cache flag to false.
      $entity_type_definition->set('static_cache', FALSE);
    }

    // Disable views cache plugin.
    $handler = $view->getDisplay();
    $handler_cache_none = [
      'type' => 'none',
      'options' => [],
    ];

    $handler->setOption('cache', $handler_cache_none);
    $handler->options['cache'] = $handler_cache_none;
  }

}
