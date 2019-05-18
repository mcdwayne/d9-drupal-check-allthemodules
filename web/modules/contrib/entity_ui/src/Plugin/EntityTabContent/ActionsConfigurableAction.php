<?php

namespace Drupal\entity_ui\Plugin\EntityTabContent;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_ui\Plugin\EntityTabContentBase;
use Drupal\entity_ui\Plugin\EntityTabContentInterface;
use Drupal\Core\Action\ActionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Shows an action form as tab content.
 *
 * This uses the action plugin's configuration form as the tab content,
 * effectively allowing the user to configure and execute an action on the fly.
 *
 * @EntityTabContent(
 *   id = "actions_configurable",
 *   deriver = "Drupal\entity_ui\Plugin\Derivative\ActionsConfigurableActionTabContentDeriver",
 * )
 */
class ActionsConfigurableAction extends EntityTabContentBase implements ContainerFactoryPluginInterface, EntityTabContentInterface, BaseFormIdInterface {

  /**
   * The action plugin manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $pluginManagerAction;

  /**
   * Creates a EntityForm instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Action\ActionManager $plugin_manager_action
   *   The action plugin manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $bundle_info_service,
    ActionManager $plugin_manager_action
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $bundle_info_service);
    $this->pluginManagerAction = $plugin_manager_action;

    // TODO: do this later than __construct?
    // Get the associated action plugin.
    $definition = $this->getPluginDefinition();
    $action_plugin_id = $definition['action_plugin_id'];
    $this->actionPlugin = $this->pluginManagerAction->createInstance($action_plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.action')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function appliesToEntityType(EntityTypeInterface $entity_type, $definition) {
    // This applies to an entity type if it is the same as the associated action
    // plugin's type. This means that this only applies to one entity type.
    $action_definition = \Drupal::service('plugin.manager.action')->getDefinition($definition['action_plugin_id']);

    return ($action_definition['type'] == $entity_type->id());
  }

  /**
   * {@inheritdoc}
   */
  public static function suggestedEntityTabValues($definition) {
    $action_plugin_id = $definition['action_plugin_id'];

    $action_plugin_definition = \Drupal::service('plugin.manager.action')->getDefinition($action_plugin_id);

    // Use the action plugin ID as the suggested path, but trim the entity type
    // ID prefix and 'action' suffix if these are present.
    $path = $action_plugin_id;
    $path = preg_replace("@^{$action_plugin_definition['type']}_@", '', $path);
    $path = preg_replace("@_action$@", '', $path);

    return [
      'tab_title' => $action_plugin_definition['label'],
      'page_title' => $action_plugin_definition['label'],
      'path' => $path,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildContent(EntityInterface $target_entity) {
    // TODO: inject.
    return \Drupal::formBuilder()->getForm($this, $target_entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    $plugin_id = $this->getPluginId();
    // Replace the ':' separator between base ID and derivative ID.
    return 'entity_tab_' . str_replace(':', '_', $plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Build the form ID from a prefix, and the tab ID, which is unique.
    $entity_tab_id = $this->entityTab->id();
    return 'entity_tab_' . str_replace('.', '_', $entity_tab_id);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $target_entity = NULL) {
    // Get the associated action plugin.
    $definition = $this->getPluginDefinition();
    $action_plugin_id = $definition['action_plugin_id'];
    $action_plugin = $this->pluginManagerAction->createInstance($action_plugin_id);

    // Present the configuration form from the action plugin as the form here.
    $form = $action_plugin->buildConfigurationForm($form, $form_state);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Execute'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->actionPlugin->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->actionPlugin->submitConfigurationForm($form, $form_state);

    // Get the target entity from the form's build info.
    $build_info = $form_state->getBuildInfo();
    $target_entity = $build_info['args'][0];

    $this->actionPlugin->execute($target_entity);
  }

}
