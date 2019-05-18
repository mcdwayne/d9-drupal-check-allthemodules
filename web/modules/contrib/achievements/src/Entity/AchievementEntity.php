<?php

namespace Drupal\achievements\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines the Achievement entity.
 *
 * @ConfigEntityType(
 *   id = "achievement_entity",
 *   label = @Translation("Achievement"),
 *   handlers = {
 *     "view_builder" = "Drupal\achievements\Entity\AchievementEntityViewBuilder",
 *     "list_builder" = "Drupal\achievements\AchievementEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\achievements\Form\AchievementEntityForm",
 *       "edit" = "Drupal\achievements\Form\AchievementEntityForm",
 *       "delete" = "Drupal\achievements\Form\AchievementEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\achievements\AchievementEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "achievement_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/achievement/{achievement_entity}",
 *     "add-form" = "/achievement/add",
 *     "edit-form" = "/achievement/{achievement_entity}/edit",
 *     "delete-form" = "/achievement/{achievement_entity}/delete",
 *     "collection" = "/achievements"
 *   }
 * )
 */
class AchievementEntity extends ConfigEntityBase implements AchievementEntityInterface {

  /**
   * The Achievement entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Achievement entity label.
   *
   * @var string
   */
  protected $label;


  /**
   * The achievement type description.
   *
   * @var string
   */
  protected $description;

  /**
   * The achievement storage.
   *
   * @var string
   */
  protected $storage;

  /**
   * Flag denoting the achievement is invisible.
   *
   * @var bool
   */
  protected $invisible;

  /**
   * Flag denoting the achievement is secret.
   *
   * @var bool
   */
  protected $secret;

  /**
   * Whether to use the default (rather than custom) image.
   *
   * @var bool
   */
  protected $use_default_image = TRUE;

  /**
   * The path of the custom image for the locked achievement.
   *
   * @var string
   */
  protected $locked_image_path;

  /**
   * The path of the custom image for the unlocked achievement.
   *
   * @var string
   */
  protected $unlocked_image_path;

  /**
   * The number of points an achievement is worth.
   *
   * @var int
   */
  protected $points;

  /**
   * @return string
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * @return int
   */
  public function getPoints() {
    return $this->points;
  }

  /**
   * @return bool
   */
  public function isSecret() {
    return $this->secret;
  }

  /**
   * @return bool
   */
  public function isInvisible() {
    return $this->invisible;
  }

  /**
   * @return bool
   */
  public function useDefaultImage() {
    return $this->use_default_image;
  }

  /**
   * @param string $type
   * @param bool $allow_default
   *
   * @return string
   */
  public function getImagePath($type = 'locked', $allow_default = TRUE) {
    switch ($type) {
      case 'locked':
        if ($allow_default && empty($this->locked_image_path)) {
          return $this->getDefaultImagePath('default-locked-70.jpg');
        }
        else {
          return $this->locked_image_path;
        }
        break;

      case 'unlocked':
        if ($allow_default && empty($this->unlocked_image_path)) {
          return $this->getDefaultImagePath('default-unlocked-70.jpg');
        }
        else {
          return $this->unlocked_image_path;
        }
        break;

      case 'secret':
        return $this->getDefaultImagePath('default-secret-70.jpg');
        break;
    }
  }

  /**
   * @return string
   */
  public function __toString() {
    return (string) $this->label();
  }

  /**
   * Required by EntityViewBuilder. Achievements are never revisioned.
   */
  public function isDefaultRevision() {
    return TRUE;
  }

  /**
   * @param $filename
   *
   * @return string
   */
  public function getDefaultImagePath($filename) {
    $module_path = "/" . \Drupal::moduleHandler()->getModule('achievements')->getPath();
    return $module_path . '/images/' . $filename;
  }

}
