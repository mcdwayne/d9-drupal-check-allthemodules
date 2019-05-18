<?php

namespace Drupal\mapycz\Plugin\views\style;

use Drupal\mapycz\Plugin\views\field\MapyCzField;
use Drupal\mapycz\MapyCzCore;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Allow to display several field items on a MapyCZ map.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "mapycz_map",
 *   title = @Translation("MapyCZ - Map"),
 *   help = @Translation("Display MapyCZ locations in a view map"),
 *   theme = "views_view_list",
 *   display_types = {"normal"},
 * )
 */
class MapyCzMap extends StylePluginBase {

  protected $usesFields = TRUE;
  protected $usesRowPlugin = TRUE;
  protected $usesRowClass = FALSE;
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  public function evenEmpty() {
    return $this->options['even_empty'] ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $markers_title_field = NULL;
    // Fail if no MapyCZ field is set.
    if (!empty($this->options['mapycz_field'])) {
      $map_field = $this->options['mapycz_field'];
    }
    else {
      \Drupal::logger('mapycz')->error("The MapyCZ map views style was called without a mapycz field defined in the views style settings.");
      return [];
    }
    if (!empty($this->options['title'])) {
      $markers_title_field = $this->options['title'];
      if ($markers_title_field == 'none') {
        $markers_title_field = NULL;
      }
    }
    $markers_title_print_address = $this->options['address_print'];
    $map_id = $this->view->dom_id;
    $markers = [];

    foreach ($this->view->result as $row) {
      if ($this->view->field[$map_field] instanceof MapyCzField) {
        /** @var \Drupal\mapycz\Plugin\views\field\MapyCZField $mapycz_field */
        $mapycz_field = $this->view->field[$map_field];
        $items = $mapycz_field->getItems($row);
      }
      else {
        continue;
      }

      // Build map object for each delta item in row.
      foreach ($items as $delta => $item) {
        $markers[] = MapyCzCore::createMapObject($item);
      }
    }

    // Add custom markers.
    if ($markers_title_field !== NULL) {
      foreach ($this->view->result as $delta => $row) {
        $value = $this->view->style_plugin->getField($delta, $markers_title_field)->__toString();
        if (isset($markers[$delta])) {
          $markers[$delta]->title = $value;
        }
      }
    }

    $build = [
      '#theme' => 'mapycz_map',
      '#map_id' => $map_id,
      '#markers' => $markers,
      '#print_markers_address' => $markers_title_print_address,
      '#theme_wrappers' => [
        'container' => [
          '#attributes' => [
            'id' => $map_id . '-wrapper',
            'class' => 'mapycz-wrapper',
          ],
        ],
      ],
      '#width' => $this->options['width'],
      '#height' => $this->options['height'],
      '#type' => $this->options['type'],
      '#attached' => [
        'library' => [
          'mapycz/mapycz.views_style',
        ],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['mapycz_field'] = ['default' => ''];
    $options['title'] = ['default' => 'none'];
    $options['width'] = ['default' => '100%'];
    $options['height'] = ['default' => '350px'];
    $options['type'] = ['default' => 'basic'];
    $options['address_print'] = FALSE;

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $labels = $this->displayHandler->getFieldLabels();
    $fieldMap = \Drupal::service('entity_field.manager')->getFieldMap();
    $mapycz_options = [];
    $title_options = [];
    $fields = $this->displayHandler->getOption('fields');
    foreach ($fields as $field_name => $field) {
      if ($field['plugin_id'] == 'mapycz_field') {
        $mapycz_options[$field_name] = $labels[$field_name];
      }
      else {
        $title_options[$field_name] = $labels[$field_name];
      }

      if ($field['plugin_id'] == 'field' && !empty($field['entity_type']) && !empty($field['entity_field'])) {
        if (!empty($fieldMap[$field['entity_type']][$field['entity_field']]['type']) && $fieldMap[$field['entity_type']][$field['entity_field']]['type'] == 'mapycz') {
          $mapycz_options[$field_name] = $labels[$field_name];
        }
        else {
          $title_options[$field_name] = $labels[$field_name];
        }
      }
    }

    $form['mapycz_field'] = [
      '#title' => $this->t('MapyCZ source field'),
      '#type' => 'select',
      '#default_value' => $this->options['mapycz_field'],
      '#description' => $this->t("The source of geodata for each entity."),
      '#options' => $mapycz_options,
      '#required' => TRUE,
    ];

    $form['title'] = [
      '#title' => $this->t('Marker title field'),
      '#type' => 'select',
      '#default_value' => $this->options['title'],
      '#description' => $this->t("The source of title for each entity."),
      "#empty_value" => "none",
      "#empty_option" => $this->t("None"),
      '#options' => $title_options,
      '#required' => FALSE,
    ];

    $form['address_print'] = [
      '#title' => $this->t('Print address to end of the markers card.'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['address_print'],
    ];

    $form['width'] = [
      '#title' => 'Šířka',
      '#type' => 'textfield',
      '#default_value' => $this->options['width'],
      '#description' => 'Vložte velikost a jednotky, např 200px nebo 100%.',
    ];

    $form['height'] = [
      '#title' => 'Výška',
      '#type' => 'textfield',
      '#default_value' => $this->options['height'],
      '#description' => 'Vložte velikost a jednotky, např 200px nebo 100%.',
    ];

    $form['type'] = [
      '#title' => 'Typ mapy',
      '#type' => 'select',
      '#options' => MapyCzCore::getMapTypeOptions(),
      '#default_value' => $this->options['type'],
      '#description' => 'Výchozí vzhled mapy, nebude-li v editaci mapy explicitně jinak.',
    ];
  }

}
