<?php

namespace Drupal\webform_ansible\Plugin\WebformHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ansible\Controller\AnsibleController;

/**
 * Form submission to Ansible handler.
 *
 * @WebformHandler(
 *   id = "ansible",
 *   label = @Translation("Ansible"),
 *   category = @Translation("Ansible"),
 *   description = @Translation("Sends a form submission to Ansible."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class WebformAnsibleHandler extends WebformHandlerBase {

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTranslationManagerInterface
   */
  protected $tokenManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, WebformTokenManagerInterface $tokenManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->tokenManager = $tokenManager;
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
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'entity' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['entity'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Ansible configuration'),
      '#default_value' => $this->configuration['entity'],
      '#options' => self::getentity(),
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

    foreach ($webform_submission->getData() as $key => $value) {
      $extravars[] = $key . "=" . $value;
    }

    $result = AnsibleController::exec($this->configuration['entity'], $extravars);

    if (preg_match("/unreachable=[1-9]/", $result) || preg_match("/failed=[1-9]/", $result) || preg_match("/fatal/", $result)) {
      \Drupal::logger('Ansible')->error("<pre>" . $result . "</pre>");
      drupal_set_message(t("Error: Show Drupal logs for more information"), 'error');
    }
    else {
      drupal_set_message(t('Command execute successfully'), 'status');
    }
  }

  /**
   * Get entity list from ansible_entity.
   *
   * @return array
   *   Return entity list
   */
  private static function getentity() {
    $entities = \Drupal::entityQuery('ansible_entity')->execute();
    $entities_list = \Drupal::entityTypeManager()->getStorage('ansible_entity')->loadMultiple($entities);

    $entities_data = [];
    foreach ($entities_list as $entitie) {
      $entities_data[$entitie->id()] = $entitie->label() . " (" . $entitie->id() . ")";
    }

    return $entities_data;
  }

}
