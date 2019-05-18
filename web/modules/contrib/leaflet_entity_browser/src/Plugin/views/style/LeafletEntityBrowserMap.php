<?php

namespace Drupal\leaflet_entity_browser\Plugin\views\style;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\leaflet_views\Plugin\views\style\LeafletMap;

/**
 * Style to render View output as a Leaflet map, to be used in Entity Browsers.
 *
 * @ingroup views_style_plugins
 *
 * Attributes set below end up in the $this->definition[] array.
 *
 * @ViewsStyle(
 *   id = "leaflet_entity_browser_map",
 *   title = @Translation("Leaflet map for Entity Browser"),
 *   help = @Translation("Displays a View as a Leaflet map, to be used in Entity Browsers."),
 *   display_types = {"normal"},
 *   theme = "leaflet-map"
 * )
 */
class LeafletEntityBrowserMap extends LeafletMap {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['icon']['iconUrl']['#weight'] = -34;
    $form['icon']['selectedIconUrl'] = [
      '#title' => $this->t('Selected Icon URL'),
      '#description' => $this->t('Can be an absolute or relative URL.'),
      '#type' => 'textfield',
      '#maxlength' => 999,
      '#default_value' => $this->options['icon']['selectedIconUrl'] ?: '',
      '#weight' => -33,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    $style_options = $form_state->getValue('style_options');
    $icon_options = $style_options['icon'];
    if (!empty($icon_options['selectedIconUrl']) && !UrlHelper::isValid($icon_options['selectedIconUrl'])) {
      $form_state->setError($form['icon']['selectedIconUrl'], $this->t('Selected icon URL is invalid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $data = [];
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

          // Render the entity with the selected view mode.
          if ($this->options['description_field'] === '#rendered_entity' && is_object($result)) {
            $entity = $this->entityManager->getStorage($this->entityType)->load($result->nid);
            $build = $this->entityManager->getViewBuilder($entity->getEntityTypeId())->view($entity, $this->options['view_mode'], $entity->language());
            $description = $this->renderer->render($build);
          }
          // Normal rendering via fields.
          elseif ($this->options['description_field']) {
            $description = $this->rendered_fields[$id][$this->options['description_field']];
          }

          // Attach pop-ups if we have a description field.
          if (isset($description)) {
            foreach ($points as &$point) {
              $point['popup'] = $description;
            }
          }

          // Attach also titles, they might be used later on.
          if ($this->options['name_field']) {
            foreach ($points as &$point) {
              $point['label'] = $this->rendered_fields[$id][$this->options['name_field']];
            }
          }

          // Add the entity_browser checkbox placeholders to each point.
          if (!empty($this->rendered_fields[$id]['entity_browser_select'])) {
            foreach ($points as &$point) {
              $point['entity_browser_placeholder'] = $this->rendered_fields[$id]['entity_browser_select'];
              $item_row_id_tmp = explode("<!--form-item-entity_browser_select--", $point['entity_browser_placeholder'] . '');
              $item_row_id = rtrim($item_row_id_tmp[1], "->");
              // We want to add the rowid to each marker as an additional class.
              // If we override className, all other icon properties need to be
              // set as well.
              $point['icon']['className'] = 'leb-marker-rowid-' . $item_row_id;
              $module_path = base_path() . drupal_get_path('module', 'leaflet_entity_browser');
              $selected_icon_url = !empty($this->options['icon']['selectedIconUrl']) ? $this->options['icon']['selectedIconUrl'] : $module_path . '/images/marker-icon-selected.png';
              if (empty($this->options['icon']['iconUrl'])) {
                $point['icon']['iconUrl'] = $module_path . '/images/marker-icon-blue.png';
                $icon_url = $point['icon']['iconUrl'];
                $point['icon']['iconSize']['x'] = 25;
                $point['icon']['iconSize']['y'] = 41;
                $point['icon']['iconAnchor']['x'] = 12;
                $point['icon']['iconAnchor']['y'] = 41;
                $point['icon']['popupAnchor']['x'] = 1;
                $point['icon']['popupAnchor']['y'] = -34;
              }
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
    $built_map = leaflet_render_map($map, $data, $this->options['height'] . 'px');

    // Attach to the rendered map the markup of our placeholders.
    $checkboxes = array_filter(array_map(function ($item) {
      return !empty($item['entity_browser_placeholder']) ? $item['entity_browser_placeholder'] : NULL;
    }, $data));
    if (!empty($checkboxes)) {
      $built_map['#checkboxes'] = $checkboxes;
      $built_map['#theme'] = 'leaflet_entity_browser_map';
      $built_map['#attached']['library'][] = 'leaflet_entity_browser/leaflet-entity-browser';
      $built_map['#attached']['drupalSettings']['leaflet_entity_browser']['icon_url'] = !empty($icon_url) ? $icon_url : '';
      $built_map['#attached']['drupalSettings']['leaflet_entity_browser']['selected_icon_url'] = !empty($selected_icon_url) ? $selected_icon_url : '';
    }

    return $built_map;
  }

}
