<?php

namespace Drupal\views_polygon_search_freedraw\Plugin\ViewsPolygonSearchPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Random;
use Drupal\Core\Form\FormState;
use Drupal\views_polygon_search\ViewsPolygonSearchPluginBase;

/**
 * Widget of .
 *
 * @ViewsPolygonSearchPlugin(
 *   id = "leaflet_freedraw",
 *   label = @Translation("Leaflet FreeDraw"),
 * )
 */
class ViewsPolygonSearchFreedraw extends ViewsPolygonSearchPluginBase {

  /**
   * {@inheritdoc}
   */
  public function formOptions(&$form, FormStateInterface $form_state, array $options) {
    $form['position'] = [
      '#title' => $this->t('Position'),
      '#type' => 'select',
      '#options' => [
        'topleft' => $this->t('Top left of the map.'),
        'topright' => $this->t('Top right of the map.'),
        'bottomleft' => $this->t('Bottom left of the map.'),
        'bottomright' => $this->t('Bottom right of the map.'),
      ],
      '#default_value' => isset($options['position']) ? $options['position'] : NULL,
    ];

    $form['buttons'] = [
      '#title' => 'Buttons',
      '#type' => 'checkboxes',
      '#options' => [
        'removeOne' => $this->t('For removing selected polygon'),
        'removeAll' => $this->t('For removing all polygons'),
      ],
      '#default_value' => isset($options['buttons']) ? $options['buttons'] : NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormState $form_state, $handler) {
    $options = $handler->options['filter_plugin_style']['options'];
    $random = new Random();
    $unique = $random->name();
    $form['value'] += [
      '#attributes' => [
        'class' => [
          'views-polygon-search',
          'views-polygon-search-' . $unique,
        ],
      ],
      '#attached' => [
        'library' => ['views_polygon_search_freedraw/views_polygon_search_freedraw'],
        'drupalSettings' => [
          'viewsPolygonSearch' => [
            [
              'domId' => $handler->view->dom_id,
              'textAreaId' => $unique,
              'position' => isset($options['position']) ? $options['position'] : 'topleft',
              'buttons' => array_filter($options['buttons']),
              'multiple' => ($handler->operator != '='),
            ],
          ],
        ],
      ],
    ];
  }

}
