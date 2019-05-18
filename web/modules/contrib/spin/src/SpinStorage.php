<?php

namespace Drupal\spin;

/**
 * Storage class.
 */
class SpinStorage {

  /**
   * Set the spin.
   *
   * @param array $fields
   *   The spin field array.
   *
   * @return Drupal\Core\Database\Query\InsertQuery
   *   An InsertQuery object for this connection.
   */
  public static function createSpin(array $fields) {
    return db_insert('spin')->fields($fields)->execute();
  }

  /**
   * Delete a spin.
   *
   * @param int $sid
   *   The spin ID.
   *
   * @return \Drupal\Core\Database\Query\DeleteQuery
   *   A new DeleteQuery object for this connection.
   */
  public static function deleteSpin($sid) {
    return db_delete('spin')->condition('sid', $sid)->execute();
  }

  /**
   * Fetch the serialized display option data.
   *
   * @param int $sid
   *   The spin ID.
   *
   * @return string
   *   The serialized display option data.
   */
  public static function getData($sid) {
    return db_query("SELECT data FROM {spin} WHERE sid = :sid", [':sid' => $sid])->fetchField();
  }

  /**
   * Fetch the serialized display option data.
   *
   * @param string $type
   *   The spin profile type, ("slideshow" or "spin").
   *
   * @return string
   *   The serialized display option data.
   */
  public static function getDefaultData($type) {
    return db_query("SELECT data FROM {spin} WHERE name = 'default' AND type = :type", [':type' => $type])->fetchField();
  }

  /**
   * Fetch the profile string.
   *
   * @param string $type
   *   The spin profile type, ("slideshow" or "spin").
   *
   * @return string
   *   The profile string.
   */
  public static function getDefaultProfile($type) {
    return db_query("SELECT profile FROM {spin} WHERE name = 'default' AND type = :type", [':type' => $type])->fetchField();
  }

  /**
   * Fetch the spin label.
   *
   * @param int $sid
   *   The spin ID.
   *
   * @return string
   *   The spin label.
   */
  public static function getLabel($sid) {
    return db_query("SELECT label FROM {spin} WHERE sid = :sid", [':sid' => $sid])->fetchField();
  }

  /**
   * Fetch a list of spins.
   *
   * @param string $type
   *   The spin profile type, ("slideshow" or "spin").
   *
   * @return string
   *   An array of spin data.
   */
  public static function getList($type) {
    return db_query("SELECT sid, name, label, type FROM {spin} WHERE type = :type ORDER BY type, label", [':type' => $type])->fetchAll();
  }

  /**
   * Fetch the spin name.
   *
   * @param int $sid
   *   The spin ID.
   *
   * @return string
   *   The spin name.
   */
  public static function getName($sid) {
    return db_query("SELECT name FROM {spin} WHERE sid = :sid", [':sid' => $sid])->fetchField();
  }

  /**
   * Fetch select options.
   *
   * @param string $type
   *   The spin profile type, ("slideshow" or "spin").
   *
   * @return array
   *   An sid to name key value pairs.
   */
  public static function getOptions($type) {
    return db_query("SELECT sid, label FROM {spin} WHERE type = :type", [':type' => $type])->fetchAllKeyed();
  }

  /**
   * Fetch profile string.
   *
   * @param int $sid
   *   The spin ID.
   *
   * @return string
   *   The profile string.
   */
  public static function getProfile($sid) {
    return db_query("SELECT profile FROM {spin} WHERE sid = :sid", [':sid' => $sid])->fetchField();
  }

  /**
   * Fetch select options.
   *
   * @param string $name
   *   The spin name.
   * @param string $type
   *   The spin profile type, ("slideshow" or "spin").
   *
   * @return bool
   *   True if the name and type exist.
   */
  public static function nameExists($name, $type) {
    return (bool) db_query("SELECT 1 FROM {spin} WHERE name = :name AND type = :type", [':name' => $name, ':type' => $type])->fetchField();
  }

  /**
   * Set the spin.
   *
   * @param int $sid
   *   The spin ID.
   * @param array $fields
   *   The spin field array.
   *
   * @return Drupal\Core\Database\Query\Update
   *   A new Update object for this connection.
   */
  public static function updateSpin($sid, array $fields) {
    return db_update('spin')->fields($fields)->condition('sid', $sid)->execute();
  }

}
