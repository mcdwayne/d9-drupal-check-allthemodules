<?php

/**
 * @file Contains \Drupal\powertagging_similar\Entity\PowerTaggingSimilarConfig.
 */

namespace Drupal\powertagging_similar\Entity;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * @ConfigEntityType(
 *   id ="powertagging_similar",
 *   label = @Translation("PowerTagging SeeAlso widget configuration"),
 *   handlers = {
 *     "list_builder" = "Drupal\powertagging_similar\PowerTaggingSimilarConfigListBuilder",
 *     "form" = {
 *       "default" = "Drupal\powertagging_similar\Form\PowerTaggingSimilarConfigForm",
 *       "add" = "Drupal\powertagging_similar\Form\PowerTaggingSimilarConfigForm",
 *       "edit" = "Drupal\powertagging_similar\Form\PowerTaggingSimilarConfigForm",
 *       "delete" = "Drupal\powertagging_similar\Form\PowerTaggingSimilarConfigDeleteForm"
 *     }
 *   },
 *   config_prefix = "powertagging_similar",
 *   admin_permission = "administer powertagging",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title"
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/semantic-drupal/powertagging/powertagging-similar/configurations/{powertagging}/delete",
 *     "edit-form" = "/admin/config/semantic-drupal/powertagging/powertagging-similar/configurations/{powertagging}",
 *     "collection" = "/admin/config/semantic-drupal/powertagging/powertagging-similar/",
 *   },
 *   config_export = {
 *     "title",
 *     "id",
 *     "powertagging_id",
 *     "config",
 *   }
 * )
 */
class PowerTaggingSimilarConfig extends ConfigEntityBase implements PowerTaggingSimilarConfigInterface {
  protected $id;
  protected $title;
  protected $powertagging_id;
  protected $config;

  /**
   * Constructor of the PowerTaggingSimilarConfig-class.
   *
   * {@inheritdoc|}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    if (is_null($this->id())) {
      $this->powertagging_id = 0;
      $this->config = array();
    }

    // Merge the Config with the default ones.
    $this->config = $this->config + self::getDefaultConfig();
  }

  /**
   * {@inheritdoc|}
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * {@inheritdoc|}
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * {@inheritdoc|}
   */
  public function getPowerTaggingId() {
    return $this->powertagging_id;
  }

  /**
   * {@inheritdoc|}
   */
  public function setPowerTaggingId($powertagging_id) {
    $this->powertagging_id = $powertagging_id;
  }

  /**
   * {@inheritdoc|}
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * {@inheritdoc|}
   */
  public function setConfig($config) {
    $this->config = $config + self::getDefaultConfig();
  }

  /**
   * {@inheritdoc|}
   */
  public static function getDefaultConfig() {
    return array(
      'content_types' => NULL,
      'display_type' => 'default',
      'merge_content' => FALSE,
      'merge_content_count' => 5,
    );
  }

  /**
   * Helper function to check whether an powertagging_similar entity with a specific
   * ID exists.
   *
   * @param string $id
   *   The ID to check if there is an entity for.
   *
   * @return bool
   *   TRUE if an entity with this ID already exists, FALSE if not.
   */
  public static function exist($id) {
    $entity_count = \Drupal::entityQuery('powertagging_similar')
      ->condition('id', $id)
      ->count()
      ->execute();
    return (bool) $entity_count;
  }
}