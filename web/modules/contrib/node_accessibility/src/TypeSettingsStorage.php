<?php

namespace Drupal\node_accessibility;

use Drupal\quail_api\QuailApiSettings;

/**
 * Class TypeSettingsStorage.
 *
 * This is implementation is a partial migration from the old drupal 7 design
 * to a drupal 8 design.
 */
class TypeSettingsStorage {
  const DEFAULT_ENABLED = 'disabled';
  const DEFAULT_METHOD = 'quail_api_method_immediate';

  private static $title_block_options = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p'];

  /**
   * The node type setting.
   *
   * @var null|string
   */
  protected $nodeType;

  /**
   * The enabled setting.
   *
   * @var string
   */
  protected $enabled;

  /**
   * The method setting.
   *
   * @var string
   */
  protected $method;

  /**
   * The format content setting.
   *
   * @var null|string
   */
  protected $formatContent;

  /**
   * The format results setting.
   *
   * @var null|string
   */
  protected $formatResults;

  /**
   * The format title results setting.
   *
   * @var null|string
   */
  protected $formatTitleResults;

  /**
   * The title block setting.
   *
   * @var null|string
   */
  protected $titleBlock;

  /**
   * The standards setting.
   *
   * @var array
   */
  protected $standards;

  /**
   * Class constructor.
   */
  public function __construct($nodeType = NULL, $enabled = NULL, $method = NULL, $formatContent = NULL, $formatResults = NULL, $formatTitleResults = NULL, $titleBlock = NULL, array $standards = []) {
    $this->nodeType = (is_null($nodeType) || is_string($nodeType)) ? $nodeType : NULL;
    $this->enabled = (is_null($enabled) || is_string($enabled)) ? $enabled : static::DEFAULT_ENABLED;
    $this->method = (is_null($method) || is_string($method)) ? $method : static::DEFAULT_METHOD;
    $this->formatContent = (is_null($formatContent) || is_string($formatContent)) ? $formatContent : NULL;
    $this->formatResults = (is_null($formatResults) || is_string($formatResults)) ? $formatResults : NULL;
    $this->formatTitleResults = (is_null($formatTitleResults) || is_string($formatTitleResults)) ? $formatTitleResults : NULL;
    $this->titleBlock = (is_null($titleBlock) || is_string($titleBlock)) ? $titleBlock : NULL;
    $this->standards = $standards;
  }

  /**
   * Class destructor.
   */
  public function __destruct() {
    $this->nodeType = NULL;
    $this->enabled = NULL;
    $this->method = NULL;
    $this->formatContent = NULL;
    $this->formatResults = NULL;
    $this->formatTitleResults = NULL;
    $this->titleBlock = NULL;
    $this->standards = [];
  }

  /**
   * Loads the node accessibility node type settings for a specific type.
   *
   * If nodeType does not exist or there is an error, then nodeType array key
   * will be set to NULL.
   *
   * @param string $nodeType
   *    The node type machine name.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  public static function load($nodeType) {
    if (!is_string($nodeType) || empty($nodeType)) {
      return $results;
    }

    $result = new static();

    try {
      $query = \Drupal::database()->select('node_accessibility_type_settings', 'nats');
      $query->fields('nats');
      $query->condition('node_type', $nodeType);

      $existing = $query->execute()->fetchObject();

      if ($existing) {
        if (!empty($existing->node_type)) {
          $result->setNodeType($existing->node_type);
        }

        if (!empty($existing->enabled)) {
          $result->setEnabled($existing->enabled);
        }
        else {
          $result->setEnabled(static::DEFAULT_ENABLED);
        }

        if (!empty($existing->method)) {
          $result->setMethod($existing->method);
        }
        else {
          $result->setMethod(static::DEFAULT_METHOD);
        }

        if (!empty($existing->format_content)) {
          $result->setFormatContent($existing->format_content);
        }

        if (!empty($existing->format_results)) {
          $result->setFormatResults($existing->format_results);
        }

        if (!empty($existing->title_block)) {
          $result->setTitleBlock($existing->title_block);
        }

        if (!empty($existing->standards)) {
          $result->setStandards(json_decode($existing->standards, TRUE));
        }
      }
    }
    catch (Exception $e) {
      \Drupal::logger('node_accessibility')->error("Failed to select from {node_accessibility_type_settings} table, exception: @exception.", ['@exception' => $e->getMessage()]);
    }
    catch (Error $e) {
      \Drupal::logger('node_accessibility')->error("Failed to select from {node_accessibility_type_settings} table, exception: @exception.", ['@exception' => $e->getMessage()]);
    }

    return $result;
  }

  /**
   * Convenience function to return settings as an array.
   *
   * If nodeType does not exist or there is an error, then node_type array key
   * will be set to NULL.
   *
   * @param string $nodeType
   *    The node type machine name.
   *
   * @return array()
   *   An array of settings is always returned.
   *
   * @see: self::load()
   */
  public static function loadAsArray($nodeType) {
    $result = static::load($nodeType);
    return $result->toArray();
  }

  /**
   * Loads the node accessibility node type settings for a specific node.
   *
   * If nodeType does not exist or there is an error, then the value will be
   * set to NULL.
   *
   * @param int $nid
   *    The numeric node id.
   *
   * @return array
   *   An array of node type settings.
   *   This always returns a populated array.
   *   On error or invalid data, the default settings are returned.
   */
  public static function loadByNode($nid) {
    $node_type = NULL;
    try {
      $query = \Drupal::database()->select('node', 'n');
      $query->addField('n', 'type', 'node_type');
      $query->condition('n.nid', $nid);
      $existing = $query->execute()->fetchObject();

      if ($existing) {
        $node_type = $existing->node_type;
      }
    }
    catch (Exception $e) {
      \Drupal::logger('node_accessibility')->error("Failed to select from {node} table, exception: @exception.", ['@exception' => $e->getMessage()]);
    }
    catch (Error $e) {
      \Drupal::logger('node_accessibility')->error("Failed to select from {node} table, exception: @exception.", ['@exception' => $e->getMessage()]);
    }

    return static::load($node_type);
  }

  /**
   * Convenience function to load settings by node as an array.
   *
   * If nodeType does not exist or there is an error, then node_type array key
   * will be set to NULL.
   *
   * @param int $nid
   *    The numeric node id.
   *
   * @return array
   *   An array of node type settings.
   *   This always returns a populated array.
   *   On error or invalid data, the default settings are returned.
   *
   * @see: self::loadByNode()
   */
  public static function loadByNodeAsArray($nid) {
    $node_type = NULL;
    try {
      $query = \Drupal::database()->select('node', 'n');
      $query->addField('n', 'type', 'node_type');
      $query->condition('n.nid', $nid);

      $existing = $query->execute()->fetchObject();

      if ($existing) {
        $node_type = $existing->node_type;
      }
    }
    catch (Exception $e) {
      \Drupal::logger('node_accessibility')->error("Failed to select from {node} table, exception: @exception.", ['@exception' => $e->getMessage()]);
    }
    catch (Error $e) {
      \Drupal::logger('node_accessibility')->error("Failed to select from {node} table, exception: @exception.", ['@exception' => $e->getMessage()]);
    }

    return static::loadAsArray($node_type);
  }

  /**
   * Insert or Update an entry to the database.
   *
   * All existing values are reset to defaults before assigning fields.
   *
   * @param array|TypeSettingsStorage $fields
   *   An array of fields to assign or update the node type settings.
   *
   * @param bool
   *   TRUE on success, FALSE otherwise.
   */
  public static function merge($fields) {
    if (is_array($fields)) {
      $sanitized = static::sanitizeFields($fields);
    }
    elseif ($fields instanceof TypeSettingsStorage) {
      $fields_array = $fields->toArray();
      $sanitized = static::sanitizeFields($fields_array);
    }
    else {
      return FALSE;
    }

    if (is_null($sanitized['node_type'])) {
      return FALSE;
    }

    $existing = static::load($sanitized['node_type']);

    try {
      if (empty($existing->getNodeType())) {
        \Drupal::database()->insert('node_accessibility_type_settings')
          ->fields($sanitized)
          ->execute();
      }
      else {
        \Drupal::database()->update('node_accessibility_type_settings')
          ->condition('node_type', $sanitized['node_type'])
          ->fields($sanitized)
          ->execute();
      }
    }
    catch (Exception $e) {
      \Drupal::logger('node_accessibility')->error("Failed to insert or update on {node_accessibility_type_settings} table, exception: @exception.", ['@exception' => $e->getMessage()]);
      return FALSE;
    }
    catch (Error $e) {
      \Drupal::logger('node_accessibility')->error("Failed to insert or update on {node_accessibility_type_settings} table, exception: @exception.", ['@exception' => $e->getMessage()]);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Delete an entry from the database.
   *
   * @param array $entry
   *   An array containing at least the person identifier 'pid' element of the
   *   entry to delete.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  public static function delete(string $nodeType) {
    if (empty($nodeType)) {
      return FALSE;
    }

    try {
      \Drupal::database()->delete('node_accessibility_type_settings')
        ->condition('node_type', $nodeType)
        ->execute();
    }
    catch (Exception $e) {
      \Drupal::logger('node_accessibility')->error("Failed to delete on {node_accessibility_type_settings} table, exception: @exception.", ['@exception' => $e->getMessage()]);
      return FALSE;
    }
    catch (Error $e) {
      \Drupal::logger('node_accessibility')->error("Failed to delete on {node_accessibility_type_settings} table, exception: @exception.", ['@exception' => $e->getMessage()]);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Get the node type string.
   *
   * @return string|null
   *   Node type string is returned if assigned.
   *   Otherwise NULL is returned.
   */
  public function getNodeType() {
    return $this->nodeType;
  }

  /**
   * Get the enabled string.
   *
   * @return string|null
   *   Enabled string is returned if assigned.
   *   Otherwise NULL is returned.
   */
  public function getEnabled() {
    return $this->enabled;
  }

  /**
   * Get the method string.
   *
   * @return string|null
   *   Node type string is returned if assigned.
   *   Otherwise NULL is returned.
   */
  public function getMethod() {
    return $this->method;
  }

  /**
   * Get the format content string.
   *
   * @return string|null
   *   Node type string is return if assigned.
   *   Otherwise NULL is returned.
   */
  public function getFormatContent() {
    return $this->formatContent;
  }

  /**
   * Get the format results string.
   *
   * @return string|null
   *   Node type string is return if assigned.
   *   Otherwise NULL is returned.
   */
  public function getFormatResults() {
    return $this->formatResults;
  }

  /**
   * Get the format title results string.
   *
   * @return string|null
   *   Node type string is return if assigned.
   *   Otherwise NULL is returned.
   */
  public function getFormatTitleResults() {
    return $this->formatTitleResults;
  }

  /**
   * Get the title block string.
   *
   * @return string|null
   *   Node type string is return if assigned.
   *   Otherwise NULL is returned.
   */
  public function getTitleBlock() {
    return $this->titleBlock;
  }

  /**
   * Get the standards array.
   *
   * @return array
   *   Standards array is returned.
   */
  public function getStandards() {
    return $this->standards;
  }

  /**
   * Return current settings as an array.
   *
   * @return array
   *   Current settings.
   */
  public function toArray() {
    $enabled = $this->getEnabled();
    if (is_null($enabled)) {
      $enabled = 'disbled';
    }

    $method = $this->getMethod();
    if (is_null($method)) {
      $method = 'quail_api_method_immediate';
    }

    return [
      'node_type' => $this->getNodeType(),
      'enabled' => $enabled,
      'method' => $method,
      'format_content' => $this->getFormatContent(),
      'format_results' => $this->getFormatResults(),
      'title_block' => $this->getTitleBlock(),
      'standards' => $this->getStandards(),
    ];
  }

  /**
   * Assign the node type string.
   *
   * @param string|null $nodeType
   *   Value to assign.
   */
  public function setNodeType($nodeType) {
    if (is_null($nodeType) || is_string($nodeType)) {
      $this->nodeType = $nodeType;
    }
  }

  /**
   * Assign the enabled string.
   *
   * @param string|null $enabled
   *   Value to assign.
   */
  public function setEnabled($enabled) {
    if (is_null($enabled) || is_string($enabled)) {
      $this->enabled = $enabled;
    }
  }

  /**
   * Assign the method string.
   *
   * @param string|null $method
   *   Value to assign.
   */
  public function setMethod($method) {
    if (is_null($method) || is_string($method)) {
      $this->method = $method;
    }
  }

  /**
   * Assign the format content string.
   *
   * @param string|null $formatContent
   *   Value to assign.
   */
  public function setFormatContent($formatContent) {
    if (is_null($formatContent) || is_string($formatContent)) {
      $this->formatContent = $formatContent;
    }
  }

  /**
   * Assign the format results string.
   *
   * @param string|null $formatResults
   *   Value to assign.
   */
  public function setFormatResults($formatResults) {
    if (is_null($formatResults) || is_string($formatResults)) {
      $this->formatResults = $formatResults;
    }
  }

  /**
   * Assign the format title results string.
   *
   * @param string|null $formatTitleResults
   *   Value to assign.
   */
  public function setFormatTitleResults($formatTitleResults) {
    if (is_null($formatTitleResults) || is_string($formatTitleResults)) {
      $this->formatTitleResults = $formatTitleResults;
    }
  }

  /**
   * Assign the title block string.
   *
   * @param string|null $titleBlock
   *   Value to assign.
   */
  public function setTitleBlock($titleBlock) {
    if (is_null($titleBlock) || is_string($titleBlock)) {
      $this->titleBlock = $titleBlock;
    }
  }

  /**
   * Assign the standards array.
   *
   * @param array $standards
   *   Value array is returned.
   */
  public function setStandards(array $standards) {
    $this->standards = $standards;
  }

  /**
   * Initializes and sanitizes the array of fields to assign.
   *
   * All existing values are reset to defaults before assigning fields.
   *
   * @param array $fields
   *   An array of all fields to assign.
   *
   * return array
   *   An array of initialized and sanitized fields.
   */
  private static function sanitizeFields(Array $fields) {
    $sanitized = [];
    $sanitized['node_type'] = NULL;
    $sanitized['enabled'] = static::DEFAULT_ENABLED;
    $sanitized['method'] = static::DEFAULT_METHOD;
    $sanitized['format_content'] = '';
    $sanitized['format_results'] = '';
    $sanitized['title_block'] = '';
    $sanitized['standards'] = json_encode([]);

    if (!empty($fields['node_type']) && is_string($fields['node_type'])) {
      $sanitized['node_type'] = $fields['node_type'];
    }

    if (!empty($fields['method']) && is_string($fields['method'])) {
      $methods = QuailApiSettings::get_validation_methods_list();

      if (array_key_exists($fields['method'], $methods)) {
        $sanitized['method'] = $fields['method'];
      }
    }

    if (!empty($fields['format_content']) && is_string($fields['format_content'])) {
      $sanitized['format_content'] = $fields['format_content'];
    }

    if (!empty($fields['format_results']) && is_string($fields['format_results'])) {
      $sanitized['format_results'] = $fields['format_results'];
    }

    if (!empty($fields['title_block']) && is_string($fields['title_block'])) {
      if (in_array($fields['title_block'], self::$title_block_options)) {
        $sanitized['title_block'] = $fields['title_block'];
      }
    }

    if (!empty($fields['enabled']) && is_string($fields['enabled'])) {
      if ($fields['enabled'] == 'disabled' || $fields['enabled'] == 'optional' || $fields['enabled'] == 'required') {
        $sanitized['enabled'] = $fields['enabled'];
      }
    }

    if (!empty($fields['standards']) && is_array($fields['standards'])) {
      $standards = QuailApiSettings::get_standards_list();
      $standards_array = array();
      foreach ($fields['standards'] as $standard_name => $standard_value) {
        if (is_numeric($standard_value)) {
          continue;
        }

        if (array_key_exists($standard_name, $standards)) {
          $standards_array[$standard_name] = $standard_name;
        }
      }
      $sanitized['standards'] = json_encode($standards_array);
    }

    return $sanitized;
  }
}
