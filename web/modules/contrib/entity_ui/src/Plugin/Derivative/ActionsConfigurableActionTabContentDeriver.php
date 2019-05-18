<?php

namespace Drupal\entity_ui\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Action\ActionManager;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives entity tab content plugins from configurable Action plugins.
 */
class ActionsConfigurableActionTabContentDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The action plugin manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $pluginManagerAction;

  /**
   * Creates a deriver instance.
   *
   * @param \Drupal\Core\Action\ActionManager $plugin_manager_action
   *   The action plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ActionManager $plugin_manager_action, EntityTypeManagerInterface $entity_type_manager) {
    $this->pluginManagerAction = $plugin_manager_action;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.action'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $action_plugin_definitions = $this->pluginManagerAction->getDefinitions();

    foreach ($action_plugin_definitions as $plugin_id => $plugin_definition) {
      // Skip plugins whose class does not inherit from ConfigurableActionBase.
      if (!is_subclass_of($plugin_definition['class'], ConfigurableActionBase::class)) {
        continue;
      }

      if (!$this->entityTypeManager->getDefinition($plugin_definition['type'], FALSE)) {
        // Skip plugins whose type is not an entity type ID, and thus can't
        // apply to an entity.
        continue;
      }

      $this->derivatives[$plugin_id] = [
        'label' => $this->t('Action form: ') . $plugin_definition['label'],
        // Action plugins have no description.
        'description' => $this->t('Provides a form to execute this action.'),
        // This needs to be in the definition for appliesToEntityType() to use.
        'action_plugin_id' => $plugin_id,
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
