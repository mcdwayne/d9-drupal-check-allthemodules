<?php

namespace Drupal\openlayers_block\Plugin\Block;


use Drupal\Core\Block\BlockBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;


/**
 * Provides a Map as a Block.
 *
 * @Block(
 *   id = "openlayers_block",
 *   admin_label = @Translation("OpenLayers Map"),
 *   category = @Translation("OpenLayers Map"),
 * )
 */
class OpenLayers_Block extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $mapid = Html::getUniqueId('openlayers_map_block');
    $build = array();
    $map = openlayers_map_get_info($this->configuration['openlayers_block_map']);
    $map['settings']['zoom'] = isset($this->configuration['openlayers_block_map_zoom']) ? $this->configuration['openlayers_block_map_zoom'] : NULL;
    $map['settings']['minzoom'] = isset($this->configuration['openlayers_block_map_minzoom']) ? $this->configuration['openlayers_block_map_minzoom'] : NULL;
    $map['settings']['maxzoom'] = isset($this->configuration['openlayers_block_map_maxzoom']) ? $this->configuration['openlayers_block_map_maxzoom'] : NULL;
    $element['#markup'] = $this->configuration['label'];
    $element = openlayers_render_map($mapid, $map, null, $this->configuration['openlayers_block_map_mapheight'] . 'px', FALSE);
    //$element['#cache'] = ['max-age' => 0];
    // Merge #attached libraries.
    $this->view->element['#attached'] = NestedArray::mergeDeep($this->view->element['#attached'], $element['#attached']);
    $element['#attached'] =& $this->view->element['#attached'];
    
    //dd($element);
    $build['map'] = $element;
    //$build['#cache'] = ['max-age' => 0];
    return $build;
  }
  
  
  /*
   * Block Settings.
   */
    /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

   // $elements = parent::settingsForm($form, $form_state);
    
    $openlayers_map_options = [];
    foreach (openlayers_map_get_info() as $key => $map) {
      $openlayers_map_options[$key] = $map['label'];
    }
    
    $form['openlayers_block_map'] = [
      '#title' => $this->t('OpenLayers Map'),
      '#type' => 'select',
      '#options' => $openlayers_map_options,
      '#default_value' => isset($config['openlayers_block_map']) ? $config['openlayers_block_map'] : '',
      '#required' => TRUE,
      '#attributes' => ['class' => ['openlayers-map-selector']],
      '#ajax' => [
        'callback' => 'Drupal\openlayers_block\src\Plugin\Block\OpenLayersBlock::getMapSettings',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];
    
    $form['openlayers_block_map_zoom'] = [
      '#title' => $this->t('Zoom'),
      '#type' => 'number',
      '#attributes' => ['id' => ['openlayers-map-zoom']],
      '#default_value' => isset($config['openlayers_block_map_zoom']) ? $config['openlayers_block_map_zoom'] : '17',
      '#required' => TRUE,
    ];
    $form['openlayers_block_map_minzoom'] = [
      '#title' => $this->t('Min. Zoom'),
      '#type' => 'number',
      '#attributes' => ['id' => ['openlayers-map-minzoom']],
      '#default_value' => isset($config['openlayers_block_map_minzoom']) ? $config['openlayers_block_map_minzoom'] : '1',
      '#required' => TRUE,
    ];
    $form['openlayers_block_map_maxzoom'] = [
      '#title' => $this->t('Max. Zoom'),
      '#type' => 'number',
      '#attributes' => ['id' => ['openlayers-map-maxzoom']],
      '#default_value' => isset($config['openlayers_block_map_maxzoom']) ? $config['openlayers_block_map_maxzoom'] : '17',
      '#required' => TRUE,
    ];
    $form['openlayers_block_map_mapheight'] = [
      '#title' => $this->t('Map Height'),
      '#type' => 'number',
      '#attributes' => ['id' => ['openlayers-map-mapheight']],
      '#default_value' => isset($config['openlayers_block_map_mapheight']) ? $config['openlayers_block_map_mapheight'] : '350',
      '#field_suffix' => $this->t('px'),
    ];
    $form['openlayers_block_map_mapid'] = [
      '#title' => $this->t('Map Entity ID'),
      '#type' => 'textfield',
      '#size' => 36,
      '#disabled' => TRUE,  
      '#attributes' => ['id' => ['openlayers-map-mapid']],
      '#default_value' => isset($config['openlayers_block_map_mapid']) ? $config['openlayers_block_map_mapid'] : '',
    ];
    
    return $form;
  }
  
  
  
  /**
   * Get the view settings of a map.
   */
  public function getMapSettings($form_state) {
    $mapid = $form_state['fields']['#value']['field_geofield']['settings_edit_form']['settings']['openlayers_map'];
    $entity_map = \Drupal::service('entity.repository')->loadEntityByUuid('openlayers_map', $mapid);

    $mapheight = $entity_map->map_height->value;
    $zoom = $entity_map->zoom->value;
    $minzoom = $entity_map->minzoom->value;
    $maxzoom = $entity_map->maxzoom->value;

    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand('#openlayers-map-zoom', 'val', [$zoom]));
    $response->addCommand(new InvokeCommand('#openlayers-map-minzoom', 'val', [$minzoom]));
    $response->addCommand(new InvokeCommand('#openlayers-map-maxzoom', 'val', [$maxzoom]));
    $response->addCommand(new InvokeCommand('#openlayers-map-mapheight', 'val', [$mapheight]));
    $response->addCommand(new InvokeCommand('#openlayers-map-mapid', 'val', [$mapid]));

    return $response;
  }
  
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['openlayers_block_map'] = $values['openlayers_block_map'];
    $this->configuration['openlayers_block_map_zoom'] = $values['openlayers_block_map_zoom'];
    $this->configuration['openlayers_block_map_minzoom'] = $values['openlayers_block_map_minzoom'];
    $this->configuration['openlayers_block_map_maxzoom'] = $values['openlayers_block_map_maxzoom'];
    $this->configuration['openlayers_block_map_mapheight'] = $values['openlayers_block_map_mapheight'];
    $this->configuration['openlayers_block_map_mapid'] = $values['openlayers_block_map_mapid'];
  }
}
