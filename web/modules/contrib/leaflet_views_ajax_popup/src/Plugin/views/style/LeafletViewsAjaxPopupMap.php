<?php

/**
 * @file
 * Definition of Drupal\leaflet_views_ajax_popup\Plugin\views\style\LeafletViewsAjaxPopupMap.
 */

namespace Drupal\leaflet_views_ajax_popup\Plugin\views\style;

use Drupal\leaflet_views\Plugin\views\style\LeafletMap;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Style plugin to render a View output as a Leaflet Views Ajax popup map.
 *
 * @ingroup views_style_plugins
 *
 * Attributes set below end up in the $this->definition[] array.
 *
 * @ViewsStyle(
 *   id = "leafet_views_ajax_popup_map",
 *   title = @Translation("Leaflet map (AJAX popups)"),
 *   help = @Translation("Displays a View as a Leaflet map with pop-ups loaded via AJAX on demand."),
 *   display_types = {"normal"},
 *   theme = "leaflet-map"
 * )
 */
class LeafletViewsAjaxPopupMap extends LeafletMap {

  /**
   * If this view is displaying an entity, save the entity type and info.
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // For later use, set entity info related to the View's base table.
    $base_tables = array_keys($view->getBaseTables());
    $base_table = reset($base_tables);

    if (empty($this->entity_type)) {
      if (strpos($base_table, 'search_api_index_') === 0) {
        $index_name = str_replace('search_api_index_', '', $base_table);
        $index = search_api_index_load($index_name);
        if ($index->item_type) {
          if ($info = \Drupal::entityTypeManager()->getDefinition($index->item_type, FALSE)) {
            $this->entity_type = $index->item_type;
            $this->entity_info = $info;
          }
        }
      }
      else {
        foreach (\Drupal::entityTypeManager()->getDefinitions() as $key => $info) {
          if ($info->getDataTable() == $base_table) {
            $this->entity_type = $key;
            $this->entity_info = $info;
            return;
          }
        }
      }
    }
  }

  /**
   * Options form
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    if ($this->entity_type) {
      parent::buildOptionsForm($form, $form_state);

      // We accept rendered entity only as popup.
      unset($form['description_field']);
      unset($form['view_mode']['#states']);

      $form['view_mode']['#title'] = t('Popup view mode');
      $form['view_mode']['#description'] = t('Select what view mode should be used to render an entity when displaying popup.');
    }
    else {
      $form = array(
        '#markup' => '<p>' . t('Entity type is not defined for this view. Please consider using another display style.') . '</p>',
      );
    }
  }

  /**
   * Renders view.
   */
  function render() {
    $data = array();
    $geofield_name = $this->options['data_source'];
    if ($this->options['data_source']) {
      $this->renderFields($this->view->result);
      foreach ($this->view->result as $id => $result) {

        $geofield_value = $this->getFieldValue($id, $geofield_name);

        if (empty($geofield_value)) {
          // In case the result is not among the raw results, get it from the
          // rendered results.
          $geofield_value = $this->rendered_fields[$id][$geofield_name];
        }
        if (!empty($geofield_value)) {
          $points = leaflet_process_geofield($geofield_value);

          // Popup placeholder.
          $entity_id = isset($result->{$this->entity_info->getKey('id')})
            ? $result->{$this->entity_info->getKey('id')}
            : ((isset($result->entity) && is_numeric($result->entity)) ? $result->entity : 0);
          $description = leaflet_views_ajax_popup_markup($this->entity_type, $entity_id, $this->options['view_mode']);

          // Attach pop-ups if we have a description field
          if (isset($description)) {
            foreach ($points as &$point) {
              $point['popup'] = $description;
            }
          }

          // Attach also titles, they might be used later on
          if ($this->options['name_field']) {
            foreach ($points as &$point) {
              $point['label'] = $this->rendered_fields[$id][$this->options['name_field']];
            }
          }

          $data = array_merge($data, $points);

          if (!empty($this->options['icon']) && $this->options['icon']['iconUrl']) {
            foreach ($data as $key => $feature) {
              $data[$key]['icon'] = $this->options['icon'];
            }
          }
        }
      }
    }

    // Always render the map, even if we do not have any data.
    $map = leaflet_map_get_info($this->options['map']);
    $build = leaflet_render_map($map, $data, $this->options['height'] . 'px');

    $build['#attached']['library'][] = 'leaflet_views_ajax_popup/leaflet_views_ajax_popup';
    return $build;
  }
}
