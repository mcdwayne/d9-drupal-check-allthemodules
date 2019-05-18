<?php

namespace Drupal\client_connection\Form;

use Drupal\client_connection\ClientConnectionManager;
use Drupal\client_connection\Plugin\ClientConnection\ClientConnectionInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ClientConnectionConfigForm.
 */
class ClientConnectionConfigForm extends EntityForm {

  /**
   * The Client Connection manager.
   *
   * @var \Drupal\client_connection\ClientConnectionManager
   */
  protected $clientManager;

  /**
   * The plugin form manager.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * Constructs a CommerceServiceForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\client_connection\ClientConnectionManager $client_manager
   *   The Client Connection manager.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   *   The plugin form manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ClientConnectionManager $client_manager, PluginFormFactoryInterface $plugin_form_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->clientManager = $client_manager;
    $this->pluginFormFactory = $plugin_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.client_connection'),
      $container->get('plugin_form.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\client_connection\Entity\ClientConnectionConfigInterface $entity */
    $entity = $this->entity;
    $plugin = $entity->getPlugin();

    // Setup form.
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $entity->label(),
      '#description' => $this->t("Label for the Client Connection Configuration."),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\client_connection\Entity\ClientConnectionConfig::load',
      ],
      '#disabled' => !$entity->isNew(),
      '#value' => !$entity->isNew() ? $entity->id() : $this->getMachineNameSuggestion($entity->getPluginId(), $entity),
    ];

    // Add plugin settings form.
    $form['#tree'] = TRUE;
    $form['settings'] = [];
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $form['settings'] = $this->getPluginForm($plugin)->buildConfigurationForm($form['settings'], $subform_state);
    $form['settings']['#access'] = TRUE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\client_connection\Entity\ClientConnectionConfigInterface $entity */
    $entity = $this->entity;
    $plugin = $entity->getPlugin();

    // Validate that plugin is set.
    if (is_null($entity->getPluginId())) {
      $form_state->setErrorByName('plugin', 'Invalid plugin type.');
    }

    // The plugin form puts all plugin form elements in the
    // settings form element, so just pass that to the plugin for validation.
    $this->getPluginForm($plugin)->validateConfigurationForm($form['settings'], SubformState::createForSubform($form['settings'], $form, $form_state));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    /** @var \Drupal\client_connection\Entity\ClientConnectionConfigInterface $entity */
    $entity = $this->entity;
    // The plugin form puts all plugin form elements in the
    // settings form element, so just pass that to the plugin for submission.
    $sub_form_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    // Call the plugin submit handler.
    $plugin = $entity->getPlugin();
    $this->getPluginForm($plugin)->submitConfigurationForm($form, $sub_form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    switch ($status) {
      case SAVED_NEW:
        $message = 'Created the %label Client Connection Configuration.';
        break;

      default:
        $message = 'Saved the %label Client Connection Configuration.';
    }

    drupal_set_message($this->t($message, ['%label' => $entity->label()]));

    $form_state->setRedirectUrl($this->getRedirectUrl($entity));
  }

  /**
   * Suggests a machine name to identify an instance of this client connection.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The plugin entity.
   *
   * @return string
   *   The suggested machine name.
   */
  protected function getMachineNameSuggestion($plugin_id, EntityInterface $entity = NULL) {
    $id = ($entity) ? $entity->uuid() : $plugin_id;
    return str_replace('-', '_', $id);
  }

  /**
   * Retrieves the plugin form for a given plugin and operation.
   *
   * @param \Drupal\client_connection\Plugin\ClientConnection\ClientConnectionInterface $plugin
   *   The Client Connection plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form for the plugin.
   */
  protected function getPluginForm(ClientConnectionInterface $plugin) {
    return $this->pluginFormFactory->createInstance($plugin, 'configure');
  }

  /**
   * Where the form redirects to after submission.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\Url
   *   The url to redirect to.
   */
  protected function getRedirectUrl(EntityInterface $entity) {
    return $entity->toUrl('collection');
  }

}
