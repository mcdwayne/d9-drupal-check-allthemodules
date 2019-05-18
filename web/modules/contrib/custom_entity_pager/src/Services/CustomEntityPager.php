<?php
namespace Drupal\custom_entity_pager\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Class CustomEntityPager.
 */
class CustomEntityPager {

  private $connection;
  private $language_manager;

  /**
   * Constructor.
   */
  public function __construct(Connection $connection, LanguageManagerInterface $language_manager) {
    $this->connection = $connection;
    $this->language_manager = $language_manager;
  }

  /**
   * @param string $content_type
   *   the name of the content type.
   */
  public function getPaginator($content_type, $current_nid, $field_order = '') {
    $result_assoc = $this->getElements($content_type, $current_nid, $field_order);
    return $this->getLastAndPrev($result_assoc, $current_nid);
  }

  /**
   * @param string $content_type
   *   the name of the content type.
   */
  public function getElements($content_type, $current_nid, $field_order) {

    $table_name = '';
    $value_field = '';

    if ($field_order != '') {
      $table_name = 'node__' . $field_order;
      $value_field = $field_order . '_value';
    }

    $id_lang = $this->language_manager->getCurrentLanguage()->getId();

    $connection = $this->connection;
    $query = $connection->select('node_field_data', 'nfd');
    $query->fields('nfd', ['nid', 'title']);

    if ($table_name != '') {
      $query->join($table_name, $table_name, $table_name . '.entity_id = nfd.nid');
    }

    $query->condition('nfd.type', $content_type);
    $query->condition('nfd.langcode', $id_lang);

    if ($value_field != '') {
      $query->orderBy($table_name . '.' . $value_field, 'ASC');
    }
    else {
      $query->orderBy('nfd.nid', 'ASC');
    }

    $result = $query->execute();

    return $result->fetchAllAssoc('nid');
  }

  /**
   * @param $result_assoc
   *   Array assoc
   *         associative array with the id as a key
   */
  public function getLastAndPrev($result_assoc, $current_nid) {

    $keys = array_keys($result_assoc);

    $prev_node_nid = '';
    $next_node_nid = '';
    $actual_pos = array_search($current_nid, $keys);

    $prev_pos = $actual_pos - 1;
    $next_pos = $actual_pos + 1;

    $limite_array = count($result_assoc) - 1;

    $element_prev = NULL;
    $element_next = NULL;

    if ($prev_pos >= 0) {
      $element_prev = $result_assoc[$keys[$prev_pos]];
    }

    if ($next_pos <= $limite_array) {
      $element_next = $result_assoc[$keys[$next_pos]];
    }

    return [
      'prev' => $element_prev,
      'next' => $element_next,
    ];
  }

}
