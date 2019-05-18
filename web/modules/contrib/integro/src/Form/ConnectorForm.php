<?php

namespace Drupal\integro\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\integro\IntegrationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConnectorForm extends EntityForm {

  /**
   * The integration manager.
   *
   * @var \Drupal\integro\IntegrationManagerInterface
   */
  protected $integrationManager;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\integro\IntegrationManagerInterface $integration_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, IntegrationManagerInterface $integration_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->integrationManager = $integration_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('integro_integration.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'integro_integration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\integro\Entity\ConnectorInterface $connector */
    $connector = $this->entity;
    $connector_entity_type = $connector->getEntityType();
    $connector_entity_label = $connector_entity_type->getLabel();
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add ' . $connector_entity_label);
    }
    else {
      $form['#title'] = $this->t('Edit %label ' . $connector_entity_label, ['%label' => $connector->label()]);
    }

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $connector->label(),
      '#description' => $this->t('The human-readable name of this entity.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $connector->id(),
      '#maxlength' => EntityTypeInterface::ID_MAX_LENGTH,
      '#disabled' => !$connector->isNew(),
      '#machine_name' => [
        'exists' => ['Drupal\integro\Entity\Connector', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this entity. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $values = $form_state->getValues();

    // Integration.
    /** @var \Drupal\integro\IntegrationInterface $integration */
    $integration_default = !$connector->isNew() ? $connector->get('integration') : NULL;

    if (isset($values['integration']) && $values['integration']) {
      $integration_default = $values['integration'];
    }

    $integration_options = ['' => $this->t('- Select -')] + $this->integrationManager->getOptions();

    $form['integration'] = [
      '#type' => 'select',
      '#options' => $integration_options,
      '#title' => $this->t('Integration'),
      '#required' => TRUE,
      '#default_value' => $integration_default,
      '#disabled' => !$connector->isNew(),
      '#ajax' => [
        'callback' => '::selectIntegration',
        'wrapper' => 'edit-connector-integration-wrapper',
      ],
    ];

    $form['container'] = [
      '#tree' => FALSE,
      '#prefix' => '<div id="edit-connector-integration-wrapper">',
      '#suffix' => '</div>',
    ];

    if ($integration_default) {
      $integration_selected = $this->integrationManager->getIntegrations()[$integration_default];
      /** @var \Drupal\integro\ClientInterface $integration_client */
      $integration_client = $integration_selected->getDefinition()->getClientPlugin();
      if (!$connector->isNew()) {
        $integration_client->setConfiguration($connector->getClientConfiguration());
      }
      $form['container']['client_configuration'] = $integration_client->buildConfigurationForm($form, $form_state);
      $form['container']['client_configuration']['#tree'] = TRUE;
    }

    return parent::form($form, $form_state);
  }

  /**
   * Handles selecting the integration.
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return
   */
  public function selectIntegration($form, FormStateInterface $form_state) {
    return $form['container'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\integro\Entity\ConnectorInterface $connector */
    $connector = $this->entity;
    $connector_entity_type = $connector->getEntityType();
    $connector_entity_label = $connector_entity_type->getLabel();
    $status = $connector->save();

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t($connector_entity_label . ' %label has been updated.', ['%label' => $connector->label()]));
    }
    else {
      drupal_set_message($this->t($connector_entity_label . ' %label has been created.', ['%label' => $connector->label()]));
    }

    $form_state->setRedirect('entity.' . $connector_entity_type->id() . '.collection');
  }

}
