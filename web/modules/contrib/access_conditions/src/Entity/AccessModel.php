<?php

namespace Drupal\access_conditions\Entity;

use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines an access model configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "access_model",
 *   label = @Translation("Access model"),
 *   label_singular = @Translation("Access model"),
 *   label_plural = @Translation("Access models"),
 *   label_count = @PluralTranslation(
 *     singular = "@count access model",
 *     plural = "@count access models"
 *   ),
 *   admin_permission = "administer access models",
 *   handlers = {
 *     "list_builder" = "Drupal\access_conditions\AccessModelListBuilder",
 *     "form" = {
 *       "add" = "Drupal\access_conditions\Form\AccessModelAddForm",
 *       "edit" = "Drupal\access_conditions\Form\AccessModelEditForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider"
 *     },
 *   },
 *   links = {
 *     "add-form" = "/admin/config/system/access-models/add",
 *     "edit-form" = "/admin/config/system/access-models/{access_model}",
 *     "delete-form" = "/admin/config/system/access-models/{access_model}/delete",
 *     "collection" = "/admin/config/system/access-models"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "access_conditions",
 *     "access_logic"
 *   }
 * )
 */
class AccessModel extends ConfigEntityBase implements AccessModelInterface {

  /**
   * The ID of the access model.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the access model.
   *
   * @var string
   */
  protected $label;

  /**
   * The description of the access model.
   *
   * @var string
   */
  protected $description;

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

}
