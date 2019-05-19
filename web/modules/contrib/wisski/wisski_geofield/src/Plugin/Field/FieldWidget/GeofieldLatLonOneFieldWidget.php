<?php

/**
 * @file
 * Contains \Drupal\wisski_geofield\Plugin\Field\FieldWidget\GeofieldLatLonOneFieldWidget.
 */

namespace Drupal\wisski_geofield\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'geofield_latlon' widget.
 *
 * @FieldWidget(
 *   id = "geofield_onefieldlatlon",
 *   label = @Translation("Latitude, Longitude in one field"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class GeofieldLatLonOneFieldWidget extends WidgetBase {

  /**
   * Lat Lon widget components.
   *
   * @var array
   */
  public $components = ['lon', 'lat'];

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'html5_geolocation' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['html5_geolocation'] = [
      '#type' => 'checkbox',
      '#title' => 'Use HTML5 Geolocation to set default values',
      '#default_value' => $this->getSetting('html5_geolocation'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      $this->t('HTML5 Geolocation button is @state', ['@state' => $this->getSetting('html5_geolocation') ? $this->t('enabled') : $this->t('disabled')])
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    foreach ($this->components as $component) {
      $latlon_value[$component] = isset($items[$delta]->{$component}) ? floatval($items[$delta]->{$component}) : '';
    }
    
    $lat = $latlon_value['lat'];
    $lon = $latlon_value['lon'];

    $element += [
      '#type' => 'textfield',
      '#default_value' => empty($latlon_value) ? '' : $lat . ', ' . $lon,
      '#geolocation' => $this->getSetting('html5_geolocation'),
      '#error_label' => !empty($element['#title']) ? $element['#title'] : $this->fieldDefinition->getLabel(),
    ];

    return ['value' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $value) {

      $explode = explode(",", $value['value']);

      $lat = (double)(trim($explode[0]));
      $lon = (double)(trim($explode[1]));
      
      $values[$delta]['value'] = \Drupal::service('geofield.wkt_generator')->WktBuildPoint([$lon, $lat]);

    }

    return $values;
  }

}
