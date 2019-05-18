<?php

namespace Drupal\maestro_webform\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Launches a Maestro workflow with a Webform submission.
 *
 * @WebformHandler(
 *   id = "maestro",
 *   label = @Translation("Spawn Maestro Workflow"),
 *   category = @Translation("Workflow"),
 *   description = @Translation("Spawns a Maestro Workflow and passes the newly created webform to the process."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class MaestroWebformHandler extends WebformHandlerBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;
  
  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;
  
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, ModuleHandlerInterface $module_handler, WebformTokenManagerInterface $token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->moduleHandler = $module_handler;
    $this->tokenManager = $token_manager;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('module_handler'),
      $container->get('webform.token_manager')
      );
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = parent::getSummary();  //gets the overall settings
    //lets now fetch the Maestro Template label.
    $template = MaestroEngine::getTemplate($this->configuration['maestro_template']);
    $summary['#settings']['template_label'] = $template->label;
    return $summary;
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'maestro_template' => '',
        'maestro_message_success' => '',
        'maestro_message_failure' => '',
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $maestro_templates = MaestroEngine::getTemplates();
    $templates = [];
    $templates ['none'] = $this->t('Select Template');
    foreach($maestro_templates as $machine_name => $template) {
      $templates[$machine_name] = $template->label;
    }
      
    $form['maestro_template'] = [
      '#type' => 'select',
      '#title' => $this->t('Maestro Workflow Template'),
      '#description' => $this->t('The template you choose will be spawned when the webform submission occurs.'),
      '#default_value' => $this->configuration['maestro_template'],
      '#options' => $templates,
      '#suffix' => $this->t('Maestro will use a default unique_identifier called "submission" to track this content in the launched workflow.
          Only <b>VALIDATED</b> templates will be spawned.'),
    ];
    
    $form['maestro_message_success'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message to user on submission'),
      '#description' => $this->t('When a user submits the webform, what message would you like to show the user (Uses a Drupal message). Leave blank for no message.'),
      '#default_value' => $this->configuration['maestro_message_success'],
    ];
    
    $form['maestro_message_failure'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message to user when a Maestro process fails to start.'),
      '#description' => $this->t('If a Maestro Process fails to start, this message will be displayed to the end user (Uses a Drupal message). Leave blank for no message.'),
      '#default_value' => $this->configuration['maestro_message_failure'],
    ];
  
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    foreach ($this->configuration as $name => $value) {
      if (isset($values[$name])) {
        $this->configuration[$name] = $values[$name];
      }
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {

    //If we have maestro elements in the URL, we know that this is a submission that is as a result of 
    //the task being INSIDE of a workflow and not spawned by itself.
    //If we can bind to a template task based on the maestro elements, then we will 
    //set a webform submission data value signalling NOT to do any post-save actions if the task
    //has it's webform submission handler option checked off.
    $webform_submission->data['maestro_skip'] = FALSE;  //make sure the key exists and default to not checked
    $maestroElements = $form_state->getValue('maestro');
    if($maestroElements) {
      $queueID = $maestroElements['queue_id'];
      $templateTask = MaestroEngine::getTemplateTaskByQueueID($queueID);
      if($templateTask) {
        //$webform_submission->setElementData('maestro_skip', $templateTask['data']['skip_webform_handlers']);
        $webform_submission->data['maestro_skip'] = $templateTask['data']['skip_webform_handlers'];
      }
    }
    
  }
  
  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    //this is where we launch our maestro workflow based on the configuration options for this webform.
    if(!$update && !$webform_submission->data['maestro_skip']) {  //only do this on NEW webforms & webforms that are not mid-workflow.
      $maestro = new MaestroEngine();
      $processID = $maestro->newProcess($this->configuration['maestro_template']);
      if($processID !== FALSE) {
        if($this->configuration['maestro_message_success'] != '') {
          drupal_set_message($this->configuration['maestro_message_success'], 'status');
        }
        //Set the entity identifier to attach this webform to the maestro workflow template that is put into production
        if(!MaestroEngine::createEntityIdentifier($processID, $webform_submission->getEntityTypeId(), $webform_submission->bundle(), 'submission', $webform_submission->id())) {
          drupal_set_message($this->configuration['maestro_message_failure'], 'error');
        }
      }
      else {  //the Maestro new process method failed for some reason.
        if($this->configuration['maestro_message_failure'] != '') { //only show the message if our config says to do so
          drupal_set_message($this->configuration['maestro_message_failure'], 'error');
        }
      }
    }
  }
  

}
