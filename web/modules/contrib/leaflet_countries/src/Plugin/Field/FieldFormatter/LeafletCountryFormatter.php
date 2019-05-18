<?php

namespace Drupal\leaflet_countries\Plugin\Field\FieldFormatter;

use Drupal\leaflet\Plugin\Field\FieldFormatter\LeafletDefaultFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\leaflet\Plugin\Field\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'leaflet_country' formatter.
 *
 * @FieldFormatter(
 *   id = "leaflet_country",
 *   label = @Translation("Leaflet country map"),
 *   field_types = {
 *     "leaflet_country_item"
 *   }
 * )
 */
class LeafletCountryFormatter extends LeafletDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + array(
        'linecolor' => '666666',
        'lineweight' => '1.5',
        'fillopacity' => '0.5',
        'fillcolor' => '666666',
        'zoom' => 'auto'
      );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    // Modify the zoom options to have an 'auto' option.
    $elements['zoom']['#options'] += array('auto' => 'Auto');

    // The outline of a country.
    $elements['linecolor'] = array(
      '#type' => 'textfield',
      '#title' => 'Outline color',
      '#description' => $this->t('Enter a hex value for the outline colour.'),
      '#field_prefix' => '#',
      '#size' => 6,
      '#default_value' => $this->getSetting('linecolor'),
      '#empty_value' => '666666',
      '#required' => TRUE,
    );

    // The line weight of the line surrounding the country.
    $elements['lineweight'] = array(
      '#type' => 'textfield',
      '#title' => 'Weight of the outline',
      '#description' => $this->t('Enter a value like 1 or 1.5'),
      '#size' => 6,
      '#default_value' => $this->getSetting('lineweight'),
      '#empty_value' => '1.5',
      '#required' => TRUE,
    );

    // The hex value for the fill colour.
    $elements['fillcolor'] = array(
      '#type' => 'textfield',
      '#title' => 'Fill color',
      '#description' => $this->t('Enter a hex value for the fill colour of a country'),
      '#field_prefix' => '#',
      '#size' => 6,
      '#default_value' => $this->getSetting('fillcolor'),
      '#empty_value' => '666666',
      '#required' => TRUE,
    );

    // The opacity value for the fill.
    $elements['fillopacity'] = array(
      '#type' => 'textfield',
      '#title' => 'Fill opacity',
      '#description' => $this->t('Enter an opacity value from 0 to 1.'),
      '#size' => 6,
      '#default_value' => $this->getSetting('fillopacity'),
      '#empty_value' => '1',
      '#required' => TRUE,
    );

    return $elements;

  }

  /**
   * {@inheritdoc}
   *
   * This function is called from parent::view().
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();
    $icon_url = $settings['icon']['icon_url'];

    $map = leaflet_map_get_info($settings['leaflet_map']);
    $map['settings']['zoom'] = isset($settings['zoom']) ? $settings['zoom'] != 'auto' ? $settings['zoom'] : NULL : NULL;
    $map['settings']['minZoom'] = isset($settings['minZoom']) ? $settings['minZoom'] : NULL;
    $map['settings']['maxZoom'] = isset($settings['zoom']) ? $settings['maxZoom'] : NULL;

    $elements = array();
    foreach ($items as $delta => $item) {
      // Load the GeoJSON file.
      $data = json_decode(\Drupal\leaflet_countries\Countries::getIndividualCountryJSON($item->value), TRUE);
      $feature = array(
        'type' => 'topojson',
        'json' => $data,
      );
      $feature['options'] = array(
        'color' => isset($settings['lineopacity']) ? '#' . $settings['linecolor'] : '#666666',
        'weight' => isset($settings['lineweight']) ? $settings['lineweight'] : '1.5',
        'lineOpacity' => isset($settings['lineopacity']) ? $settings['lineopacity'] : '1',
        'fillColor' => isset($settings['fillcolor']) ? '#' . $settings['fillcolor'] : '#666666',
        'fillOpacity' => isset($settings['fillopacity']) ? $settings['fillopacity'] : '1',
      );

      // If only a single feature, set the popup content to the entity title.
      if ($settings['popup'] && count($items) == 1) {
        $feature['popup'] = $items->getEntity()->label();
      }
      if (!empty($icon_url)) {
        foreach ($feature as $key => $feature) {
          $feature[$key]['icon'] = $settings['icon'];
        }
      }
      $elements[$delta] = leaflet_render_map($map, array($feature), $settings['height'] . 'px');
      // Add the topojson library and the leaflet countries javascript.
      $elements[$delta]['#attached']['library'][] = 'leaflet_topojson/leaflet-topojson';
      $elements[$delta]['#attached']['library'][] = 'leaflet_countries/leaflet-countries';
    }
    return $elements;
  }

}
