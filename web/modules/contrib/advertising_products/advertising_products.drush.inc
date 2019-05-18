<?php

/**
 * @file Contains code for custom drush commands for advertising products
 */

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\ConsoleOutput;

use Drush\Log\LogLevel;


/**
 * Implements hook_drush_command().
 */
function advertising_products_drush_command() {
  $items = [];
  $items['advertising-products-fill-queue'] = [
    'description' => 'Fills the advertising products queue.',
    'aliases' => ['apqfil'],
  ];
  $items['advertising-products-run-queue'] = [
    'description' => 'Works on the advertising products queue.',
    'arguments' => array(
      'queue' => 'Which queue to work',
    ),
    'options' => array(
      'time' => 'How long should we work the queue, default is 300 seconds.',
    ),
    'aliases' => ['apqrun'],
  ];
  $items['advertising-products-import'] = [
    'description' => 'Imports products from a CSV with format "product ID,provider_name"',
    'callback' => 'drush_advertising_products_import',
    'arguments' => [
      'filename' => 'the filename to read the data from. Format "product ID,provider_name"',
    ],
    'options' => array(
      'delay' => 'How many milliseconds should we wait after each import?',
    ),
    'aliases' => ['apimp'],
    'examples' => [
      'drush apimp data.csv --delay=10' => 'Import all products from data.csv and wait 10 Milliseconds in between each imports.'
    ],
  ];
  return $items;
}


/**
 * Implements drush_{module}_{command}.
 */
function drush_advertising_products_fill_queue() {
  module_load_include('module', 'advertising_products');
  advertising_products_run_cron();
}

/**
 * Implements drush_{module}_{command}.
 */
function drush_advertising_products_run_queue($queue_name) {
  $providers = \Drupal::service('plugin.manager.advertising_products.provider');
  $worker_manager = \Drupal::service('plugin.manager.queue_worker');

  $time_limit = drush_get_option('time') ?: 300;

  foreach ($providers->getDefinitions() as $provider) {
    if ($provider['id'] != $queue_name) {
      continue;
    }

    $connection = \Drupal\Core\Database\Database::getConnection('default');
    $queue = new \Drupal\advertising_products\Plugin\Queue\DatabaseQueue($provider['id'], $connection);
    $worker = $worker_manager->createInstance('advertising_product_' . $queue_name . '_update');
    $end = time() + $time_limit;
    $count = 0;

    while ((!$time_limit || time() < $end) && ($item = $queue->claimItem())) {
      try {
        drush_log(dt('Processing item @id from @name queue.', array('@name' => $queue_name, '@id' => $item->item_id)), LogLevel::INFO);
        $worker->processItem($item->data);
        $queue->deleteItem($item);
        $count++;
      }
      catch (SuspendQueueException $e) {
        // If the worker indicates there is a problem with the whole queue,
        // release the item and skip to the next queue.
        $queue->releaseItem($item);
        drush_set_error('DRUSH_SUSPEND_QUEUE_EXCEPTION', $e->getMessage());
      }
      catch (\Exception $e) {
        // In case of any other kind of exception, log it and leave the item
        // in the queue to be processed again later.
        drush_set_error('DRUSH_QUEUE_EXCEPTION', $e->getMessage());
      }
    }

    return $count;
  }
}


/**
 * Implements drush_{module}_{command}.
 */
function drush_advertising_products_import($filename = ''){
  $delay = drush_get_option('delay');

  if(!file_exists($filename) || !is_readable($filename)){
    drush_set_error('file not found', dt('The file whth name "!name" could not be found or is not readable.', ['!name' => $filename]));
    return;
  }

  $lines = [];
  if (($handle = fopen($filename, 'r')) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
      $lines[] = $data;
    }
  }
  fclose($handle);

  /** @var \Drupal\advertising_products\AdvertisingProductsProviderManager $providerManager */
  $providerManager = \Drupal::service('plugin.manager.advertising_products.provider');

  $_providers = array_keys($providerManager->getDefinitions());
  foreach ($_providers as $provider_name) {
    try {
      /** @var \Drupal\advertising_products\AdvertisingProductsProviderInterface $provider */
      $providers[$provider_name] = $providerManager->createInstance($provider_name);
    }
    catch (\Exception $e) {
      drush_log(dt('Provider !name not available', ['!name' => $provider_name]), LogLevel::WARNING);
    }
  }

  $results = array_fill_keys($_providers, array('errors' => 0, 'successes' => 0));
  $results['unknown'] = ['no_provider' => 0];
  $errors = $successes = 0;
  foreach ($lines as $product_data) {
    if (isset($providers[$product_data[1]])) {
      $product = $providers[$product_data[1]]->fetchProductOnTheFly($product_data[0]);
      if ($product) {
        $successes++;
        $results[$product_data[1]]['successes']++;
      }
      else {
        drush_log(dt('Product !id could not be loaded from provider !provider', ['!id' => $product_data[0], '!provider' => $product_data[1]]), LogLevel::WARNING);
        $errors++;
        $results[$product_data[1]]['errors']++;
      }
    }
    else {
      drush_log(dt('Provider !provider not available.', ['!provider' => $product_data[1]]), LogLevel::WARNING);
      $errors++;
      $results['unknown']['no_provider']++;
    }
    usleep($delay * 1000);
  }

  drush_log(dt('Number of products: !num', ['!num' => count($lines)]), LogLevel::SUCCESS);
  drush_log(dt('Successfull imports: !num', ['!num' => $successes]), LogLevel::SUCCESS);
  drush_log(dt('Unsuccessfull imports: !num', ['!num' => $errors]), LogLevel::WARNING);
  if (count($results['unknown']['no_provider'])) {
    drush_log(dt('There were !num products for which no provider is available.', ['!num' => $results['unknown']['no_provider']]));
  }
  foreach ($results as $provider_name => $log) {
    if (isset($providers[$provider_name])) {
      drush_log(dt('There were !num products imported for provider !name.', ['!num' => $log['successes'], '!name' => $provider_name]), LogLevel::SUCCESS);
      drush_log(dt('There were !num products not imported for provider !name.', ['!num' => $log['errors'], '!name' => $provider_name]), LogLevel::WARNING);
    }
  }
}
