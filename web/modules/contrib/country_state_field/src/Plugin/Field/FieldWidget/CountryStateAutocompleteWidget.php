<?php

namespace Drupal\country_state_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\country_state_field\Entity\City;

/**
 * Plugin implementation of the 'country_state_autocomplete_widget' widget.
 *
 * @FieldWidget(
 *   id = "country_state_autocomplete_widget",
 *   label = @Translation("Country state autocomplete widget"),
 *   field_types = {
 *     "country_state_type"
 *   }
 * )
 */
class CountryStateAutocompleteWidget extends WidgetBase {

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
   * Undocumented function.
   *
   * @param int $city_id
   *   The id of the city.
   *
   * @return string
   *   The string with the Country, State and City.
   */
  protected function getLocationValue($city_id) {
    $city = City::load($city_id);
    $state = $city->getState();
    $country = $city->getCountry();

    $value = $country->getName() . ' (' . $country->id() . '),' . $state->getName() . ' (' . $state->id() . '),' . $city->getName() . ' (' . $city->id() . ')';

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = $this->t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];
    $value = $item->getEntity()->isNew() ? $this->getInitialValues() : $item->toArray();

    $is_new = FALSE;

    $field_name = $this->fieldDefinition->getName();
    if (isset($form_state->getUserInput()[$field_name])) {
      $user_input = $form_state->getUserInput()[$field_name][$delta]['locate'];

      $is_new = TRUE;

      $locate = [];
      $locate = explode(',', $user_input);

      // O valores que estão no array locate são os novos valores retornados do
      // calback do autocomplete.
      $new_country = (int) filter_var($locate[0], FILTER_SANITIZE_NUMBER_INT);
      $new_state = (int) filter_var($locate[1], FILTER_SANITIZE_NUMBER_INT);
      $new_city = (int) filter_var($locate[2], FILTER_SANITIZE_NUMBER_INT);
    }

    // Não não tiver nenhum novo valor retornado pelo autocomplete, atribui
    // os campos que já foram preenchidos, no caso da edição.
    $country = $is_new ? $new_country : $value['country'];
    $state = $is_new ? $new_state : $value['state'];
    $city = $is_new ? $new_city : $value['city'];

    $is_new = FALSE;

    // Traz o valor formatado do campo locate para exibir para usuário.
    $location = !empty($city) ? $this->getLocationValue($city) : NULL;

    $element['locate'] = [
      '#title' => $this->t('Localization'),
      '#type' => 'textfield',
      '#value' => $location,
      '#autocomplete_route_name' => 'country_state_field.autocomplete',
      '#autocomplete_route_parameters' => [],
    ];

    $element['country'] = [
      '#type' => 'hidden',
      '#value' => $country,
    ];

    $element['state'] = [
      '#type' => 'hidden',
      '#value' => $state,
    ];

    $element['city'] = [
      '#type' => 'hidden',
      '#value' => $city,
    ];

    return $element;
  }

}
