<?php

namespace Drupal\switches\Entity;

use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Config\ConfigValueException;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines the Switch entity.
 *
 * @ConfigEntityType(
 *   id = "switch",
 *   label = @Translation("Switch"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\switches\SwitchListBuilder",
 *     "form" = {
 *       "add" = "Drupal\switches\Form\SwitchForm",
 *       "edit" = "Drupal\switches\Form\SwitchForm",
 *       "delete" = "Drupal\switches\Form\SwitchDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\switches\SwitchHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "switch",
 *   admin_permission = "administer switches",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/switch/{switch}",
 *     "add-form" = "/admin/structure/switch/add",
 *     "edit-form" = "/admin/structure/switch/{switch}/edit",
 *     "delete-form" = "/admin/structure/switch/{switch}/delete",
 *     "collection" = "/admin/structure/switch"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "activationConditions",
 *     "activationMethod",
 *     "manualActivationStatus",
 *   },
 *   lookup_keys = {
 *     "id"
 *   }
 * )
 */
class SwitchEntity extends ConfigEntityBase implements SwitchInterface, EntityWithPluginCollectionInterface {

  /**
   * The Switch ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Switch label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Switch description.
   *
   * @var string
   */
  protected $description;

  /**
   * The available contexts for this switch and its activation conditions.
   *
   * @var array
   */
  protected $contexts = [];

  /**
   * The activation method.
   *
   * @var string
   */
  protected $activationMethod;

  /**
   * The manual activation status.
   *
   * @var bool
   */
  protected $manualActivationStatus;

  /**
   * The activation condition settings for this switch.
   *
   * @var array
   */
  protected $activationConditions = [];

  /**
   * The activation conditions driving this switch's value.
   *
   * @var \Drupal\Core\Condition\ConditionInterface[]|\Drupal\Core\Condition\ConditionPluginCollection
   *   An array or collection of configured condition plugins.
   */
  protected $activationConditionsCollection;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $conditionPluginManager;

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'activationConditions' => $this->getActivationConditions(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getActivationMethod() {
    return $this->activationMethod;
  }

  /**
   * {@inheritdoc}
   */
  public function getManualActivationStatus() {
    return $this->manualActivationStatus;
  }

  /**
   * Returns activation status for conditional method.
   *
   * @return bool
   *   Activation Status.
   *
   * @todo Support configuration of a default value when using conditions.
   * @todo Allow configuration of and/or logic.
   */
  public function getConditionActivationStatus() {
    // Evaluate all configured activation conditions.
    foreach ($this->getActivationConditions() as $condition_plugin) {
      $condition_value = $condition_plugin->evaluate();

      // Since we're using AND logic we can stop when we encounter any condition
      // evaluating as FALSE.
      if (!$condition_value) {
        return FALSE;
      }
    }

    // If we've gotten to this point with AND logic the Switch is enabled.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setActivationMethod($activationMethod) {
    return $this->set('activationMethod', $activationMethod);
  }

  /**
   * {@inheritdoc}
   */
  public function setManualActivationStatus($status) {
    return $this->set('manualActivationStatus', $status);
  }

  /**
   * {@inheritdoc}
   */
  public function setActivationConditionConfig($instance_id, array $configuration) {
    $conditions = $this->getActivationConditions();
    if (!$conditions->has($instance_id)) {
      $configuration['id'] = $instance_id;
      $conditions->addInstanceId($instance_id, $configuration);
    }
    else {
      $conditions->setInstanceConfiguration($instance_id, $configuration);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getActivationConditions() {
    if (!isset($this->activationConditionsCollection)) {
      $this->activationConditionsCollection = new ConditionPluginCollection($this
        ->conditionPluginManager(), $this->get('activationConditions'));
    }

    return $this->activationConditionsCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getActivationConditionsConfig() {
    return $this->getActivationConditions()->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getActivationCondition($instance_id) {
    return $this->getActivationConditions()->get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getActivationStatus() {

    $activation_method = $this->getActivationMethod();

    switch ($activation_method) {

      case 'manual':
        return $this->getManualActivationStatus();

      case 'condition':
        return $this->getConditionActivationStatus();

      default:
        // Throw an exception since this means the activation method is not a
        // valid option we know how to handle.
        throw new ConfigValueException('Invalid activation method: ' . $activation_method);
    }
  }

  /**
   * Gets the condition plugin manager.
   *
   * @return \Drupal\Core\Executable\ExecutableManagerInterface
   *   The condition plugin manager.
   *
   * @todo Figure out how to load this through DI.
   */
  protected function conditionPluginManager() {
    if (!isset($this->conditionPluginManager)) {
      $this->conditionPluginManager = \Drupal::service('plugin.manager.condition');
    }
    return $this->conditionPluginManager;
  }

}
