<?php

namespace Drupal\select_text_value\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\select_text_value\WidgetHelper;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'select_string_textfield' widget.
 *
 * @FieldWidget(
 *   id = "select_string_textfield",
 *   label = @Translation("Select text value"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class SelectStringTextfieldWidget extends StringTextfieldWidget {

  /**
   * Common functionality shared between all widgets.
   *
   * @var WidgetHelper
   */
  protected $helper;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->helper = new WidgetHelper();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + WidgetHelper::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    return $this->helper->settingsForm($form, $this->getSettings());
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return array_merge(parent::settingsSummary(), $this->helper->settingsSummary($this->getSettings()));
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = [
      '#type' => 'container',
      'select' => $this->helper->createSelectField($this->getSettings(), $this->fieldDefinition->isRequired()),
      'field' => parent::formElement($items, $delta, $element, $form, $form_state)['value'],
    ];
    $element = $this->helper->setFormElementTitle($element, $this->getSettings());
    $element = $this->helper->setFormElementDescription($element, $this->getSettings());
    $element = $this->helper->setFormElementDefaultValues($items, $delta, $element, $this->getSettings());
    $element = $this->helper->handleFormElementRequired($element);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    $element = parent::afterBuild($element, $form_state);
    $element = WidgetHelper::stringFieldAssignStates($element);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return $this->helper->stringFieldMassageFormValues($values);
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return parent::errorElement($element, $violation, $form, $form_state);
  }

}
