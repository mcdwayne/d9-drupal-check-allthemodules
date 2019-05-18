<?php

namespace Drupal\entityreference_view_mode\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entityreference_view_mode\Plugin\Field\FieldType\EntityReferenceViewModeFieldType;


/**
 * Plugin implementation of the 'field_example_text' widget.
 *
 * @FieldWidget(
 *   id = "entityreference_view_mode_field_widget",
 *   module = "entityreference_view_mode",
 *   label = @Translation("Content plus view mode"),
 *   field_types = {
 *     "entityreference_view_mode_field_type"
 *   }
 * )
 */
class EntityReferenceViewModeFieldWidget extends WidgetBase {

  use EntityReferenceViewModeFieldWidgetTrait;

  /**
   * Return the available entity types.
   *
   * @return mixed
   *   Array of possible entity types.
   */
  public function availableEntityTypes() {

    // Selected types.
    $selected = $this->getFieldSetting('target_type') ? $this->getFieldSetting('target_type') : [];

    // Remove unselected values.
    $selected = array_filter($selected);

    // All the possible type.
    $all = \Drupal::entityManager()->getEntityTypeLabels(TRUE)['Content'];

    // Remove all but the selected types from $all.
    foreach ($all as $key => $value) {
      if (!isset($selected[$key])) {
        unset($all[$key]);
      }
    }

    // Generic alter function.
    \Drupal::moduleHandler()->alter('entityreference_view_mode_types', $all);

    return $all;
  }

  /**
   * Return the available view modes for a given target_type and bundle.
   *
   * @param $target_type
   *   Target Type.
   * @param $bundle
   *   Bundle.
   *
   * @return array
   *   Available view modes.
   */
  public function availableViewModes($target_type, $bundle) {

    // If there is no bundle present then there can't be any available view modes yet.
    if (!$bundle) {
      return [];
    }

    $settings = $this->getFieldSetting('settings') ? $this->getFieldSetting('settings') : [];

    // The available view modes for the current target type.
    $available_view_modes = array_filter($settings[$target_type]['view_modes']);

    // All the possible view modes used as a lookup for the labels.
    $all_view_modes = entity_load_multiple('entity_view_mode');

    $view_modes = [];
    foreach ($available_view_modes as $key) {
      $view_modes[$target_type . '.' . $key] = $all_view_modes[$target_type . '.' . $key]->label();
    }

    // If no view modes have been selected then return them all!
    if (empty($view_modes)) {
        $view_modes = EntityReferenceViewModeFieldType::availableViewModes($target_type);
    }
      
      // Generic alter function.
    \Drupal::moduleHandler()
      ->alter('entityreference_view_mode_modes', $view_modes, $target_type, $bundle);

    // Type and bundle specific alters.
    \Drupal::moduleHandler()
      ->alter('entityreference_view_mode_modes_' . $target_type . '_' . $bundle, $view_modes, $target_type, $bundle);

    return $view_modes;
  }

  /**
   * Return the available bundles for the selected target type.
   *
   * @param $target_type
   *   Target type.
   *
   * @return array
   *   Available bundles.
   */
  public function availableBundles($target_type) {

    $selected = $this->getFieldSetting('settings') ? $this->getFieldSetting('settings') : [];

    $bundles = array_filter($selected[$target_type]['bundles']);

    // If no bundles have been selected then return them all!
    if (empty($bundles)) {
        $bundles = EntityReferenceViewModeFieldType::availableBundles($target_type);
    }

    // Generic alter function.
    \Drupal::moduleHandler()
      ->alter('entityreference_view_mode_bundles', $bundles, $target_type);

    // Type specific alter.
    \Drupal::moduleHandler()
      ->alter('entityreference_view_mode_modes_' . $target_type, $bundles, $target_type);

    return $bundles;
  }


  /**
   * Update options.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Formstate.
   *
   * @return mixed
   *   Returns the updated options.
   */
  public static function updateWidgetOptions($form, FormStateInterface &$form_state) {

    $trigger = $form_state->getTriggeringElement();
    $parents = $trigger['#array_parents'];
    unset($form[$parents[0]][$parents[1]][$parents[2]]['_weight']);

    array_pop($parents);

    $result = NestedArray::getValue($form, $parents, $key_exists);
    unset($result['_weight']);

    return $result;
  }


  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // Generate a unique ID for the field wrapper.
    $id = Html::getUniqueId('entityreference_view_mode');

    $selections = $this->getSelections($items, $delta, $element, $form, $form_state);

    // Ajax wrapper opening markup.
    $element['prefix'] = [
      '#markup' => '<div id="' . $id . '" class="container-inline">',
    ];

    $element['target_type'] = [
      '#type' => 'select',
      '#default_value' => $selections['target_type'] ,
      '#options' => $this->availableEntityTypes(),
      '#ajax' => [
        'callback' => [$this, 'updateWidgetOptions'],
        'wrapper' => $id,
        'method' => 'replace',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $element['content'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => $selections['target_type'] ? $selections['target_type'] : 'node',
      '#title' => t('Content'),
      '#title_display' => 'hidden',
      '#default_value' => $selections['content'],
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => $this->availableBundles($selections['target_type']),
      ],
      '#validate_reference' => FALSE,
      // @todo would prefer not to do this but the validation gets mixed up.
      '#ajax' => [
        'callback' => [$this, 'updateWidgetOptions'],
        'wrapper' => $id,
        'method' => 'replace',
        'event' => 'autocomplete-select',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $view_modes = $this->availableViewModes($selections['target_type'], $selections['bundle']);
    $element['view_mode'] = [
      '#type' => 'select',
      '#title' => 'View Mode',
      '#title_display' => 'hidden',
      '#default_value' => $selections['view_mode'],
      '#options' => $view_modes,
      '#access' => !empty($view_modes) ? TRUE : FALSE,
      '#validated' => TRUE,
    ];

    // Ajax wrapper closing markup.
    $element['suffix'] = [
      '#markup' => '</div>',
      '#weight' => 99999999,
    ];

    // Attach the javascript library.
    $element['#attached']['library'][] = 'entityreference_view_mode/entityreference-view-autocomplete';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateFormElement($element, FormStateInterface $form_state) {
    $test = 1;
  }

}
