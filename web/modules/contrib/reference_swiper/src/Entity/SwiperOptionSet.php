<?php

namespace Drupal\reference_swiper\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\reference_swiper\SwiperOptionSetInterface;

/**
 * Defines the Swiper option set config entity.
 *
 * @ConfigEntityType(
 *   id = "swiper_option_set",
 *   label = @Translation("Swiper option set"),
 *   handlers = {
 *     "list_builder" = "Drupal\reference_swiper\SwiperOptionSetListBuilder",
 *     "form" = {
 *       "add" = "Drupal\reference_swiper\Form\SwiperOptionSetForm",
 *       "edit" = "Drupal\reference_swiper\Form\SwiperOptionSetForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     }
 *   },
 *   config_prefix = "swiper_option_set",
 *   admin_permission = "administer swiper option sets",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "collection" = "/admin/config/system/reference-swiper",
 *     "edit-form" = "/admin/config/system/reference-swiper/{swiper_option_set}",
 *     "delete-form" = "/admin/config/system/reference-swiper/{swiper_option_set}/delete",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "parameters",
 *   }
 * )
 */
class SwiperOptionSet extends ConfigEntityBase implements SwiperOptionSetInterface {

  /**
   * The option set ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The option set label.
   *
   * @var string
   */
  protected $label;

  /**
   * The option set parameters.
   *
   * @var array
   */
  protected $parameters = [];

  /**
   * {@inheritdoc}
   */
  public function getParameters() {
    return $this->parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function setParameters(array $values) {
    foreach ($values as $parameter => $value) {
      $this->parameters[$parameter] = $value;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function clearParameters() {
    $this->parameters = [];
    return $this;
  }

}
