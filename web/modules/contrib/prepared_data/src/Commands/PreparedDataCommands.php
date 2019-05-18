<?php

namespace Drupal\prepared_data\Commands;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\prepared_data\Processor\ProcessorManager;
use Drupal\user\Entity\User;
use Drupal\prepared_data\Provider\ProviderManager;

/**
 * Commands regards Prepared Data.
 */
class PreparedDataCommands {

  use StringTranslationTrait;

  /**
   * The manager for data provider plugins.
   *
   * @var \Drupal\prepared_data\Provider\ProviderManager
   */
  protected $providerManager;

  /**
   * The manager for data processor plugins.
   *
   * @var \Drupal\prepared_data\Processor\ProcessorManager
   */
  protected $processorManager;

  /**
   * The logger instance.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The Drupal state storage.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * PreparedDataCommands constructor.
   *
   * @param \Drupal\prepared_data\Provider\ProviderManager $provider_manager
   *   The manager for data provider plugins.
   * @param \Drupal\prepared_data\Processor\ProcessorManager $processor_manager
   *   The manager for data processor plugins.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger instance.
   * @param \Drupal\Core\State\StateInterface $state
   *   The Drupal state storage.
   */
  public function __construct(ProviderManager $provider_manager, ProcessorManager $processor_manager, LoggerChannelInterface $logger, StateInterface $state) {
    $this->providerManager = $provider_manager;
    $this->processorManager = $processor_manager;
    $this->logger = $logger;
    $this->state = $state;
  }

  /**
   * Builds up prepared data.
   *
   * @param string $partial
   *   (Optional) Either a partial or complete key for providers
   *   which need this information to build up the data.
   *   Take a look at the documentation of implemented ::nextMatch()
   *   methods to see which information is needed by certain providers.
   * @param array $options
   *   (Optional) An array of options. Have a look at
   *   PreparedDataDrushCommands:build() to see which options are available.
   */
  public function build($partial = NULL, array $options = []) {
    $options += [
      'refresh' => FALSE,
      'uid' => 0,
      'wait' => 100000,
      'limit' => 0,
      'offset' => 0,
      'state' => 1,
    ];

    $starttime = $time_passed = microtime(TRUE);

    $limit = (int) $options['limit'];
    $wait = $options['wait'];
    $nonstop = empty($options['limit']);
    $offset = (int) $options['offset'];
    $state_id = (int) $options['state'];
    $refresh = !empty($options['refresh']);

    if ($nonstop){
      $this->logger->info($this->t('Prepared data non-stop building process started.'));
    }
    else {
      $this->logger->info($this->t('Prepared data building process started for a total of @limit records.', ['@limit' => $limit]));
    }

    $this->processorManager->acquireActiveProcessors();
    $active_processors = [];
    foreach ($this->processorManager->getEnabledProcessors() as $processor) {
      if ($processor->isActive()) {
        $definition = $processor->getPluginDefinition();
        $active_processors[] = $definition['label'];
      }
    }
    if (empty($active_processors)) {
      $this->logger->warning($this->t('There are no active processors available, stopping building process.'));
    }
    $this->logger->info($this->t('The building process is using the following processors: @processors.', ['@processors' => implode(', ', $active_processors)]));
    unset($active_processors);

    $all_providers = $this->providerManager->getAllProviders();

    if (!empty($offset)) {
      foreach ($all_providers as $provider) {
        $provider->setNextMatchOffset($offset);
      }
    }

    if ($state_id >= 0) {
      $state = $this->state->get('prepared_data.build_' . $state_id, []);
      if (!empty($state)) {
        foreach ($all_providers as $provider) {
          $provider_id = $provider->getPluginId();
          if (isset($state['providers'][$provider_id])) {
            $provider->setStateValues($state['providers'][$provider_id]);
          }
        }
      }
    }

    $uid = (int) $options['uid'];
    if (!empty($uid)) {
      if (!($account = User::load($uid))) {
        $this->logger->error($this->t('Failed to load user account with ID @id, aborting.', ['@id' => $uid]));
        return;
      }
      foreach ($all_providers as $provider) {
        $provider->setCurrentUser($account);
      }
    }

    $i = $num_total = $num_interval = 0;
    while ($nonstop || ($i < $limit)) {
      if (round(microtime(TRUE) - $time_passed) > 60) {
        $time_passed = microtime(TRUE);
        $total_time = round(microtime(TRUE) - $starttime);
        $this->logger->info($this->t('The process of building prepared data is up since @time seconds and is still running. Processed builds within the last 60 seconds: @num.', ['@time' => $total_time, '@num' => $num_interval]));
        $num_interval = 0;
        // Save the intermediate state of this process.
        if ($state_id >= 0) {
          $state = [];
          foreach ($all_providers as $provider) {
            $provider_id = $provider->getPluginId();
            $state['providers'][$provider_id] = $provider->getStateValues();
          }
          $this->state->set('prepared_data.build_' . $state_id, $state);
        }
      }
      $matches = [];
      foreach ($all_providers as $provider) {
        if ($key = $provider->nextMatch($partial, FALSE)) {
          $matches[$provider->getPluginId()] = $key;
        }
      }
      if (!empty($matches)) {
        foreach ($matches as $provider_id => $key) {
          $i++;
          usleep($wait);
          $provider = $this->providerManager->createInstance($provider_id);
          if (($refresh && $provider->demandFresh($key)) || (!$refresh && $provider->demand($key, TRUE))) {
            $num_interval++;
            $num_total++;
          }
          else {
            if (!$provider->access($provider->getCurrentUser(), $key)) {
              $this->logger->notice($this->t('Access denied for user ID @uid at provider @provider when trying to build prepared data with key @key', ['@uid' => $provider->getCurrentUser()->id(), '@provider' => $provider->getPluginId(), '@key' => $key]));
            }
            else {
              $this->logger->error($this->t('Unknown failure at provider @provider when trying to build prepared data with key @key', ['@provider' => $provider->getPluginId(), '@key' => $key]));
            }
          }
        }
      }
      else {
        $i++;
      }
    }
    $total_time = round(microtime(TRUE) - $starttime);

    // Save the state of this process.
    if ($state_id >= 0) {
      $state = [];
      foreach ($all_providers as $provider) {
        $provider_id = $provider->getPluginId();
        $state['providers'][$provider_id] = $provider->getStateValues();
      }
      $this->state->set('prepared_data.build_' . $state_id, $state);
    }

    $this->logger->info($this->t('Prepared data building process finished with a total of @num builds. Time taken: @time seconds', ['@time' => $total_time, '@num' => $num_total]));
  }

  /**
   * Refreshes existing, expired records of prepared data.
   *
   * @param array $options
   *   (Optional) An array of options. Have a look at
   *   PreparedDataDrushCommands:refresh() to see which options are available.
   */
  public function refresh(array $options = []) {
    $options += [
      'all' => FALSE,
      'uid' => 0,
      'wait' => 100000,
      'limit' => 0,
    ];

    $starttime = $time_passed = microtime(TRUE);

    $limit = (int) $options['limit'];
    $wait = $options['wait'];
    $nonstop = empty($options['limit']);
    $refresh_all = (bool) $options['all'];

    if ($nonstop) {
      $this->logger->info($this->t('Prepared data non-stop refresh process started.'));
    }
    else {
      $this->logger->info($this->t('Prepared data refresh process started for a total of @limit records.', ['@limit' => $limit]));
    }

    $this->processorManager->acquireActiveProcessors();
    $active_processors = [];
    foreach ($this->processorManager->getEnabledProcessors() as $processor) {
      if ($processor->isActive()) {
        $definition = $processor->getPluginDefinition();
        $active_processors[] = $definition['label'];
      }
    }
    if (empty($active_processors)) {
      $this->logger->warning($this->t('There are no active processors available, stopping refresh process.'));
    }
    $this->logger->info($this->t('The refresh process is using the following processors: @processors.', ['@processors' => implode(', ', $active_processors)]));
    unset($active_processors);

    $all_providers = $this->providerManager->getAllProviders();

    $uid = (int) $options['uid'];
    if (!empty($uid)) {
      if (!($account = User::load($uid))) {
        $this->logger->error($this->t('Failed to load user account with ID @id, aborting.', ['@id' => $uid]));
        return;
      }
      foreach ($all_providers as $provider) {
        $provider->setCurrentUser($account);
      }
    }

    /** @var \Drupal\prepared_data\Storage\StorageInterface[] $storages */
    $storages = [];
    foreach ($all_providers as $provider) {
      $storage = $provider->getDataStorage();
      $collected = FALSE;
      foreach ($storages as $collected_storage) {
        if ($collected_storage === $storage) {
          $collected = TRUE;
          break;
        }
      }
      if (!$collected) {
        $storages[] = $storage;
      }
    }
    if (empty($storages)) {
      if (empty($all_providers)) {
        $this->logger->error($this->t('No data providers found, aborting.'));
      }
      else {
        $this->logger->error($this->t('No data storages found, aborting.'));
      }
      return;
    }

    $i = $num_total = $num_interval = 0;
    $first_error = TRUE;
    while ($nonstop || ($i < $limit)) {
      foreach ($storages as $storage) {
        $i++;
        usleep($wait);
        if (!($data = $storage->fetchNext())) {
          if ($first_error || round(microtime(TRUE) - $time_passed) > 60) {
            $first_error = FALSE;
            $time_passed = microtime(TRUE);
            $this->logger->warning($this->t('The process to refresh prepared data cannot find any record to refresh.'));
          }
          continue;
        }
        if (round(microtime(TRUE) - $time_passed) > 60) {
          $time_passed = microtime(TRUE);
          $total_time = round(microtime(TRUE) - $starttime);
          $this->logger->info($this->t('The process to refresh prepared data is up since @time seconds and is still running. Refreshes performed within the last 60 seconds: @num.', ['@time' => $total_time, '@num' => $num_interval]));
          $num_interval = 0;
        }

        $matched = FALSE;
        $key = $data->key();
        foreach ($all_providers as $provider) {
          if ($provider->match($key)) {
            $matched = TRUE;
            if ($provider->access($provider->getCurrentUser(), $key)) {
              if (($refresh_all && $provider->demandFresh($key)) || (!$refresh_all && $data->shouldRefresh() && $provider->demandFresh($key))) {
                $num_interval++;
                $num_total++;
              }
              elseif (!$data->shouldRefresh()) {
                // Seems there is currently only data, which does
                // not need a refresh. Wait a little bit and try again.
                usleep(300000);
              }
              else {
                $this->logger->warning($this->t('The process to refresh prepared data failed to refresh a data record with key @key when using provider @provider.', ['@key' => $key, '@provider' => $provider->getPluginId()]));
              }
            }
            else {
              $this->logger->notice($this->t('Access denied for user ID @uid at provider @provider when trying to refresh prepared data with key @key', ['@uid' => $provider->getCurrentUser()->id(), '@provider' => $provider->getPluginId(), '@key' => $key]));
            }
            break;
          }
        }
        if (!$matched) {
          $storage->delete($key);
          $this->logger->notice($this->t('The process to refresh prepared data found and removed a stale record with key @key.', ['@key' => $key]));
        }
      }
    }

    $total_time = round(microtime(TRUE) - $starttime);

    $this->logger->info($this->t('Prepared data refresh process finished with a total of @num performed refreshes. Time taken: @time seconds', ['@time' => $total_time, '@num' => $num_total]));
  }

}
