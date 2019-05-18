<?php

namespace Drupal\gpx_track_elevation\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'gpx_track_elevation_field_simple_text' formatter.
 *
 * @FieldFormatter(
 *   id = "gpx_track_elevation_field_forfile",
 *   module = "gpx_track_elevation",
 *   label = @Translation("GPX Elevation Formatter"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class GpxFileFormatter extends GenericFileFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    //<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAMXz2ee2NPOimPfY6c3roMTHTdHG4q_m8&amp;libraries=geometry&amp;ojnomc"></script>
    /*$elements[0] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => 'accommodation',
      ),
      '#states' => array(
        'invisible' => array(
          'input[name="needs_accommodation"]' => array('checked' => FALSE),
        ),
      ),
    );*/
    $config = \Drupal::config('gpx_track_elevation.settings');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $item) {
      $elements[$delta] = array(
        'gpxtrele-container' => array(
          '#type' => 'container',
          '#attributes' => array(
            'id' => array('gpxtrele-container-'.$items->getEntity()->id()),
            'class' => array('gpxtrele-container'),
          ),
          'map-canvas' => array(
            '#type' => 'container',
            '#attributes' => array(
              'class' => array('map-canvas'),
            ),
          ),
          'elevation-canvas' => array(
            '#type' => 'container',
            '#attributes' => array(
              'class' => array('elevation_chart'),
            ),
          ),
          /*'#attached' => array(
            'library' => array(
              'gpx_track_elevation/gpxtrele',
            ),
          ),*/
        ),
      );
      $data_for_js['id-'.$items->getEntity()->id()][] = !is_null($this->getSetting('gpx_start_point'))?$this->getSetting('gpx_start_point'):1;
      $data_for_js['id-'.$items->getEntity()->id()][] = !is_null($this->getSetting('gpx_end_point'))?$this->getSetting('gpx_end_point'):1;
      $data_for_js['id-'.$items->getEntity()->id()][] = $this->get_gpx_info(file_create_url($item->getFileUri()));
      
      if (is_null($data_for_js['id-'.$items->getEntity()->id()][2])) {
        drupal_set_message(t('Error reading the GPX file'));
        \Drupal::logger('gpx_track_elevation')->error(t('Error reading the GPX file'));
        return parent::viewElements($items, $langcode);
      }

      $data_for_wpt = [];
	  $query = \Drupal::database()->select('gpx_track_elevation', 'w');
      foreach ($query->fields('w')->execute()->fetchAll() as $wpt_type) {
        $data_for_wpt[$wpt_type->type] = Html::escape($wpt_type->url);
      }

      
      $elements[$delta]['#attached']= array(
        'library' => array(
          'gpx_track_elevation/gpx_track_elevation.gpxtrele',
          'gpx_track_elevation/gpx_track_elevation.jsapi',
          //'gpx_track_elevation/gmaps',
        ),
        'drupalSettings' => array (
          'gpx_track_elevation' => array (
            'trColor' => $config->get('gpx_track_elevation.trcolor'),
            'epColor' => $config->get('gpx_track_elevation.epcolor'),
            'trstroke' => $config->get('gpx_track_elevation.trstroke'),
            'maptype' => $config->get('gpx_track_elevation.maptype'),
            'enableBilink' => $config->get('gpx_track_elevation.bilink'),
            'trStartPoint' => $config->get('gpx_track_elevation.start_url'),
            'trEndPoint' => $config->get('gpx_track_elevation.end_url'),
            'trLastPoint' => $config->get('gpx_track_elevation.last_url'),
            'parDistance' => t('Distance'),
            'parElevation' => t('Elevation'),
            'parAsl' => t('asl'),
            'parLengthUnit' => t('m'),
            'points' => $data_for_js,
            'wpt_types' => $data_for_wpt,
            'google_map_url' => $config->get('gpx_track_elevation.http').'://maps.googleapis.com/maps/api/js?key='.$config->get('gpx_track_elevation.google_map_key').'&libraries=geometry',
          ),
        ),
      );
    }

    $elements = array_merge($elements, parent::viewElements($items, $langcode));
    
    return $elements;
  }
  
  private function get_gpx_info($file) {
    $waypoints = array();
    $points_array = array();
    $use_errors = libxml_use_internal_errors(TRUE);
    $xml = simplexml_load_file($file);
    if (!$xml) {
      return NULL;
    }
    libxml_clear_errors();
    libxml_use_internal_errors($use_errors);

    // Each trk tag will be in a different elevation profiles.
    foreach ($xml->trk as $track) {
      $track_name = Html::escape((string) $track->name);

      $segments_points = array();
      // Ignore any trkseg in a trk:
      // we will treat all of them as a single track.
      foreach ($track->trkseg->trkpt as $point) {
        $point_attributes = $point->attributes();
        $segments_points[] = array(
          floatval($point_attributes['lat']),
          floatval($point_attributes['lon']),
          floatval((string) $point->ele),
        );
      }
      $points_array[] = array($track_name, $segments_points);
    }

    foreach ($xml->wpt as $waypoint) {
      $point_attributes = $waypoint->attributes();
      $wpt_name = Html::escape((string) $waypoint->name);
      $wpt_desc = Html::escape((string) $waypoint->desc);
      $wpt_ele = Html::escape((string) $waypoint->ele);
      $wpt_type = Html::escape((string) $waypoint->type);
      $waypoints[] = array(
        $wpt_name,
        floatval($point_attributes['lat']),
        floatval($point_attributes['lon']),
        $wpt_desc,
        $wpt_ele,
        $wpt_type,
      );
    }

    \Drupal::service('module_handler')->alter('gpx_track_elevation_waypoints', $waypoints);

    return array($points_array, $waypoints);
  }

  public static function isApplicable(FieldDefinitionInterface $field_definition) {
  if ($field_definition->getFieldStorageDefinition()->getCardinality() ==1) {
    $config = \Drupal::config('gpx_track_elevation.settings');
    if ($config->get('gpx_track_elevation.'.$field_definition->getTargetEntityTypeId().'.'.$field_definition->getTargetBundle())) {
      return TRUE;
    }
  }
  return False;
  }
  
  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // This gets the view_mode where our settings are stored.
    //$display = $instance['display'][$view_mode];
    // This gets the actual settings.
    //$settings = $display['settings'];
    $form['gpx_start_point'] = array(
      '#type' => 'select',
      '#default_value' => !is_null($this->getSetting('gpx_start_point'))?$this->getSetting('gpx_start_point'):1,
      '#title' => t('Highlight start point:'),
      '#required' => TRUE,
      '#options' => $this->get_formatter_option('first'),
      '#description' => t('Select which start points have to be highlighted'),
    );

    $form['gpx_end_point'] = array(
      '#type' => 'select',
      '#default_value' => !is_null($this->getSetting('gpx_end_point'))?$this->getSetting('gpx_end_point'):1,
      '#title' => t('Highlight end point:'),
      '#required' => TRUE,
      '#options' => $this->get_formatter_option('last'),
      '#description' => t('Select which end points have to be highlighted'),
    );
    
    return $form;
  }
  
  public function settingsSummary() {
    return array(
      t('Show start point: @gpx_start_point', ['@gpx_start_point' => $this->get_formatter_option()[$this->getSetting('gpx_start_point')]]),
      t('Show end point: @gpx_start_point', ['@gpx_start_point' => $this->get_formatter_option('last')[$this->getSetting('gpx_end_point')]]),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'gpx_end_point' => '1',
      'gpx_start_point' => '1',
    ) + parent::defaultSettings();
  }

  private function get_formatter_option($position = 'first') {
    return array(
      '0' => 'None',
      '1' => ($position == 'last') ? t('Only last track') : t('Only first track'),
      '2' => t('All tracks'),
    );
  }
  
}
