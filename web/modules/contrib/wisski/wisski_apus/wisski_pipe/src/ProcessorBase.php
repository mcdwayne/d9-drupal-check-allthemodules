<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\ProcessorBase.
 */

namespace Drupal\wisski_pipe;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a base class for processor plugins.
 *
 * @see \Drupal\wisski_pipe\ProcessorBase
 * @see \Drupal\wisski_pipe\ProcessorManager
 * @see plugin_api
 */
abstract class ProcessorBase extends PluginBase implements ProcessorInterface {

  /**
   * The UUID of the processor.
   *
   * @var string
   */
  protected $uuid;
  

  /**
   * The name of the processor.
   *
   * @var string
   */
  protected $name = 0;

  
  /**
   * The weight of the processor compared to others in an prcessor collection.
   *
   * @var int
   */
  protected $weight = 0;

  
  /**
   * The data that was passed in the run call.
   *
   * @var mixed
   */
  protected $data;


  /**
   * The ticket for the run.
   *
   * @var string
   */
  protected $ticket;


  /**
   * The logger for the run.
   *
   * @var LoggerInterface
   */
  protected $logger;


  /**
   * Indicates whether this instance is currently processing data.
   *
   * @var boolean
   */
  protected $isRunning = FALSE;

  
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'uuid' => $this->uuid,
      'id' => $this->getPluginId(),
      'name' => $this->name,
      'weight' => $this->weight,
    ] + $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    $this->uuid = $this->configuration['uuid'];
    $this->name = $this->configuration['name'];
    $this->weight = $this->configuration['weight'];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = [
     'uuid' => '',
     'name' => '',
     'weight' => '0',
     'settings' => [],
    ];
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getHtmlName() {
    return $this->pluginDefinition['html_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    // By default the plugin description gives a good summary
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTags() {
    return $this->pluginDefinition['tags'];
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  
  /**
   * {@inheritdoc}
   */
  public function run($data, $ticket = '', LoggerInterface $logger = NULL) {
    
    if ($this->isRunning) {
      $id = $this->getId();
      $uuid = $this->getUuid();
      throw new \RuntimeException("Processor $id/$uuid is already running. No parallel runs supported. Maybe there is a loop?");
    }

    $this->data = $data;
    $this->ticket = $ticket;
    $this->logger = $logger;
    
    $this->isRunning = TRUE;
    $this->log(LogLevel::INFO, "Running processor {_processor_name}/{_processor_uuid}");
    $this->doRun();
    $this->isRunning = FALSE;

    return $this->data;

  }

  
  /**
   * Implements the processing logic of the processor.
   * 
   * This function should work on the $this->data field.
   * This field should be extended with/replaced by the data to be returned.
   *
   * The method may use the logger and ticket fields.
   */
  public abstract function doRun();

  
  /**
   * This is a convenience method for logging. See also the log*() methods.
   * 
   * This methods checks whether there is a logger
   * and always sets these context variables, so that postprocessors should be
   * able to use these consistently:
   * _processor_uuid: the UUID of the processor that issued the msg
   * _processor_name: the human-readable name of the processor
   * _ticket: the ticket of the process
   */
  protected function log($level, $message, array $context = array()) {
    if ($this->logger != NULL) {
      $context['_processor_uuid'] = $this->getUuid();
      $context['_processor_name'] = $this->getName();
      $context['_ticket'] = $this->ticket;
      $this->logger->log($level, "{_processor_name}: $message", $context);
    }
  }

  /**
   * @see log()
   */
  protected function logAlert($message, array $context = array()) {
    $this->log(LogLevel::ALERT, $message, $context);
  }

  /**
   * @see log()
   */
  protected function logCritical($message, array $context = array()) {
    $this->log(LogLevel::CRITICAL, $message, $context);
  }

  /**
   * @see log()
   */
  protected function logDebug($message, array $context = array()) {
    $this->log(LogLevel::DEBUG, $message, $context);
  }

  /**
   * @see log()
   */
  protected function logEmergency($message, array $context = array()) {
    $this->log(LogLevel::EMERGENCY, $message, $context);
  }

  /**
   * @see log()
   */
  protected function logError($message, array $context = array()) {
    $this->log(LogLevel::ERROR, $message, $context);
  }

  /**
   * @see log()
   */
  protected function logInfo($message, array $context = array()) {
    $this->log(LogLevel::INFO, $message, $context);
  }

  /**
   * @see log()
   */
  protected function logNotice($message, array $context = array()) {
    $this->log(LogLevel::NOTICE, $message, $context);
  }

  /**
   * @see log()
   */
  protected function logWarning($message, array $context = array()) {
    $this->log(LogLevel::WARNING, $message, $context);
  }




  /**
   * {@inheritdoc}
   */
  public function runsOnPipes() {
    return array();
  }

  
  /**
   * {@inheritdoc}
   */
  public function inputFields() {
    return array();
  }


  /**
   * {@inheritdoc}
   */
  public function outputFields() {
    return array();
  }


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

  }


}
