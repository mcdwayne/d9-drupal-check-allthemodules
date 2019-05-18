<?php

namespace Drupal\form_element_states\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\form_element_states\FormElementStates;


/**
 * Plugin implementation of the 'SelectOptionsWidget'.
 *
 * @FieldWidget(
 *   id = "form_element_states_option",
 *   label = @Translation("Select list with states"),
 *   field_types = {
 *     "entity_reference",
 *     "list_integer",
 *     "list_float",
 *     "list_string"
 *   }
 * )
 */
class SelectOptionsWidget extends  OptionsSelectWidget{


  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'form_element_states' => []
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element = FormElementStates::settingsFrom($element,$this->getSetting('form_element_states'));
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Write every state properties in one line');
    return $summary;
  }


  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#states']  = FormElementStates::prepareStateProperties($this->getSetting('form_element_states'));
    return $element;
  }

}
