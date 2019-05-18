<?php

namespace Drupal\migrate_recurring_import\EventSubscriber;

use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateMessage;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\migrate_tools\MigrateExecutable;

/**
 * A subscriber running api after a response is sent.
 */
class MigrateRecurringImport implements EventSubscriberInterface {

  /**
   * The MigrationPluginManagerInterface service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migration_plugin_manager;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface;
   */
  protected $state;
  
  /**
   * The account switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;
  
  /**
   * The lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;
  
  /**
   * Constructs a new automated runner.
   */
  public function __construct(MigrationPluginManagerInterface $migration_plugin_manager, StateInterface $state, AccountSwitcherInterface $account_switcher, LockBackendInterface $lock) {
    $this->migration_plugin_manager = $migration_plugin_manager;
    $this->state = $state;
    $this->accountSwitcher = $account_switcher;
    $this->lock = $lock;
  }

  /**
   * Run the migration if enabled.
   *
   * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   The Event to process.
   */
  public function onTerminate(PostResponseEvent $event) {
    global $config;

    $log = new MigrateMessage();
    $plugins = $this->migration_plugin_manager->createInstances([]);
    
    foreach ($plugins as $id => $migration) {
      if((null !== $migration->get('third_party_settings')) && !empty($migration->get('third_party_settings'))){
        $migrations[$id] = $migration;
      }
    }
    
    // Nothing to do here, just return
    if(empty($migrations)) {
      return;
    }
    
    // Do not return any migrations which fail to meet requirements.
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    foreach ($migrations as $id => $migration) {
      if ($migration->getSourcePlugin() instanceof RequirementsInterface) {
        try {
          $migration->getSourcePlugin()->checkRequirements();
        }
        catch (RequirementsException $e) {
          unset($migrations[$id]);
        }
      }
    }
    
    // Allow execution to continue even if the request gets cancelled.
    @ignore_user_abort(TRUE);
    // Try to allocate enough time to run all
    drupal_set_time_limit(240);
    // Force the current user to anonymous to ensure consistent permissions on
    // migration runs.
    // $this->accountSwitcher->switchTo(new AnonymousUserSession());
    // Loop through migrations
    foreach ($migrations as $id => $migration) {
      $third_party_settings = $migration->get('third_party_settings');
      $interval = $third_party_settings['migrate_recurring_import']['interval'];
      if ($interval > 0) {
        $run_next = $this->state->get('system.' . $id . '.run_last', 0) + $interval;
        if ((int) $event->getRequest()->server->get('REQUEST_TIME') > $run_next) {
          // run the migration
          $migration->getIdMap()->prepareUpdate();
          try {
            $executable = new MigrateExecutable($migration, $log);
            if (isset($config['migrate_recurring_import']) && $config['migrate_recurring_import']) {
              $executable->import();
              // $executable->rollbackMissingItems();
            }
          } 
          catch(Exception $e){
            continue;
          }
          // set the timeout
          $this->state->set('system.' . $id . '.run_last', $event->getRequest()->server->get('REQUEST_TIME'));
        }
        // Restore the user.
        // $this->accountSwitcher->switchBack();
      }
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::TERMINATE => [['onTerminate', 100]]];
  }

}
