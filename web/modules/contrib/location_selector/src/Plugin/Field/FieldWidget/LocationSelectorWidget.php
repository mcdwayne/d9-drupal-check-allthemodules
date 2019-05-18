<?php

namespace Drupal\location_selector\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\WidgetBase;

/**
 * Plugin implementation of the 'location_selector' widget.
 *
 * @FieldWidget(
 *   id = "location_selector_widget",
 *   label = @Translation("Location Selector"),
 *   field_types = {
 *     "location_selector_type"
 *   },
 * )
 */
class LocationSelectorWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings_default = [
      'force_deepest' => FALSE,
      'save_last' => FALSE,
      'limit_level' => 0,
      'basic_parent_id' => '6295630',
      'parent_include' => FALSE,
    ];
    return $settings_default + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['force_deepest'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force selection of deepest level'),
      '#default_value' => $this->getSetting('force_deepest'),
      '#description' => $this->t('Force users to select terms from the deepest level.'),
    ];
    $element['save_last'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Save only the last selected GeoNames ID'),
      '#default_value' => $this->getSetting('save_last'),
      '#description' => $this->t('Save only the last selected GeoNames ID and not the parents.'),
    ];
    $element['limit_level'] = [
      '#type' => 'number',
      '#title' => $this->t('Children Level limitation'),
      '#default_value' => $this->getSetting('limit_level'),
      '#description' => $this->t('Choose how many children levels you want to display. Set 0 for no limitation.'),
      '#min' => 0,
      '#max' => $this->maxLevel,
      '#step' => 1,
      '#required' => TRUE,
    ];
    $element['basic_parent_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Basic Parent ID'),
      '#default_value' => $this->getSetting('basic_parent_id'),
      '#description' => $this->t('Go to geonames.org and search for your wanted parent location. 6295630 e.g. is the ID of the Earth.'),
      '#size' => 20,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];
    $element['parent_include'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include the Basic Parent ID data'),
      '#default_value' => $this->getSetting('parent_include'),
      '#description' => $this->t('Display also the Basic Parent ID data in the first select list.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($this->getSetting('force_deepest')) {
      $summary[] = $this->t('Force selection of deepest level');
    }
    else {
      $summary[] = $this->t('Do not force selection of deepest level');
    }
    if ($this->getSetting('save_last')) {
      $summary[] = $this->t('Saving only the last selected GeoNames ID');
    }
    else {
      $summary[] = $this->t("Saving all selected GeoNames ID's");
    }
    if (!empty($levels = $this->getSetting('limit_level'))) {
      $summary[] = $this->t('Children levels: @levels.', ['@levels' => $levels]);
    }
    else {
      $summary[] = $this->t('Children levels: @levels.', ['@levels' => 'no limitations']);
    }
    if (!empty($basic_parent_id = $this->getSetting('basic_parent_id'))) {
      $summary[] = $this->t('Basic Parent ID: @basic_parent_id.', ['@basic_parent_id' => $basic_parent_id]);
    }
    if ($this->getSetting('parent_include')) {
      $summary[] = $this->t('Include the Basic Parent ID data.');
    }
    else {
      $summary[] = $this->t("Not including Basic Parent ID data.");
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // Create individual ID for this field.
    $element_unique_id = $this->fieldDefinition->getUniqueIdentifier();
    $element_ids = [
      'id' => $element_unique_id . '-ls',
      'id_ajax' => $element_unique_id . '-ls-ajax',
    ];

    $wrapper_class = 'ls--field--type-location-selector-type';
    $form_type = 'ls--entity-form';

    $widget_default_values = $items[$delta]->value ?: NULL;
    if (!empty($widget_default_values)) {
      // Save the value to the session.
      // @see \Drupal\location_selector\LocationSelectorController::validateGeoNames
      // Because on submit, the method formElement is called again,
      // so check if the ajax callback has already been made.
      $session = \Drupal::request()->getSession();
      if (!$session->get($element_ids['id_ajax'])) {
        $session->set($element_ids['id'], $widget_default_values);
      }
    }

    $element['value'] = $element + [
      '#type' => 'textarea',
      '#default_value' => $items[$delta]->value,
      '#prefix' => '<div class="' . $wrapper_class . ' ' . $form_type . '">',
      '#suffix' => '</div>',
      '#rows' => 5,
      '#attributes' => [
        'class' => [
          'js-text-full',
          'text-full',
        ],
        'readonly' => 'readonly',
      ],
    ];

    // Set the ids for using in validate function.
    $element['#ls_ids'] = $element_ids;

    // Get the form element settings.
    $formElementSettings = $this->getSettings();

    if (empty($element['#attributes'])) {
      $element['#attributes'] = [];
    }
    $element['#attributes'] = array_merge($element['#attributes'], [
      'class' => ['location-selector__enabled'],
    ]);
    if (empty($element['#attached'])) {
      $element['#attached'] = [];
    }
    // Change default values if user has changed the input.
    // Because on validate function it happens that an error occur
    // and then the value must be from the user.
    if ($user_input = $form_state->getUserInput()) {
      $widget_default_values = $user_input['field_location_selector'][$delta]['value'];
    }
    $element['#attached'] = array_merge($element['#attached'], [
      'library' => ['location_selector/location_selector.form'],
      'drupalSettings' => [
        'location_selector' => [
          'form_element_settings' => $formElementSettings,
          'form_element_default_values' => $widget_default_values,
          'form_element_ids' => $element_ids,
          'form_type' => $form_type,
          'form_wrapper_class' => $wrapper_class,
        ],
      ],
    ]);
    $element['#element_validate'][] = [get_called_class(), 'validateJsonStructure'];
    return $element;
  }

  /**
   * Validates the input to see if it is a properly formatted JSON object.
   *
   * If not, PgSQL will throw fatal errors upon insert.
   *
   * @param array $element
   *   The element array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $form
   *   The form array.
   */
  public static function validateJsonStructure(array &$element, FormStateInterface $form_state, array $form) {
    $session = \Drupal::request()->getSession();

    // @todo: check separate limit level without force deepest option.
    $settings = $element['#attached']['drupalSettings']['location_selector']['form_element_settings'];

    // Check if it's json.
    if (mb_strlen($element['value']['#value'])) {
      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setError($element['value'], t('The field %name must contain a valid JSON object.', ['%name' => $element['value']['#title']]));
      }
      // Prevent value manipulation.
      $value = $element['value']['#value'];
      $session_value = $session->get($element['#ls_ids']['id']);
      if ($session_value !== $value) {
        $form_state->setError($element['value'], t('It is not allowed to change the JSON object of the field %name.', ['%name' => $element['value']['#title']]));
      }
    }

    // Check for the foce_deepest setting.
    if ($settings['force_deepest']) {
      if (empty($form_state->getErrors())) {

        $last_selected_values = json_decode($element['value']['#value'], TRUE);
        $last_selected_path = $last_selected_values['path'];

        // Because:
        // @see https://stackoverflow.com/questions/48382457/mysql-json-column-change-array-order-after-saving
        if (is_array($last_selected_path)) {
          ksort($last_selected_path);
          $last_selected_value = end($last_selected_path);
          $last_selected_value = [['children' => $last_selected_value['val']]];
          $geonames_service = \Drupal::service('location_selector.geonames');
          // Check limit level too.
          if ($settings['limit_level']) {
            $current_level = max(array_keys($last_selected_path));
            if ($current_level < $settings['limit_level']) {
              $data = $geonames_service->getGeoNamesAndIds($last_selected_value);
            }
          }
          else {
            $data = $geonames_service->getGeoNamesAndIds($last_selected_value);
          }
          if (!empty($data)) {
            $form_state->setError($element['value'], t('You must choose the deepest level of the field %name.', ['%name' => $element['value']['#title']]));
          }
        }
      }
    }

    if (empty($form_state->getErrors())) {
      // If no errors appear, delete the session.
      $session->remove($element['#ls_ids']['id_ajax']);
    }
  }

}
