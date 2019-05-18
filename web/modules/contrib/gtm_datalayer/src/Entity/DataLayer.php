<?php

namespace Drupal\gtm_datalayer\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\gtm_datalayer\DataLayerProcessorPluginCollection;

/**
 * Defines a GTM dataLayer configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "gtm_datalayer",
 *   label = @Translation("GTM dataLayer"),
 *   label_singular = @Translation("GTM dataLayer"),
 *   label_plural = @Translation("GTM dataLayers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count GTM dataLayer",
 *     plural = "@count GTM dataLayers"
 *   ),
 *   admin_permission = "administer gtm datalayer",
 *   handlers = {
 *     "list_builder" = "Drupal\gtm_datalayer\DataLayerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\gtm_datalayer\Form\DataLayerAddForm",
 *       "edit" = "Drupal\gtm_datalayer\Form\DataLayerEditForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider"
 *     },
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/datalayers/add",
 *     "edit-form" = "/admin/structure/datalayers/{gtm_datalayer}",
 *     "delete-form" = "/admin/structure/datalayers/{gtm_datalayer}/delete",
 *     "enable" = "/admin/structure/datalayers/{gtm_datalayer}/enable",
 *     "disable" = "/admin/structure/datalayers/{gtm_datalayer}/disable",
 *     "collection" = "/admin/structure/datalayers"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "plugin",
 *     "weight",
 *     "access_conditions",
 *     "access_logic"
 *   }
 * )
 */
class DataLayer extends ConfigEntityBase implements DataLayerInterface {

  /**
   * The ID of the GTM dataLayer.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the GTM dataLayer.
   *
   * @var string
   */
  protected $label;

  /**
   * The description of the GTM dataLayer.
   *
   * @var string
   */
  protected $description;

  /**
   * The processor plugin instance ID of the GTM dataLayer.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The weight of the GTM dataLayer.
   *
   * @var int
   */
  protected $weight;

  /**
   * The processor plugin of the GTM dataLayer.
   *
   * @var \Drupal\gtm_datalayer\Plugin\DataLayerProcessorInterface
   */
  protected $pluginCollection;

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
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;

    return $this;
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
  public function setDescription($description) {
    $this->description = $description;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin($plugin_id) {
    $this->plugin = $plugin_id;

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
  public function setAccessLogic($access_logic) {
    $this->access_logic = $access_logic;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataLayerProcessor() {
    return $this->getDataLayerProcessorCollection()->get($this->plugin);
  }

  /**
   * Encapsulates the creation of the dataLayer Processor's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The GTM dataLayer's processor plugin collection.
   */
  protected function getDataLayerProcessorCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new DataLayerProcessorPluginCollection(\Drupal::service('plugin.manager.gtm_datalayer.processor'), $this->plugin, $this->id);
    }

    return $this->pluginCollection;
  }

}
