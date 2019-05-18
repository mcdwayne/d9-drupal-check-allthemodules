<?php

namespace Drupal\reference_swiper;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Swiper option set config entity.
 */
interface SwiperOptionSetInterface extends ConfigEntityInterface {

  /**
   * Returns the Swiper parameters of the option set.
   *
   * @return array
   *   Option set parameters as used by the Swiper library.
   */
  public function getParameters();

  /**
   * Sets the Swiper parameters on the option set.
   *
   * @param array $values
   *   The option set's values that will be set on the entity, keyed by
   *   parameter id.
   *
   * @return \Drupal\reference_swiper\SwiperOptionSetInterface
   *   The swiper option set config entity.
   */
  public function setParameters(array $values);

  /**
   * Resets the Swiper parameters on the option set to an empty array.
   *
   * @return \Drupal\reference_swiper\SwiperOptionSetInterface
   *   The swiper option set config entity.
   */
  public function clearParameters();

}
