<?php

namespace Drupal\prepared_data\Builder;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\prepared_data\PreparedData;
use Drupal\prepared_data\PreparedDataInterface;
use Drupal\prepared_data\Processor\ProcessorManager;
use Drupal\prepared_data\Processor\ProcessorRuntimeException;

/**
 * Class DataBuilder which builds up and refreshes prepared data.
 */
class DataBuilder implements DataBuilderInterface {

  use StringTranslationTrait;

  /**
   * The manager for data processor plugins.
   *
   * @var \Drupal\prepared_data\Processor\ProcessorManager
   */
  protected $processorManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Whether the module hook files have been included or not.
   *
   * @var bool
   */
  protected $hookFilesIncluded = FALSE;

  /**
   * The logger instance.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * DataBuilder constructor.
   *
   * @param \Drupal\prepared_data\Processor\ProcessorManager $processor_manager
   *   The manager for data processor plugins.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger instance.
   */
  public function __construct(ProcessorManager $processor_manager, ModuleHandlerInterface $module_handler, LoggerChannelInterface $logger) {
    $this->processorManager = $processor_manager;
    $this->moduleHandler = $module_handler;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function build($key) {
    $data = new PreparedData([], $key);
    $this->refresh($data);
    $this->moduleHandler->invokeAll('prepared_data_build', [$data]);
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function refresh(PreparedDataInterface $data) {
    $all_processors = $this->processorManager->getAllProcessors();
    $enabled_processors = $this->processorManager->getEnabledProcessors();
    $module_handler = $this->moduleHandler;
    $this->includeHookFiles();
    $faults = [];

    // Initialization phase.
    foreach ($enabled_processors as $index => $processor) {
      if (!$processor->isActive()) {
        continue;
      }
      try {
        $processor->initialize($data);
        $module_handler->invokeAll('prepared_data_initialized_by', [$processor, $data]);
        $module_handler->invokeAll('prepared_data_initialized_by_' . $processor->getPluginId(), [$processor, $data]);
      }
      catch (ProcessorRuntimeException $e) {
        $this->logger->error($this->t('The processor @processor failed to initialize for prepared data key @key. Exception message: @message.', ['@processor' => $processor->getPluginId(), '@key' => $data->key(), '@message' => $e->getMessage()]));
        $faults[$index] = (int) $e->getCode();
        $processor->setActive(FALSE);
      }
    }

    // Main processing phase.
    foreach ($enabled_processors as $index => $processor) {
      if (!$processor->isActive()) {
        continue;
      }
      try {
        $processor->process($data);
        $module_handler->invokeAll('prepared_data_processed_by', [$processor, $data]);
        $module_handler->invokeAll('prepared_data_processed_by_' . $processor->getPluginId(), [$processor, $data]);
      }
      catch (ProcessorRuntimeException $e) {
        $this->logger->error($this->t('The processor @processor failed to process on prepared data key @key. Exception message: @message.', ['@processor' => $processor->getPluginId(), '@key' => $data->key(), '@message' => $e->getMessage()]));
        $faults[$index] = (int) $e->getCode();
        $processor->setActive(FALSE);
      }
    }

    // Finishing phase.
    foreach ($enabled_processors as $index => $processor) {
      if (!$processor->isActive()) {
        continue;
      }
      try {
        $processor->finish($data);
        $module_handler->invokeAll('prepared_data_finished_by', [$processor, $data]);
        $module_handler->invokeAll('prepared_data_finished_by_' . $processor->getPluginId(), [$processor, $data]);
      }
      catch (ProcessorRuntimeException $e) {
        $this->logger->error($this->t('The processor @processor failed to finish on prepared data key @key. Exception message: @message.', ['@processor' => $processor->getPluginId(), '@key' => $data->key(), '@message' => $e->getMessage()]));
        $faults[$index] = (int) $e->getCode();
        $processor->setActive(FALSE);
      }
    }

    // Cleanup phase for all existing processors.
    foreach ($all_processors as $processor) {
      try {
        $processor->cleanup($data);
        $module_handler->invokeAll('prepared_data_cleanup_by', [$processor, $data]);
        $module_handler->invokeAll('prepared_data_cleanup_by_' . $processor->getPluginId(), [$processor, $data]);
      }
      catch (ProcessorRuntimeException $e) {
        $this->logger->error($this->t('The processor @processor failed to cleanup on prepared data key @key. Exception message: @message.', ['@processor' => $processor->getPluginId(), '@key' => $data->key(), '@message' => $e->getMessage()]));
      }
    }

    foreach ($faults as $index => $error_code) {
      if ($error_code > 1000) {
        // The processor may continue on the next record.
        $enabled_processors[$index]->setActive(TRUE);
        unset($faults[$index]);
      }
    }

    // Refresh completed, flag not to be refreshed again accidentally.
    $data->shouldRefresh(FALSE);

    $module_handler->invokeAll('prepared_data_refreshed', [$data]);
  }

  /**
   * Includes module hook files.
   */
  protected function includeHookFiles() {
    if (!$this->hookFilesIncluded) {
      $this->hookFilesIncluded = TRUE;
      $module_handler = $this->moduleHandler;
      $modules = $module_handler->getModuleList();
      foreach (array_keys($modules) as $module) {
        $module_handler->loadInclude($module, 'inc', $module . '.prepared_data');
      }
    }
  }

}
