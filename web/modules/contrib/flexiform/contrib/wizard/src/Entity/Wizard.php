<?php

namespace Drupal\flexiform_wizard\Entity;

use Drupal\Component\Plugin\Context\ContextInterface;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines a flexiform wizard entity class.
 *
 * @ConfigEntityType(
 *   id = "flexiform_wizard",
 *   label = @Translation("Wizard"),
 *   handlers = {
 *     "access" = "Drupal\flexiform_wizard\Entity\WizardAccess",
 *     "form" = {
 *       "add" = "Drupal\flexiform_wizard\Form\WizardForm",
 *       "edit" = "Drupal\flexiform_wizard\Form\WizardEditForm",
 *     },
 *     "list_builder" = "Drupal\flexiform_wizard\WizardListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\flexiform_wizard\Routing\WizardHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer flexiform wizards",
 *   links = {
 *     "add-form" = "/admin/structure/wizards/add",
 *     "edit-form" = "/admin/structure/wizards/manage/{flexiform_wizard}",
 *     "collection" = "/admin/structure/wizards",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "use_admin_theme",
 *     "path",
 *     "access_logic",
 *     "access_conditions",
 *     "parameters",
 *     "pages",
 *   },
 * )
 */
class Wizard extends ConfigEntityBase implements EntityWithPluginCollectionInterface {

  /**
   * The ID of the wizard entity.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the wizard entity.
   *
   * @var string
   */
  protected $label;

  /**
   * The description of the wizard entity.
   *
   * @var string
   */
  protected $description;

  /**
   * The path of the wizard entity.
   *
   * @var string
   */
  protected $path;

  /**
   * The configuration of access conditions.
   *
   * @var array
   */
  protected $access_conditions = [];

  /**
   * Tracks the logic used to compute access, either 'and' or 'or'.
   *
   * @var string
   */
  protected $access_logic = 'and';

  /**
   * The plugin collection that holds the access conditions.
   *
   * @var \Drupal\Component\Plugin\LazyPluginCollection
   */
  protected $accessConditionCollection;

  /**
   * Indicates if this wizard should be displayed in the admin theme.
   *
   * @var bool
   */
  protected $use_admin_theme;

  /**
   * Parameter context configuration.
   *
   * An associative array keyed by parameter name, which contains associative
   * arrays with the following keys:
   * - machine_name: Machine-readable context name.
   * - label: Human-readable context name.
   * - type: Context type.
   *
   * @var array[]
   */
  protected $parameters = [];

  /**
   * Context configuration.
   *
   * @var array[]
   */
  protected $contexts = [];

  /**
   * Page configuration.
   *
   * @var array[]
   */
  protected $pages = [];

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function usesAdminTheme() {
    return isset($this->use_admin_theme) ? $this->use_admin_theme : strpos($this->getPath(), '/admin/') === 0;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    static::routeBuilder()->setRebuildNeeded();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    static::routeBuilder()->setRebuildNeeded();
  }

  /**
   * Wraps the route builder.
   *
   * @return \Drupal\Core\Routing\RouteBuilderInterface
   *   An object for state storage.
   */
  protected static function routeBuilder() {
    return \Drupal::service('router.builder');
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'access_conditions' => $this->getAccessConditions(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessConditions() {
    if (!$this->accessConditionCollection) {
      $this->accessConditionCollection = new ConditionPluginCollection(\Drupal::service('plugin.manager.condition'), $this->get('access_conditions'));
    }
    return $this->accessConditionCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function addAccessCondition(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getAccessConditions()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessCondition($condition_id) {
    return $this->getAccessConditions()->get($condition_id);
  }

  /**
   * {@inheritdoc}
   */
  public function removeAccessCondition($condition_id) {
    $this->getAccessConditions()->removeInstanceId($condition_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessLogic() {
    return $this->access_logic;
  }

  /**
   * {@inheritdoc}
   */
  public function getParameters() {
    $names = $this->getParameterNames();
    if ($names) {
      return array_intersect_key($this->parameters, array_flip($names));
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getParameter($name) {
    if ($this->hasParameter($name)) {
      return $this->parameters[$name];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasParameter($name) {
    return isset($this->parameters[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function setParameter($name, $type, $label = '') {
    $this->parameters[$name] = [
      'machine_name' => $name,
      'type' => $type,
      'label' => $label,
    ];
    // Reset contexts when a parameter is added or changed.
    $this->contexts = [];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeParameter($name) {
    unset($this->parameters[$name]);
    // Reset contexts when a parameter is removed.
    $this->contexts = [];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParameterNames() {
    if (preg_match_all('|\{(\w+)\}|', $this->getPath(), $matches)) {
      return $matches[1];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    $this->filterParameters();
  }

  /**
   * Filters the parameters to remove any without a valid type.
   *
   * @return $this
   */
  protected function filterParameters() {
    $names = $this->getParameterNames();
    foreach ($this->get('parameters') as $name => $parameter) {
      // Remove parameters without any type, or which are no longer valid.
      if (empty($parameter['type']) || !in_array($name, $names)) {
        $this->removeParameter($name);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addContext($name, ContextInterface $value) {
    $this->contexts[$name] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts() {
    return $this->contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getPages() {
    return $this->pages;
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $vars = parent::__sleep();

    // Gathered contexts objects should not be serialized.
    if (($key = array_search('contexts', $vars)) !== FALSE) {
      unset($vars[$key]);
    }

    return $vars;
  }

}
