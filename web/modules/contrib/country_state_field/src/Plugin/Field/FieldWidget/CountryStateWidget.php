<?php

namespace Drupal\country_state_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'country_state_widget' widget.
 *
 * @FieldWidget(
 *   id = "country_state_widget",
 *   label = @Translation("Country state widget"),
 *   field_types = {
 *     "country_state_type"
 *   }
 * )
 */
class CountryStateWidget extends WidgetBase {


  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 60,
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * Gets the initial values for the widget.
   *
   * This is a replacement for the disabled default values functionality.
   *
   * @return array
   *   The initial values, keyed by property.
   */
  protected function getInitialValues() {
    $initial_values = [
      'country' => '',
      'state' => '',
      'city' => '',
    ];

    return $initial_values;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getStates($country_id) {
    if ($country_id) {
      $query = \Drupal::entityQuery('state')
        ->condition('country_id', $country_id)
        ->sort('name', 'asc');

      $ids = $query->execute();

      $states = [];
      if (count($ids) == 1) {
        $result = entity_load('state', key($ids));
        $states[$result->id()] = $result->getName();
      }
      elseif (count($ids) > 1) {
        $results = entity_load_multiple('state', $ids);
        foreach ($results as $result) {
          $states[$result->id()] = $result->getName();
        }
      }

      return $states;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCities($state_id) {
    if ($state_id) {
      $query = \Drupal::entityQuery('city')
        ->condition('state_id', $state_id)
        ->sort('name', 'asc');

      $ids = $query->execute();

      $cities = [];
      if (count($ids) == 1) {
        $result = entity_load('city', key($ids));
        $cities[$result->id()] = $result->getName();
      }
      elseif (count($ids) > 1) {
        $results = entity_load_multiple('city', $ids);
        foreach ($results as $result) {
          $cities[$result->id()] = $result->getName();
        }
      }

      return $cities;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];
    $value = $item->getEntity()->isNew() ? $this->getInitialValues() : $item->toArray();

    $field_name = $this->fieldDefinition->getName();

    if (isset($form_state->getUserInput()[$field_name])) {
      $country_id = $form_state->getUserInput()[$field_name][$delta]['country'];
      $state_id = $form_state->getUserInput()[$field_name][$delta]['state'];
      $city_id = $form_state->getUserInput()[$field_name][$delta]['city'];
    }

    $country_id = $country_id ?? $value['country'] ?? NULL;
    $state_id = $state_id ?? $value['state'] ?? NULL;
    $city_id = $city_id ?? $value['city'] ?? NULL;

    $query = \Drupal::entityQuery('country')
      ->sort('name', 'asc');

    $ids = $query->execute();

    $countries = [];
    if (count($ids) == 1) {
      $result = entity_load('country', key($ids));
      $countries[$result->id()] = $result->getName();
    }
    elseif (count($ids) > 1) {
      $results = entity_load_multiple('country', $ids);
      foreach ($results as $result) {
        $countries[$result->id()] = $result->getName();
      }
    }

    $div_id = 'state-wrapper-' . $field_name . '-' . $delta;

    $element['state_wrapper1']['#prefix'] = '<div id="' . $div_id . '">';
    $element['state_wrapper1']['#markup'] = '';

    $element['country'] = [
      '#type' => 'select',
      '#options' => $countries,
      '#default_value' => $country_id,
      '#empty_option' => t('-- Select an option --'),
      '#required' => $this->fieldDefinition->isRequired(),
      '#title' => $this->t('Country'),
      '#delta' => $delta,
      '#ajax' => [
        'callback' => [$this, 'ajaxFillState'],
        'event' => 'change',
        'wrapper' => $div_id,
        'progress' => [
          'type' => 'throbber',
          'message' => t('Searching states...'),
        ],
      ],
    ];

    if ($country_id) {
      $element['state'] = [
        '#type' => 'select',
        '#default_value' => $state_id,
        '#options' => $this->getStates($country_id),
        '#empty_option' => t('-- Select an option --'),
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $this->t('State'),
        '#active' => FALSE,
        '#delta' => $delta,
        '#ajax' => [
          'callback' => [$this, 'ajaxFillState'],
          'event' => 'change',
          'wrapper' => $div_id,
          'progress' => [
            'type' => 'throbber',
            'message' => t('Searching cities...'),
          ],
        ],
      ];
    }

    if ($state_id) {
      $element['city'] = [
        '#type' => 'select',
        '#default_value' => $city_id,
        '#options' => $this->getCities($state_id),
        '#empty_option' => t('-- Select an option --'),
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $this->t('City'),
      ];
    }

    $element['state_wrapper2']['#suffix'] = '</div>';
    $element['state_wrapper2']['#markup'] = '';
    return $element;
  }

  /**
   * Call the function that consume the webservice.
   *
   * @param array $form
   *   A form that be modified.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The values of the form.
   *
   * @return array
   *   The form modified
   */
  public function ajaxFillState(array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $delta = $element['#delta'];

    $field_name = $this->fieldDefinition->getName();
    $form = $form[$field_name];

    unset($form['widget'][$delta]['_weight']);

    return $form['widget'][$delta];
  }

}
