<?php

namespace Drupal\select2_widget\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use function is_array;
use function str_replace;
use function strpos;

/**
 * Plugin implementation of the 'select2_autocomplete_widget' widget.
 *
 * @FieldWidget(
 *   id = "select2_autocomplete_widget",
 *   label = @Translation("Select2 Autocomplete"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class Select2AutocompleteWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $entity = $items->getEntity();
    $target_type = $this->getFieldSetting('target_type');
    $field_name = $this->fieldDefinition->getFieldStorageDefinition()->getName();
    $selected_options = $this->getSelectedOptions($items);
    $selection_settings = $this->getFieldSetting('handler_settings');

    // If the form is submited add the newly added options to the allowed
    // options to prevent 'An illegal choice has been detected.' validation
    // error.
    $input = $form_state->getUserInput();
    if(!empty($input) && isset($input[$field_name]) && is_array($input[$field_name])){
      $field_input = array_flip($input[$field_name]);

      foreach ($field_input as $entity_id => $label) {
        if(!isset($selected_options[$entity_id])){
          if(is_numeric($entity_id)){
            $entity = \Drupal::entityTypeManager()->getStorage($target_type)->load($entity_id);
            if(isset($entity)) {
              $selected_options[$entity_id] = $entity->label();
            }
          }

          if(!isset($selected_options[$entity_id])) {
            $selected_options[$entity_id] = $entity_id;
          }
        }
      }

    }

    $element = [
      '#type' => 'select2_entity_autocomplete',
      '#target_type' => $target_type,
      '#selection_handler' => $this->getFieldSetting('handler'),
      '#selection_settings' => $selection_settings,
      // Entity reference field items are handling validation themselves via
      // the 'ValidReference' constraint.
      '#validate_reference' => FALSE,
      '#options' => $selected_options,
      '#default_value' => array_keys($selected_options),
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#multiple' => $this->multiple,
    ] + $element;


    if ($this->getSelectionHandlerSetting('auto_create') && ($bundle = $this->getAutocreateBundle())) {
      $element['#autocreate'] = array(
        'bundle' => $bundle,
        'uid' => ($entity instanceof EntityOwnerInterface) ? $entity->getOwnerId() : \Drupal::currentUser()->id()
      );
    }

    $class = 'select2-' . hash('md5', Html::getUniqueId('select2-' . $field_name));

    $select2_settings = [
      'selector' => '.' . $class,
      'field_name' => $field_name,
      'settings' => $this->getSettings(),
      'items' => []
    ];

    foreach ($selected_options as $entity_id => $label) {
      $entity = \Drupal::entityTypeManager()->getStorage($target_type)->load($entity_id);
      if(isset($entity)){
        $select2_settings['items'][$entity_id] = [
          'id' => $entity_id,
          'label' => $label,
          'status' => $entity->get('status')->value,
        ];
      }
    }

    $element['#attached']['drupalSettings']['select2'][$class] = $select2_settings;
    $element['#attributes']['class'][] = $class;
    $element['#attached']['library'][] = 'select2_widget/select2.widget';

    return $element;
  }

  public static function validateElement(array $element, FormStateInterface $form_state) {
    $target_type = $element['#target_type'];

    $element_value = array_reduce($element['#value'], function ($return, $item) use ($target_type) {
      if (is_numeric($item)) {
        $entity = \Drupal::entityTypeManager()->getStorage($target_type)->load($item);
        if(isset($entity)){
          $item = 'dummy term name (' . $item . ')';
        }
      }

      if(strpos($item, 'create:') === 0) {
        $item = str_replace('create:', '', $item);
      }

      $return[] = $item;

      return $return;
    });

    if(is_array($element_value)){
      $element['#value'] = Tags::implode($element_value);
      $complete_form = &$form_state->getCompleteForm();
      EntityAutocomplete::validateEntityAutocomplete($element, $form_state, $complete_form);
    }

  }

  protected function getOptions(FieldableEntityInterface $entity) {
    if (!isset($this->options)) {
      $this->options = [];
    }
    return $this->options;
  }

  protected function getSelectedOptions(FieldItemListInterface $items) {
    $selected_options = array();
    $referenced_entities = $items->referencedEntities();

    foreach ($referenced_entities as $delta => $referenced_entity) {
      $id = $referenced_entity->id();
      $label = $referenced_entity->label();
      $selected_options[$id] = $label;
    }

    return $selected_options;
  }

  /**
   * Returns the name of the bundle which will be used for autocreated entities.
   *
   * @return string
   *   The bundle name.
   */
  protected function getAutocreateBundle() {
    $bundle = NULL;
    $target_bundles = $this->getSelectionHandlerSetting('target_bundles');
    if ($this->getSelectionHandlerSetting('auto_create')) {
      // If there's only one target bundle, use it.
      if (is_array($target_bundles) && count($target_bundles) == 1) {
        $bundle = reset($target_bundles);
      }
      // Otherwise use the target bundle stored in selection handler settings.
      elseif (!$bundle = $this->getSelectionHandlerSetting('auto_create_bundle')) {
        // If no bundle has been set as auto create target means that there is
        // an inconsistency in entity reference field settings.
        trigger_error(sprintf(
          "The 'Create referenced entities if they don't already exist' option is enabled but a specific destination bundle is not set. You should re-visit and fix the settings of the '%s' (%s) field.",
          $this->fieldDefinition->getLabel(),
          $this->fieldDefinition->getName()
        ), E_USER_WARNING);
      }
    }

    return $bundle;
  }

  /**
   * Returns the value of a setting for the entity reference selection handler.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  protected function getSelectionHandlerSetting($setting_name) {
    $settings = $this->getFieldSetting('handler_settings');
    return isset($settings[$setting_name]) ? $settings[$setting_name] : NULL;
  }

}
