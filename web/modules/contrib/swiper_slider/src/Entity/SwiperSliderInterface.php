<?php

namespace Drupal\swiper_slider\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Swiper slide entities.
 *
 * @ingroup swiper_slider
 */
interface SwiperSliderInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.
  /**
   * Gets the Swiper slide name.
   *
   * @return string
   *   Name of the Swiper slide.
   */
  public function getName();

  /**
   * Sets the Swiper slide name.
   *
   * @param string $name
   *   The Swiper slide name.
   *
   * @return \Drupal\swiper_slider\Entity\SwiperSlideInterface
   *   The called Swiper slide entity.
   */
  public function setName($name);

  /**
   * Gets the Swiper slide class.
   *
   * @return string
   *   Class of the Swiper slide.
   */
  public function getClass();

  /**
   * Sets the Swiper slide class.
   *
   * @param string $class
   *   The Swiper slide class.
   *
   * @return \Drupal\swiper_slider\Entity\SwiperSlideInterface
   *   The called Swiper slide entity.
   */
  public function setClass($class);

  /**
   * Gets the Swiper slide background.
   *
   * @return string
   *   Background of the Swiper slide.
   */
  public function getBackground();

  /**
   * Sets the Swiper slide background.
   *
   * @param string $background
   *   The Swiper slide background.
   *
   * @return \Drupal\swiper_slider\Entity\SwiperSlideInterface
   *   The called Swiper slide entity.
   */
  public function setBackground($background);

  /**
   * Gets the Swiper slide content.
   *
   * @return string
   *   Content of the Swiper slide.
   */
  public function getContent();

  /**
   * Sets the Swiper slide content.
   *
   * @param string $content
   *   The Swiper slide content.
   *
   * @return \Drupal\swiper_slider\Entity\SwiperSlideInterface
   *   The called Swiper slide entity.
   */
  public function setContent($content);

  /**
   * Gets the Swiper slide creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Swiper slide.
   */
  public function getCreatedTime();

  /**
   * Sets the Swiper slide creation timestamp.
   *
   * @param int $timestamp
   *   The Swiper slide creation timestamp.
   *
   * @return \Drupal\swiper_slider\Entity\SwiperSlideInterface
   *   The called Swiper slide entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Swiper slide published status indicator.
   *
   * Unpublished Swiper slide are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Swiper slide is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Swiper slide.
   *
   * @param bool $published
   *   TRUE to set this Swiper slide to published, FALSE to unpublished.
   *
   * @return \Drupal\swiper_slider\Entity\SwiperSlideInterface
   *   The called Swiper slide entity.
   */
  public function setPublished($published);

}
