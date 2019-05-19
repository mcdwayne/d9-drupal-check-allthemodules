<?php

namespace Drupal\stacks;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Widget Instance entity entities.
 *
 * @ingroup stacks
 */
interface WidgetInstanceEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Widget Instance entity title.
   *
   * @return string
   *   Title of the Widget Instance entity.
   */
  public function getTitle();

  /**
   * Sets the Widget Instance entity title.
   *
   * @param string $title
   *   The Widget Instance entity title.
   *
   * @return \Drupal\stacks\WidgetInstanceEntityInterface
   *   The called Widget Instance entity entity.
   */
  public function setTitle($title);

  /**
   * Gets the Widget Instance entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Widget Instance entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Widget Instance entity creation timestamp.
   *
   * @param int $timestamp
   *   The Widget Instance entity creation timestamp.
   *
   * @return \Drupal\stacks\WidgetInstanceEntityInterface
   *   The called Widget Instance entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Widget Instance entity status.
   *
   * @return bool
   *   True if Widget Instance entity enabled. False if disabled.
   */
  public function getStatus();

  /**
   * Sets the Widget Instance entity status.
   *
   * @param bool $status
   *   True if Widget Instance entity enabled. False if disabled.
   *
   * @return \Drupal\stacks\WidgetInstanceEntityInterface
   *   The called Widget Instance entity entity.
   */
  public function setStatus($status);

  /**
   * Increments the widget_instance_times_used value for this entity.
   */
  public function triggerTimesUsed();

  /**
   * Returns the Widget Instance entity template value. This is the template
   * that this widget instance should display with.
   *
   * @return string
   */
  public function getTemplate();

  /**
   * Sets the template of a Widget Instance entity.
   *
   * @param string $template
   *   The template value.
   *
   * @return \Drupal\stacks\WidgetInstanceEntityInterface
   *   The called Widget Instance entity entity.
   */
  public function setTemplate($template);

  /**
   * Returns the Widget Instance entity theme value. This is the theme value
   * that is used for this widget instance
   *
   * @return string
   */
  public function getTheme();

  /**
   * Sets the theme of a Widget Instance entity.
   *
   * @param string $theme
   *   The theme value. This will be used like a CSS class.
   *
   * @return \Drupal\stacks\WidgetInstanceEntityInterface
   *   The called Widget Instance entity entity.
   */
  public function setTheme($theme);

  /**
   * Returns the Widget Instance entity theme value. This is the theme value
   * that is used for this widget instance
   *
   * @return string
   */
  public function getWrapperID();

  /**
   * Sets the wrapper_id of a Widget Instance entity.
   *
   * @param string $wrapper_id
   *   The wrapper id value.
   *
   * @return \Drupal\stacks\WidgetInstanceEntityInterface
   *   The called Widget Instance entity entity.
   */
  public function setWrapperID($wrapper_id);

  /**
   * Returns the Widget Instance entity theme value. This is the theme value
   * that is used for this widget instance
   *
   * @return string
   */
  public function getWrapperClasses();

  /**
   * Sets the wrapper_classes of a Widget Instance entity.
   *
   * @param string $wrapper_classes
   *   The wrapper classes value.
   *
   * @return \Drupal\stacks\WidgetInstanceEntityInterface
   *   The called Widget Instance entity entity.
   */
  public function setWrapperClasses($wrapper_classes);

  /**
   * Returns the Widget Instance entity enable sharing indicator.
   *
   * @return bool
   *   TRUE if the Widget Instance entity has sharing enabled.
   */
  public function isShareable();

  /**
   * Sets the enable_sharing of a Widget Instance entity.
   *
   * @param bool $enable_sharing
   *   TRUE to set this Widget Instance entity to be sharable, FALSE to set it to not be shareable.
   *
   * @return \Drupal\stacks\WidgetInstanceEntityInterface
   *   The called Widget Instance entity entity.
   */
  public function setEnableSharing($enable_sharing);

  /**
   * Returns the Widget Entity ID value. This is the stacks entity this instance
   * is attached to.
   *
   * @return int
   */
  public function getWidgetEntityID();

  /**
   * Sets the Widget Entity ID of the Widget Instance entity.
   *
   * @param int $widget_entity_id
   *   The stacks entity id value.
   *
   * @return \Drupal\stacks\WidgetInstanceEntityInterface
   *   The called Widget Instance entity entity.
   */
  public function setWidgetEntityID($widget_entity_id);

  /**
   * Returns the Widget Instance entity required indicator.
   *
   * @return bool
   *   TRUE if the Widget Instance entity is a required widget instance.
   */
  public function getIsRequired();

  /**
   * Sets the required status of a Widget Instance entity.
   *
   * @param bool $is_required
   *   TRUE to set this Widget Instance entity to be required,
   *
   * @return \Drupal\stacks\WidgetInstanceEntityInterface
   *   The called Widget Instance entity entity.
   */
  public function setIsRequired($is_required);

  /**
   * Returns the Widget Instance entity required type value.
   *
   * @return string
   */
  public function getRequiredType();

  /**
   * Sets the required type of a Widget Instance entity.
   *
   * @param string $required_type
   *
   * @return \Drupal\stacks\WidgetInstanceEntityInterface
   *   The called Widget Instance entity entity.
   */
  public function setRequiredType($required_type);

  /**
   * Returns the Widget Instance entity required type value.
   *
   * @return string
   */
  public function getRequiredBundle();

  /**
   * Sets the required bundle of a Widget Instance entity.
   *
   * @param string $required_bundle
   *
   * @return \Drupal\stacks\WidgetInstanceEntityInterface
   *   The called Widget Instance entity entity.
   */
  public function setRequiredBundle($required_bundle);

}
