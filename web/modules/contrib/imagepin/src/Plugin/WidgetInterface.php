<?php

namespace Drupal\imagepin\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for widget plugins.
 */
interface WidgetInterface extends PluginInspectionInterface {

  /**
   * New widget element builder for the pin form.
   *
   * See \Drupal\imagepin\Form\PinWidgetsForm::buildForm()
   *
   * @param array &$form
   *   The currently array build for the pin form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The corresponding form state.
   *
   * @return array
   *   The form element of this widget.
   */
  public function formNewElement(array &$form, FormStateInterface $form_state);

  /**
   * View builder for the given widget value.
   *
   * @param mixed $value
   *   The widget value.
   *
   * @return array
   *   A renderable array.
   */
  public function viewContent($value);

  /**
   * Administrative preview builder for the given widget value.
   *
   * Used in the pin form.
   * See \Drupal\imagepin\Form\PinWidgetsForm::buildForm()
   *
   * @param mixed $value
   *   The widget value.
   *
   * @return array
   *   A renderable array.
   */
  public function previewContent($value);

  /**
   * View builder of the pin content for the given value.
   *
   * @param mixed $value
   *   The widget value.
   *
   * @return array
   *   A renderable array.
   */
  public function viewPinContent($value);

  /**
   * Prepares for saving the widget value in the database.
   *
   * @param mixed &$value
   *   The widget value. Because the value will be stored in the database,
   *   make sure the value is serializable.
   * @param array $belonging
   *   An array, whose keys define the belonging entity of this value.
   *   Consists of following keys:
   *     - entity_type: The entity type id as string.
   *     - bundle: The bundle of the entity.
   *     - entity_id: The entity id, can be NULL for new entities.
   *     - language: The language of the entity.
   *     - field_name: The name of the field,
   *                   where the corresponding image is being referenced.
   *     - image_fid: The file id of the corresponding image.
   *     - view_mode: The corresponding view mode of the entity.
   * @param int $key
   *   A given key indicates this value should update an existing widget record.
   */
  public function preSave(&$value, array $belonging, $key = NULL);

  /**
   * Get the position coordinates for the given widget value.
   *
   * @param mixed $value
   *   The widget value.
   *
   * @return array
   *   An array with the keys 'top' and 'left' as coordinates,
   *   as well as the keys 'image_width' and 'image_height'
   *   of the image the widget was attached to.
   *   Returns NULL if no coordinates and image properties
   *   are defined for the given value.
   */
  public function getPosition($value);

  /**
   * Sets the position for the given widget value.
   *
   * The position usually may be attached to the given $value.
   *
   * @param mixed &$value
   *   The widget value.
   * @param array $position
   *   The position information. Typically consists of 'top' and 'left'
   *   keys as fixed coordinates, as well as 'image_width' and 'image_height'
   *   of the image the widget was attached to. These keys are required
   *   for calculating the final pin position on the client display.
   */
  public function setPosition(&$value, array $position);

}
