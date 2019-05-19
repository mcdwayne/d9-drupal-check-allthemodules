<?php

namespace Drupal\trail_graph\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\ViewExecutable;

/**
 * Defines an interface for Trail graph data plugins.
 */
interface TrailGraphDataInterface extends PluginInspectionInterface {

  /**
   * Builds plugin option form.
   *
   * @param \Drupal\views\Plugin\views\style\StylePluginBase $style
   *   Drupal views style plugin object.
   *
   * @return array
   *   Form array.
   */
  public function buildOptionsForm(StylePluginBase $style);

  /**
   * Calculates trail nodes and trails.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View used for searching nodes.
   *
   * @return array
   *   Array of results with keys - trail_nodes and trails.
   */
  public function getAllTrailData(ViewExecutable $view);

  /**
   * Gets rendered field values from provided node list.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View used for searching nodes.
   * @param array $trail_nodes
   *   Array of nodes with loaded node entities as values.
   *
   * @return array
   *   Returns prepared array of data for front end library.
   */
  public function getTrailNodeFields(ViewExecutable $view, array $trail_nodes);

  /**
   * Gets rendered field values and links from provided trail list.
   *
   * @param array $trails
   *   Array of taxonomy terms with loaded term entities as values.
   * @param array $trail_info
   *   Array of weight data as returned from $this->getAllNodeWeights.
   *
   * @return array
   *   Returns prepared array of data for front end library.
   */
  public function getTrailFields(array $trails, array $trail_info);

  /**
   * Returns weigh data for all terms.
   *
   * @param array $term_ids
   *   List of term ids.
   *
   * @return array
   *   List of node and weight information grouped by term id.
   */
  public function getAllNodeWeights(array $term_ids);

  /**
   * Prepares trail data as front end nodes.
   *
   * @param array $trail_data
   *   Array of trail data.
   *
   * @return array
   *   Rendered field values from provided term data list.
   */
  public function getTrailHeaderFields(array $trail_data);

  /**
   * Returns Exposed filter input fields and values.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View used for searching nodes.
   * @param array $filter_input_fields
   *   Array of filter input fields.
   *
   * @return array
   *   Array of filtered input fields and values.
   */
  public function getExposedFilterInput(ViewExecutable $view, array $filter_input_fields);

}
