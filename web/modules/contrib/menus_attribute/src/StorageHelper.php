<?php

namespace Drupal\menus_attribute;

/**
 * Helper Class for database interaction.
 */
class StorageHelper {

  public $db;

  /**
   * Function to create object of storage helper class.
   */
  public static function instance() {
    static $inst = NULL;
    if ($inst === NULL) {
      $inst = new StorageHelper();
    }
    return $inst;
  }

  /**
   * Constructor of storage helper class.
   */
  public function __construct() {
    $this->db = \Drupal::database();
  }

  /**
   * Function to getData.
   */
  public function getData($plugin_id) {
    $data = $this->db->select('menus_attribute', 'ma')
      ->fields('ma')
      ->condition('plugin_id', $plugin_id, '=')
      ->execute()->fetchObject();
    return $data;
  }

  /**
   * Function to check existence of a menu in our table.
   */
  public function exists($plugin_id) {
    $data = $this->db->select('menus_attribute', 'ma')
      ->fields('ma')
      ->condition('plugin_id', $plugin_id, '=')
      ->execute()->fetchField();
    return (bool) $data;
  }

  /**
   * Function to add data.
   */
  public function add(&$arr, $plugin_id) {
    $query = $this->db->insert('menus_attribute');
    $query->fields(
      [
        'plugin_id' => $plugin_id,
        'link_id' => $arr['menu_link_id'],
        'link_name' => $arr['menu_link_name'],
        'link_title' => $arr['menu_link_title'],
        'link_rel' => $arr['menu_link_rel'],
        'link_classes' => $arr['menu_link_class'],
        'link_style' => $arr['menu_link_style'],
        'link_target' => $arr['menu_link_target'],
        'link_accesskey' => $arr['menu_link_access_key'],
        'item_id' => $arr['menu_item_id'],
        'item_classes' => $arr['menu_item_class'],
        'item_style' => $arr['menu_item_style'],
      ]
    )->execute();
  }

  /**
   * Function to update data.
   */
  public function update(&$arr, $plugin_id) {
    $query = $this->db->update('menus_attribute');
    $query->fields(
      [
        'plugin_id' => $plugin_id,
        'link_id' => $arr['menu_link_id'],
        'link_name' => $arr['menu_link_name'],
        'link_title' => $arr['menu_link_title'],
        'link_rel' => $arr['menu_link_rel'],
        'link_classes' => $arr['menu_link_class'],
        'link_style' => $arr['menu_link_style'],
        'link_target' => $arr['menu_link_target'],
        'link_accesskey' => $arr['menu_link_access_key'],
        'item_id' => $arr['menu_item_id'],
        'item_classes' => $arr['menu_item_class'],
        'item_style' => $arr['menu_item_style'],
      ]
    );
    $query->condition('plugin_id', $plugin_id);
    $query->execute();
  }

}
