<?php

namespace Drupal\google_place_autocomplete\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;

/**
 * Plugin implementation of the 'field_example_text' widget.
 *
 * @FieldWidget(
 *   id = "google_place_autocomplete",
 *   module = "google_place_autocomplete",
 *   label = @Translation("Place Autocomplete"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class PlaceAutocompleteWidget extends WidgetBase implements ContainerFactoryPluginInterface {
  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs Field object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\State\StateInterface $state
   *   State Key/Value Object.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, StateInterface $state) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $countryCode = $this->state->get('place_country');
    $apiKey = $this->state->get('place_api_key');

    $element['value'] = $element + [
      '#type' => 'textfield',
      '#size' => 64,
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#attributes' => ['id' => ['popaddr']],
    ];

    $googleMapKey = [
      '#tag' => 'script',
      '#attributes' => ['src' => '//maps.googleapis.com/maps/api/js?key=' . $apiKey . '&sensor=true&libraries=places'],
    ];
    $element['#attached']['html_head'][] = [$googleMapKey, 'googleMapKey'];
    $element['#attached']['drupalSettings']['place']['autocomplete'] = $countryCode;
    $element['#attached']['library'][] = 'google_place_autocomplete/google_place_autocomplete.location';
    return $element;
  }

}
