<?php

namespace Drupal\geolocation_2gis\Plugin\views\style;

use Drupal\Core\Render\Renderer;
use Drupal\geolocation\Plugin\views\field\GeolocationField;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Allow to display several field items on a 2gis map.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "maps_2gis",
 *   title = @Translation("2GIS map"),
 *   help = @Translation("Display geolocations on a 2GIS map."),
 *   theme = "views_view_list",
 *   display_types = {"normal"},
 * )
 */
class Geolocation2gisMap extends StylePluginBase
{

  protected $usesFields = TRUE;
  protected $usesRowPlugin = TRUE;
  protected $usesRowClass = FALSE;
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  public function evenEmpty()
  {
    return $this->options['even_empty'] ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function render()
  {
    if (!empty($this->options['geolocation2gis_field'])) {
      $geo_field = $this->options['geolocation2gis_field'];
    } else {
      \Drupal::logger('geolocation')->error("The geolocation common map views style was called without a geolocation field defined in the views style settings.");
      return [];
    }
    $geo_items = [];

    foreach ($this->view->result as $row_number => $row) {
      $renderer = \Drupal::service('renderer');
      $row_render_array = $this->view->rowPlugin->render($row);
      $description = $renderer->render($row_render_array);

      $geolocation_field = $this->view->field[$geo_field];
      $entity = $geolocation_field->getEntity($row);
      if (isset($entity->{$geolocation_field->definition['field_name']})) {
        /** @var \Drupal\Core\Field\FieldItemListInterface $field_item_list */
        $items = $entity->{$geolocation_field->definition['field_name']}->getValue();
        if (!empty($items)) {
          foreach ($items AS $item) {
            $geo_items[] = [
              'lat' => $item['lat'],
              'lng' => $item['lng'],
              'description' => $description
            ];
          }
        }
      }
    }

    $build = [
      '#theme' => 'geolocation_2gis_map',
      '#locations' => []
    ];

    $build['#attached']['library'][] = 'geolocation_2gis/api-2gis';
    $build['#attached']['library'][] = 'geolocation_2gis/map-2gis';
    $build['#attached']['drupalSettings']['locations'] = $geo_items;

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions()
  {
    $options = parent::defineOptions();

    $options['show_raw_locations'] = ['default' => '0'];
    $options['even_empty'] = ['default' => '0'];
    $options['geolocation_field'] = ['default' => ''];
    $options['title_field'] = ['default' => ''];
    $options['icon_field'] = ['default' => ''];
    $options['marker_scroll_to_result'] = ['default' => 0];
    $options['marker_row_number'] = ['default' => FALSE];
    $options['id_field'] = ['default' => ''];
    $options['marker_clusterer'] = ['default' => 0];
    $options['marker_clusterer_image_path'] = ['default' => ''];
    $options['marker_clusterer_styles'] = ['default' => []];
    $options['dynamic_map'] = [
      'contains' => [
        'enabled' => ['default' => 0],
        'update_handler' => ['default' => ''],
        'update_target' => ['default' => ''],
        'hide_form' => ['default' => 0],
        'views_refresh_delay' => ['default' => '1200'],
      ],
    ];
    $options['centre'] = ['default' => ''];
    $options['context_popup_content'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state)
  {

    parent::buildOptionsForm($form, $form_state);

    $labels = $this->displayHandler->getFieldLabels();
    $geo_options = [];

    $fields = $this->displayHandler->getOption('fields');
    foreach ($fields as $field_name => $field) {
      if ($field['type'] == 'geolocation2gis_latlng') {
        $geo_options[$field_name] = $labels[$field_name];
      }
    }

    $form['geolocation2gis_field'] = [
      '#title' => $this->t('Geolocation source field'),
      '#type' => 'select',
      '#default_value' => $this->options['geolocation2gis_field'],
      '#description' => $this->t("The source of geodata for each entity."),
      '#options' => $geo_options,
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state)
  {
    parent::validateOptionsForm($form, $form_state);
  }

}
