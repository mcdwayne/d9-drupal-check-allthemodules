<?php

namespace Drupal\flexfield\Plugin;

use Drupal\flexfield\Plugin\Field\FieldType\FlexItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Flexfield Type plugins.
 */
interface FlexFieldTypeInterface extends PluginInspectionInterface {

  /**
   * Defines the widget settings for this plugin.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultWidgetSettings();

  /**
   * Defines the formatter settings for this plugin, if any.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultFormatterSettings();

  /**
   * Returns a form for the widget settings for this flexfield type.
   *
   * @param array $form
   *   The form where the settings form is being included in. Provided as a
   *   reference. Implementations of this method should return a new form
   *   element which will be inserted into the main settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   *
   * @return array
   *   The form definition for the widget settings.
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state);

  /**
   * Returns a form for the formatter settings for this flexfield type.
   *
   * @param array $form
   *   The form where the settings form is being included in. Provided as a
   *   reference. Implementations of this method should return a new form
   *   element which will be inserted into the main settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   *
   * @return array
   *   The form definition for the formatter settings.
   */
  public function formatterSettingsForm(array $form, FormStateInterface $form_state);

  /**
   * Returns the flexfield item widget as form array.
   *
   * Called from the Flexfield widget plugin formElement method.
   *
   * @see Drupal\Core\Field\WidgetInterface::formElement() for parameter descriptions
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state);

  /**
   * Render the stored value of the flexfield item.
   *
   * @param array $item
   *   A field
   *
   * @return string
   *   The value
   */
  public function value(FlexItem $item);

  /**
   * The label for the flexfield item.
   *
   * @return string
   */
  public function getLabel();

  /**
   * The machine name of the flexfield item.
   *
   * @return string
   */
  public function getName();

  /**
   * The widget settings for the flexfield item.
   *
   * @return array
   */
  public function getWidgetSettings();

  /**
   * The formatter settings for the flexfield item.
   *
   * @return array
   */
  public function getFormatterSettings();


}
