<?php

namespace Drupal\plotly_js\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Implementation of plotly.js data formatter.
 *
 * @FieldFormatter(
 *   id = "plotly_js_graph_formatter",
 *   label = @Translation("Plotly.js Graph"),
 *   field_types = {
 *     "plotly_js_graph"
 *   }
 * )
 */
class PlotlyJsGraphFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
  /**
   * Drupal LoggerFactory service container.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigFactory $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // Show which graph we are currently using for the field.
    $summary = [
      $this->t('Displays a plotly.js graph.'),
    ];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Early opt-out if the field is empty.
    if (count($items) <= 0) {
      return [];
    }

    $graph_settings = [];
    $graph_render_elements = [];

    // Loop over each graph and build data.
    foreach ($items as $item) {
      // Get a unique ID for this graph element.
      $graph_id = $this->getUniqueId();

      // Create render element for graph.
      $graph_render_elements[$graph_id] = [
        '#theme' => 'plotly_js_graph',
        '#graph_id' => $graph_id,
      ];

      // Split field into individual data points.
      $graph_settings[$graph_id] = [
        'unique_id' => $graph_id,
        'name' => $item->get('graph_name')->getString(),
        'series' => unserialize($item->get('series_data')->getString()),
        'layout' => unserialize($item->get('layout')->getString()),
      ];

      // Format the data in the way that plotly is expecting it.
      foreach ($graph_settings[$graph_id]['series'] as $series_index => &$series) {
        // Clear out the 'use_' values as we only use those to flag colors.
        $this->removeExtraFields($series);
        // Change boolean strings to actual booleans.
        $this->massageBooleanValues($series);

        // If the series isn't setup, remove it.
        if (count($series) <= 1 && isset($series['type'])) {
          unset($graph_settings[$graph_id]['series'][$series_index]);
        }

        // Get the mapbox access token if we need it.
        if ($series['type'] == 'scattermapbox') {
          $graph_settings[$graph_id]['mapbox_access_token'] = $this->configFactory->get('plotly_js.settings')->get('mapbox_access_token');
        }
      }
      // Re-index to handle deleted values.
      $graph_settings[$graph_id]['series'] = array_values($graph_settings[$graph_id]['series']);

      // Format layout values.
      $this->removeExtraFields($graph_settings[$graph_id]['layout']);
      $this->massageBooleanValues($graph_settings[$graph_id]['layout']);
    }

    // Return the plotly.js graph type.
    return [
      [
        'graphs' => $graph_render_elements,
        '#attached' => [
          'library' => [
            // Attach the plotly library.
            'plotly_js/plotly_js.plotly',
          ],
          'drupalSettings' => [
            'plotlyjs' => $graph_settings,
          ],
        ],
      ],
    ];
  }

  /**
   * Get a unique ID for a graph.
   *
   * @return string
   *   Unique ID string.
   */
  private function getUniqueId() {
    // Handle File entity.
    return Html::getUniqueId('plotly_js_graph');
  }

  /**
   * Sets boolean values to the actual booleans rather than strings.
   *
   * @param array &$data
   *   The array containing all the submitted values.
   */
  private function massageBooleanValues(array &$data) {
    foreach ($data as &$value) {
      if (is_array($value)) {
        $this->massageBooleanValues($value);
      }
      elseif ($value === 'true') {
        $value = TRUE;
      }
      elseif ($value === 'false') {
        $value = FALSE;
      }
    }
  }

  /**
   * Remove values which we use for settings, not for actual graphing.
   *
   * @param array &$data
   *   The array containing the graphing data.
   */
  private function removeExtraFields(array &$data) {
    foreach ($data as $key => &$value) {
      if (is_array($value)) {
        // Remove the ajax_actions if we can.
        if (isset($value['ajax_actions'])) {
          unset($value['ajax_actions']);
        }
        $this->removeExtraFields($value);
        // Remove empty arrays.
        if (count($value) == 0) {
          unset($data[$key]);
        }
      }
      // Remove flags that allow for using other fields.
      elseif (substr($key, 0, 4) == 'use_') {
        unset($data[$key]);
      }
      // Remove values that signify number of subfields in use.
      elseif ($key === 'number_subfield_values') {
        unset($data[$key]);
      }
    }
  }

}
