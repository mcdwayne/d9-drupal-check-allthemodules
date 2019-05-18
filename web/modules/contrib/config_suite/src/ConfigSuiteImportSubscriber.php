<?php

namespace Drupal\config_suite;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Cache\ChainedFastBackend;

/**
 * Subscribe to KernelEvents::REQUEST events and redirect if site is currently
 * in maintenance mode.
 */
class ConfigSuiteImportSubscriber implements EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkForRedirection');

    return $events;
  }

  /**
   * This method is called whenever the KernelEvents::REQUEST event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function checkForRedirection(GetResponseEvent $event) {
    $user = \Drupal::currentUser()->getRoles();
    if(!in_array("administrator", $user)) {
      return;
    }

    $config = \Drupal::config('config_suite.settings');
    if (!$config->get('automatic_import')) {
      return;
    }

    $storage_comparer = new StorageComparer(
      \Drupal::service('config.storage.sync'),
      \Drupal::service('config.storage'),
      \Drupal::service('config.manager')
    );

    $config_importer = new ConfigImporter(
      $storage_comparer,
      \Drupal::service('event_dispatcher'),
      \Drupal::service('config.manager'),
      \Drupal::lock(),
      \Drupal::service('config.typed'),
      \Drupal::moduleHandler(),
      \Drupal::service('module_installer'),
      \Drupal::service('theme_handler'),
      \Drupal::service('string_translation')
    );
    if (!$config_importer->alreadyImporting()) {
      try {
        $last_save_time = \Drupal::service('cache.backend.database')
          ->get('config')
          ->get(ChainedFastBackend::LAST_WRITE_TIMESTAMP_PREFIX . 'cache_config')
          ->data;

        $last_sync_update = $this->checkModifiedTime();
        if ($last_sync_update > $last_save_time ) {
          $sync_steps = $config_importer->initialize();
          foreach ($sync_steps as $step) {
            $context = array();
            do {
              $config_importer->doSyncStep($step, $context);
            } while ($context['finished'] < 1);
          }
        }
      } catch (ConfigException $e) {
        $message = 'The import failed due for the following reasons:' . "\n";
        $message .= implode("\n", $config_importer->getErrors());

        watchdog_exception('config_import', $e);
      }
    }
  }

  protected function checkModifiedTime() {
    $destination_dir = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
    $stat = stat($destination_dir);

    return $stat['mtime'];
  }
}