<?php

namespace Drupal\openlayers_geofield\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\field\Entity;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'geofield_OpenLayersInputWidget' widget.
 *
 * @FieldWidget(
 *   id = "geofield_OpenLayersInputWidget",
 *   label = @Translation("OpenLayers"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class OpenLayersInputWidget extends WidgetBase {

  /**
   * Gives a visual Option to save Geometries.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    
    $mapid = Html::getUniqueId('openlayers_map');
    $map = openlayers_map_get_info($this->getSetting('openlayers_map'));
    $element['selectGeom'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of Geom'),
      '#attributes' => ['id' => $mapid.'-selectfield'],
      '#options' => [
        'Point' => 'Point',
        'LineString' => 'Line',
        'Polygon' => 'Polygon',
    // 'MultiPoint' => 'Multipoint',
    // 'MultiLineString' => 'Multiline',
    // 'MultiPolygon' => 'Multipolygon',.
      ],
    ];
    
    $element['map'] = openlayers_render_map($mapid, $map, null, $map['settings']['mapheight'].'px', true);
    
    
    if($this->getSetting('openlayers_showbox')) {
      $type = 'hidden';
    } else {
      $type = 'textarea';
    }
    
    $element['value'] = [
      '#type' => $type,
      '#title' => $this->t('Geom as WKT'),
      '#default_value' => $items[$delta]->value ?: NULL,
      '#attributes' => ['id' => $mapid.'-wktbox'],
    ];
    return $element;
  }

  /**
  * {@inheritdoc}
  */
  public static function defaultSettings() {
    return [
      'openlayers_map' => 'none selected',
      'mapeid' => 'blabla',
      'mapheight' => 450,
      'zoom' => 9,
      'minzoom' => 1,
      'maxzoom' => 18,
      'openlayers_showbox' => false,
    ] + parent::defaultSettings();
  }
   /**
   * Get the view settings of a map.
   */
  public function getMapSettings(&$form, FormStateInterface $form_state) {
    
    $response = new AjaxResponse();
    $mapid_ = $form_state->getTriggeringElement();
  
    if($mapid_['#value'] === 'lastSave') {      
        
      $entity_type = 'node';
      $bundle = $mapid_['#attributes']['bundle'];
      $form_mode = 'default';
    
      $form_display = \Drupal::entityTypeManager()
        ->getStorage('entity_form_display')
        ->load($entity_type . '.' . $bundle . '.' . $form_mode);
    
      $field = $form_display->getComponent($mapid_['#attributes']['field']);
      $response->addCommand(new InvokeCommand('#openlayers-map-zoom', 'val', [$field['settings']['zoom']]));
      $response->addCommand(new InvokeCommand('#openlayers-map-minzoom', 'val', [$field['settings']['minzoom']]));
      $response->addCommand(new InvokeCommand('#openlayers-map-maxzoom', 'val', [$field['settings']['maxzoom']]));
      $response->addCommand(new InvokeCommand('#openlayers-map-mapheight', 'val', [$field['settings']['mapheight']]));
      $response->addCommand(new InvokeCommand('#openlayers-map-map', 'val', [$mapid_['#attributes']['saved']]));
      
    } else {
      
      $entity_map = \Drupal::service('entity.repository')->loadEntityByUuid('openlayers_map', $mapid_['#value']);

      $mapheight = $entity_map->map_height->value;
      $zoom = $entity_map->zoom->value;
      $minzoom = $entity_map->minzoom->value;
      $maxzoom = $entity_map->maxzoom->value;
      $response->addCommand(new InvokeCommand('#openlayers-map-zoom', 'val', [$zoom]));
      $response->addCommand(new InvokeCommand('#openlayers-map-minzoom', 'val', [$minzoom]));
      $response->addCommand(new InvokeCommand('#openlayers-map-maxzoom', 'val', [$maxzoom]));
      $response->addCommand(new InvokeCommand('#openlayers-map-mapheight', 'val', [$mapheight]));
      $response->addCommand(new InvokeCommand('#openlayers-map-map', 'val', [$mapid_['#value']]));
    }
    return $response;
  }
  /**
  * {@inheritdoc}
  */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    
    $config = $this->fieldDefinition;
    
    $elements = parent::settingsForm($form, $form_state);

    $openlayers_map_options = [];
    foreach (openlayers_map_get_info() as $key => $map) {
      $openlayers_map_options[$key] = $this->t($map['label']);
    }
    
    $openlayers_map_options['lastSave'] = $openlayers_map_options[$this->getSetting('openlayers_map')] . ' (last save)';
    
    $elements['openlayers_mapid'] = [
      '#title' => $this->t('OpenLayers Map'),
      '#type' => 'select',
      '#options' => $openlayers_map_options,
      '#default_value' => 'lastSave',
      '#required' => TRUE,
      '#attributes' => ['class' => ['openlayers-map-selector'], 'field' => $config->getName(), 'bundle' => $config->getTargetBundle(), 'saved' => $this->getSetting('openlayers_map')],
      '#ajax' => [
        'callback' => 'Drupal\openlayers_geofield\Plugin\Field\FieldWidget\OpenLayersInputWidget::getMapSettings',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];
    
    $elements['zoom'] = [
      '#title' => $this->t('Zoom'),
      '#type' => 'number',
      '#attributes' => ['id' => ['openlayers-map-zoom']],
      '#default_value' => $this->getSetting('zoom'),
      '#required' => TRUE,
    ];
    $elements['minzoom'] = [
      '#title' => $this->t('Min. Zoom'),
      '#type' => 'number',
      '#attributes' => ['id' => ['openlayers-map-minzoom']],
      '#default_value' => $this->getSetting('minzoom'),
      '#required' => TRUE,
    ];
    $elements['maxzoom'] = [
      '#title' => $this->t('Max. Zoom'),
      '#type' => 'number',
      '#attributes' => ['id' => ['openlayers-map-maxzoom']],
      '#default_value' => $this->getSetting('maxzoom'),
      '#required' => TRUE,
    ];
    $elements['mapheight'] = [
      '#title' => $this->t('Map Height'),
      '#type' => 'number',
      '#attributes' => ['id' => ['openlayers-map-mapheight']],
      '#default_value' => $this->getSetting('mapheight'),
      '#field_suffix' => $this->t('px'),
      '#required' => TRUE,
    ];
    
    $elements['openlayers_map'] = [
      '#title' => $this->t('Map Entity ID'),
      '#type' => 'hidden',
      '#size' => 36,
      '#disabled' => FALSE,
      '#attributes' => ['id' => ['openlayers-map-map']],
      '#default_value' => $this->getSetting('openlayers_map'),
    ];
    $elements['openlayers_showbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide input value box'),
      '#attributes' => ['id' => ['openlayers-map-showbox']],
      '#default_value' => $this->getSetting('openlayers_showbox'),
    ];
    return $elements;
  }
  
  
  /**
  * {@inheritdoc}
  */
  public function settingsSummary() {
      
    $summary = [];
    
    foreach (openlayers_map_get_info() as $key => $map) {
      if( $key === $this->getSetting('openlayers_map')) {
        $summary[] = $this->t('OpenLayers MAP: @map', ['@map' => $this->t($map['label'])]); 
      }
    }  
    $summary[] = $this->t('Map height: @height px', ['@height' => $this->getSetting('mapheight')]);
    return $summary;
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $geophp = \Drupal::service('geofield.geophp');
    foreach ($values as $delta => $value) {
      if ($geom = $geophp->load($value['value'])) {
        $values[$delta]['value'] = $geom->out('wkt');
      }
    }
    return $values;
  }
/**
   * {@inheritdoc}
   *
   * This function is called from parent::view().
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    dsm("Input:viewElements");
    $settings = $this->getSettings();
    $map = openlayers_map_get_info($settings['openlayers_map']);
    $map['settings']['zoom'] = isset($settings['zoom']) ? $settings['zoom'] : NULL;
    $map['settings']['minzoom'] = isset($settings['minzoom']) ? $settings['minzoom'] : NULL;
    $map['settings']['maxzoom'] = isset($settings['maxzoom']) ? $settings['maxzoom'] : NULL;

    $elements = [];
    foreach ($items as $delta => $item) {
      $features = openlayers_process_geofield($item->value);
      $elements[$delta] = openlayers_render_map($map, $features, $settings['mapheight'] . 'px');
    }
    dsm();
    return $elements;
  }
}
