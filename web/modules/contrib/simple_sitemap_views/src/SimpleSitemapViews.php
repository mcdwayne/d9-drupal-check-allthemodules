<?php

/**
 * @file
 * Contains simple_sitemap_views service.
 */

namespace Drupal\simple_sitemap_views;

use Drupal\simple_sitemap_views\Plugin\views\display_extender\SimpleSitemapDisplayExtender;
use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Class to manage sitemap data for views.
 */
class SimpleSitemapViews {

  /**
   * Separator between arguments.
   */
  const ARGUMENT_SEPARATOR = '/';

  /**
   * The current active database's master connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * SimpleSitemapViews constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The current active database's master connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Get sitemap settings for view display.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   A view executable instance.
   * @param string|null $display_id
   *   The display id. If empty uses the preselected display.
   *
   * @return array|null
   *   The sitemap settings if the display is indexed, NULL otherwise.
   */
  public function getSitemapSettings(ViewExecutable $view, $display_id = NULL) {
    // Ensure the display was correctly set.
    if (!$view->setDisplay($display_id)) {
      return NULL;
    }
    // Get the list of extenders.
    $extenders = $view->display_handler->getExtenders();
    $extender = isset($extenders['simple_sitemap_display_extender']) ? $extenders['simple_sitemap_display_extender'] : NULL;
    // Retrieve the sitemap settings from the extender.
    if ($extender instanceof SimpleSitemapDisplayExtender && $extender->hasSitemapSettings() && $extender->isIndexingEnabled()) {
      return $extender->getSitemapSettings();
    }
    return NULL;
  }

  /**
   * Get indexable arguments for view display.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   A view executable instance.
   * @param string|null $display_id
   *   The display id. If empty uses the preselected display.
   *
   * @return array
   *   Indexable arguments identifiers.
   */
  public function getIndexableArguments(ViewExecutable $view, $display_id = NULL) {
    $indexable_arguments = [];
    $settings = $this->getSitemapSettings($view, $display_id);
    if ($settings && !empty($settings['arguments']) && is_array($settings['arguments'])) {
      // Find indexable arguments.
      $arguments = array_keys($view->display_handler->getHandlers('argument'));
      foreach ($arguments as $argument_id) {
        if (empty($settings['arguments'][$argument_id])) {
          break;
        }
        $indexable_arguments[] = $argument_id;
      }
    }
    return $indexable_arguments;
  }

  /**
   * Adds view arguments to the index.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   A view executable instance.
   * @param array $args
   *   Array of arguments to add to the index.
   * @param string|null $display_id
   *   The display id. If empty uses the preselected display.
   *
   * @return bool
   *   TRUE if the arguments are added to the index, FALSE otherwise.
   */
  public function addArgumentsToIndex(ViewExecutable $view, array $args, $display_id = NULL) {
    // An array of arguments to be added to the index can not be empty.
    // Also ensure the display was correctly set.
    if (empty($args) || !$view->setDisplay($display_id)) {
      return FALSE;
    }
    // Check that indexing of at least one argument is enabled.
    $indexable_arguments = $this->getIndexableArguments($view);
    if (empty($indexable_arguments)) {
      return FALSE;
    }
    // Check that the number of identifiers is equal to the number of values.
    $args_ids = array_slice($indexable_arguments, 0, count($args));
    if (count($args_ids) != count($args)) {
      return FALSE;
    }
    // Check that the current number of rows in the index does not
    // exceed the specified number.
    $condition = new Condition('AND');
    $condition->condition('view_id', $view->id());
    $condition->condition('display_id', $view->current_display);
    $settings = $this->getSitemapSettings($view);
    $max_links = is_numeric($settings['max_links']) ? $settings['max_links'] : 0;
    if ($max_links > 0 && $this->getArgumentsFromIndexCount($condition) >= $max_links) {
      return FALSE;
    }
    // Convert the set of identifiers and a set of values to string.
    $args_ids = $this->convertArgumentsArrayToString($args_ids);
    $args_values = $this->convertArgumentsArrayToString($args);
    $condition->condition('arguments_ids', $args_ids);
    $condition->condition('arguments_values', $args_values);
    // Check that this set of arguments has not yet been indexed.
    if ($this->getArgumentsFromIndexCount($condition)) {
      return FALSE;
    }
    // Check that the view result is not empty for this set of arguments.
    $params = array_merge([$view->id(), $view->current_display], $args);
    $view_result = call_user_func_array('views_get_view_result', $params);
    if (empty($view_result)) {
      return FALSE;
    }
    // Add a set of arguments to the index.
    $options = ['return' => Database::RETURN_AFFECTED];
    $query = $this->database->insert('simple_sitemap_views', $options);
    $query->fields([
      'view_id' => $view->id(),
      'display_id' => $view->current_display,
      'arguments_ids' => $args_ids,
      'arguments_values' => $args_values,
    ]);
    return (bool) $query->execute();
  }

  /**
   * Get arguments from index.
   *
   * @param \Drupal\Core\Database\Query\ConditionInterface|null $condition
   *   The query conditions.
   * @param int|null $limit
   *   The number of records to return from the result set. If NULL, returns
   *   all records.
   * @param bool $convert
   *   Defaults to FALSE. If TRUE, the argument string will be converted
   *   to an array.
   *
   * @return array
   *   An array with information about the indexed arguments.
   */
  public function getArgumentsFromIndex(ConditionInterface $condition = NULL, $limit = NULL, $convert = FALSE) {
    // Select the rows from the index table.
    $query = $this->database->select('simple_sitemap_views', 'ssv');
    $query->addField('ssv', 'id');
    $query->addField('ssv', 'view_id');
    $query->addField('ssv', 'display_id');
    $query->addField('ssv', 'arguments_values', 'arguments');
    // Add conditions if necessary.
    if (!empty($condition)) {
      $query->condition($condition);
    }
    // Limit results if necessary.
    if (!empty($limit)) {
      $query->range(0, $limit);
    }
    $rows = $query->execute()->fetchAll();
    // Form the result.
    $arguments = [];
    foreach ($rows as $row) {
      $arguments[$row->id] = [
        'view_id' => $row->view_id,
        'display_id' => $row->display_id,
        'arguments' => $convert ? $this->convertArgumentsStringToArray($row->arguments) : $row->arguments,
      ];
    }
    return $arguments;
  }

  /**
   * Get the number of rows in the index.
   *
   * @param \Drupal\Core\Database\Query\ConditionInterface|null $condition
   *   The query conditions.
   *
   * @return int
   *   The number of rows.
   */
  public function getArgumentsFromIndexCount(ConditionInterface $condition = NULL) {
    $query = $this->database->select('simple_sitemap_views', 'ssv');
    // Add conditions if necessary.
    if (!empty($condition)) {
      $query->condition($condition);
    }
    // Get the number of rows from the index table.
    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Returns the ID of the record in the index for the specified position.
   *
   * @param int $position
   *   Position of the record.
   * @param \Drupal\Core\Database\Query\ConditionInterface|null $condition
   *   The query conditions.
   *
   * @return int|bool
   *   The ID of the record, or FALSE if there is no specified position.
   */
  public function getIndexIdByPosition($position, ConditionInterface $condition = NULL) {
    $query = $this->database->select('simple_sitemap_views', 'ssv');
    $query->addField('ssv', 'id');
    // Add conditions if necessary.
    if (!empty($condition)) {
      $query->condition($condition);
    }
    $query->orderBy('id', 'ASC');
    $query->range($position - 1, 1);
    return $query->execute()->fetchField();
  }

  /**
   * Remove arguments from index.
   *
   * @param \Drupal\Core\Database\Query\ConditionInterface|null $condition
   *   The query conditions.
   */
  public function removeArgumentsFromIndex(ConditionInterface $condition = NULL) {
    if (empty($condition)) {
      // If there are no conditions, use the TRUNCATE query.
      $query = $this->database->truncate('simple_sitemap_views');
    }
    else {
      // Otherwise, use the DELETE query.
      $query = $this->database->delete('simple_sitemap_views');
      $query->condition($condition);
    }
    $query->execute();
  }

  /**
   * Get all display plugins that use the route.
   *
   * @return array
   *   An array with plugin identifiers.
   */
  public function getDisplayPathPluginIds() {
    static $plugin_ids = [];
    if (empty($plugin_ids)) {
      // Get all display plugins that use the route.
      $display_plugins = Views::pluginManager('display')->getDefinitions();
      foreach ($display_plugins as $plugin_id => $definition) {
        if (!empty($definition['uses_route'])) {
          $plugin_ids[$plugin_id] = $plugin_id;
        }
      }
    }
    return $plugin_ids;
  }

  /**
   * Callback for filtering view displays.
   *
   * @param array $display
   *   The display options.
   *
   * @return bool
   *   The display is valid (TRUE) or not (FALSE).
   */
  public function isValidDisplay(array $display) {
    $display_plugins = $this->getDisplayPathPluginIds();
    return !empty($display['display_plugin']) && in_array($display['display_plugin'], $display_plugins);
  }

  /**
   * Get variations for string representation of arguments.
   *
   * @param array $args
   *   Array of arguments.
   *
   * @return array
   *   Array of variations of the string representation of arguments.
   */
  public function getArgumentsStringVariations(array $args) {
    $variations = [];
    for ($length = 1; $length <= count($args); $length++) {
      $args_slice = array_slice($args, 0, $length);
      $variations[] = $this->convertArgumentsArrayToString($args_slice);
    }
    return $variations;
  }

  /**
   * Converts an array of arguments to a string.
   *
   * @param array $args
   *   Array of arguments to convert.
   *
   * @return string
   *   A string representation of the arguments.
   */
  protected function convertArgumentsArrayToString(array $args) {
    return implode(self::ARGUMENT_SEPARATOR, $args);
  }

  /**
   * Converts a string with arguments to an array.
   *
   * @param string $args
   *   A string representation of the arguments to convert.
   *
   * @return array
   *   Array of arguments.
   */
  protected function convertArgumentsStringToArray($args) {
    return explode(self::ARGUMENT_SEPARATOR, $args);
  }

}
