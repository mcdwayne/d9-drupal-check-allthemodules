<?php

namespace Drupal\geolocation_street_view\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\geolocation\GoogleMapsDisplayTrait;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'geolocation_street_view' formatter.
 *
 * @FieldFormatter(
 *   id = "geolocation_street_view",
 *   label = @Translation("Geolocation Street View"),
 *   field_types = {
 *     "geolocation"
 *   }
 * )
 */
class StreetViewFormatter extends FormatterBase {

  use GoogleMapsDisplayTrait {
    getGoogleMapDefaultSettings as originalGoogleMapDefaultSettings;
  }

  /**
   * {@inheritdoc}
   */
  public static function getGoogleMapDefaultSettings() {
    $settings = self::originalGoogleMapDefaultSettings();
    $settings['google_map_settings'] += [
      'addressControl' => TRUE,
      'enableCloseButton' => FALSE,
    ];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + self::getGoogleMapDefaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function mergeDefaults() {
    parent::mergeDefaults();
    // Merge the options in the Google Map settings as well.
    $defaults = static::defaultSettings();
    $this->settings['google_map_settings'] += $defaults['google_map_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $form_prefix = 'fields][' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][';

    $element = $this->getGoogleMapsSettingsForm($settings, $form_prefix);

    $element['google_map_settings']['street_view_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Street View'),
    ];
    $element['google_map_settings']['addressControl'] = [
      '#group' => $form_prefix . 'google_map_settings][street_view_settings',
      '#type' => 'checkbox',
      '#title' => $this->t('Enable a textual overlay with the address of the location.'),
      '#default_value' => $settings['google_map_settings']['addressControl'],
    ];
    $element['google_map_settings']['enableCloseButton'] = [
      '#group' => $form_prefix . 'google_map_settings][street_view_settings',
      '#type' => 'checkbox',
      '#title' => $this->t('Allow the user to close Street View and return to the map.'),
      '#default_value' => $settings['google_map_settings']['enableCloseButton'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $summary = $this->getGoogleMapsSettingsSummary($settings);

    $summary[] = $this->t('Textual address overlay: @address_control', ['@address_control' => $settings['google_map_settings']['addressControl'] ? $this->t('Yes') : $this->t('No')]);
    $summary[] = $this->t('Allow to close Street View: @enable_close_button', ['@enable_close_button' => $settings['google_map_settings']['enableCloseButton'] ? $this->t('Yes') : $this->t('No')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $google_maps_url = $this->getGoogleMapsApiUrl();
    $map_settings = $this->getGoogleMapsSettings($this->getSettings());

    foreach ($items as $delta => $item) {
      $unique_id = uniqid('map-canvas-');

      // Render Street View formatter.
      $elements[$delta] = [
        '#theme' => 'geolocation_street_view_formatter',
        '#uniqueid' => $unique_id,
        '#latitude' => $item->lat,
        '#longitude' => $item->lng,
        '#attached' => [
          'library' => ['geolocation_street_view/formatter.street_view'],
          'drupalSettings' => [
            'geolocation' => [
              'google_map_url' => $google_maps_url,
              'maps' => [$unique_id => ['settings' => $map_settings]],
            ],
          ],
        ],
      ];

      // Add Street View POV variables.
      if (!empty($item->data['google_street_view_pov'])) {
        $street_view_pov = $item->data['google_street_view_pov'];
        $elements[$delta] += [
          '#heading' => $street_view_pov['heading'],
          '#pitch' => $street_view_pov['pitch'],
          '#zoom' => $street_view_pov['zoom'],
        ];
      }
    }

    return $elements;
  }

}
