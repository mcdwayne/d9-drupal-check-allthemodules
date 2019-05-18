<?php

namespace Drupal\janrain_connect_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\janrain_connect_ui\Service\JanrainConnectUiFlowExtractorService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for configure messages.
 */
class JanrainConnectUiUsersForm extends ConfigFormBase {

  /**
   * JanrainConnectFlowExtractorService.
   *
   * @var \Drupal\janrain_connect_ui\Service\JanrainConnectUiFlowExtractorService
   */
  private $janrainConnectFlowExtractorService;

  /**
   * {@inheritdoc}
   */
  public function __construct(JanrainConnectUiFlowExtractorService $janrain_connect_flow_extractor_service) {
    $this->janrainConnectFlowExtractorService = $janrain_connect_flow_extractor_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('janrain_connect_ui.flow_extractor'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'janrain_connect_admin_settings_users';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'janrain_connect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('janrain_connect.settings');

    $options_fields = $this->janrainConnectFlowExtractorService->getFieldsSchemaKey();

    $form = [];

    if (!$options_fields) {
      drupal_set_message($this->t('No Entity Type Attributes were found in the flow. Did you perform Janrain Sync?'), 'warning');
      return [];
    }

    $form['fields'] = [
      '#type' => 'details',
      '#title' => 'Fields',
      '#open' => TRUE,
    ];

    $fields_persist_users = $config->get('fields_persist_users');

    if (!$fields_persist_users) {
      $fields_persist_users = [];
    }

    $form['fields']['fields_persist_users'] = [
      '#type' => 'checkboxes',
      '#options' => $options_fields,
      '#title' => $this->t('Fields to persist in Drupal user'),
      '#default_value' => $fields_persist_users,
      '#description' => $this->t('These fields are attributes from Janrain Entity Type (captured in the Janrain Flow).'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    parent::submitForm($form, $form_state);

    $config = $this->config('janrain_connect.settings');

    $fields_persist_users = $form_state->getValue('fields_persist_users');

    $config->set('fields_persist_users', $fields_persist_users);

    $config->save();
  }

}
