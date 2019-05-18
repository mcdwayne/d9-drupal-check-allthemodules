<?php

namespace Drupal\monster_menus\Entity;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;
use Drupal\monster_menus\Constants;

/**
 * Defines the extended settings trait for MMTree.
 *
 * @ingroup monster_menus
 */
trait MMTreeExtendedSettingsTrait {

  /**
   * @var array Extended settings array.
   */
  private $extendedSettings;

  /**
   * @var array
   *   Extended field definitions.
   */
  private static $extendedFields = [
    'flags' => ['mm_tree_flags', 'f', 'f.mmtid = t.mmtid', "GROUP_CONCAT(DISTINCT CONCAT_WS('|1', f.flag, f.data) SEPARATOR '|2')", 'expandRowFlags'],
    'archive' => ['mm_archive', 'a', 'a.main_mmtid = t.mmtid OR a.archive_mmtid = t.mmtid', ['main_mmtid', 'archive_mmtid', 'frequency', 'main_nodes']],
    'cascaded' => ['mm_cascaded_settings', 's', 's.mmtid = t.mmtid', "GROUP_CONCAT(DISTINCT CONCAT_WS('|1', s.name, s.data_type, s.multiple, s.array_key, s.data) SEPARATOR '|2')", 'expandRowCascaded'],
    'blocks' => ['mm_tree_block', 'b', 'b.mmtid = t.mmtid', ['MAX(bid)' => 'menu_start', 'max_depth', 'max_parents']],
    'vgroup' => ['mm_vgroup_query', 'v', 'v.vgid = g.vgid', ['MAX(field)' => 'qfield', 'qfrom']],
  ];

  /**
   * @var array
   *   Extended field keys.
   */
  private static $extendedFieldKeys = [
    'flags',
    'main_mmtid', 'archive_mmtid', 'frequency', 'main_nodes',
    'cascaded',
    'menu_start', 'max_depth', 'max_parents',
    'qfield', 'qfrom',
    'members',
    'perms',
    'large_group_form_token',
  ];

  /**
   * @var array
   *   Default extended settings.
   */
  private static $defaultSettings = [
    'cascaded' => [],
    'flags' => [],
    'max_depth' => -1,
    'max_parents' => -1,
    'members' => [],
    'menu_start' => Constants::MM_MENU_UNSET,
    'perms' => [
      Constants::MM_PERMS_WRITE => ['groups' => [], 'users' => []],
      Constants::MM_PERMS_SUB   => ['groups' => [], 'users' => []],
      Constants::MM_PERMS_APPLY => ['groups' => [], 'users' => []],
      Constants::MM_PERMS_READ  => ['groups' => [], 'users' => []],
    ],
    'qfield' => '',
    'qfrom' => '',
    'main_mmtid' => 0,
    'archive_mmtid' => 0,
    'frequency' => '',
    'main_nodes' => 0,
    'large_group_form_token' => '',
  ];

  /** @var Connection */
  private $database;

  /**
   * Expand 'flags' extended setting.
   *
   * @param string $field
   *   The field to be expanded.
   */
  private function expandRowFlags(&$field) {
    preg_match_all('/(?:(.*?)\|1(.*?)(?:\|2|$))/', $field, $matches);
    $field = $matches[0] ? array_combine($matches[1], $matches[2]) : array();
  }

  /**
   * Expand 'cascaded' extended setting.
   *
   * @param string $field
   *   The field to be expanded.
   */
  private function expandRowCascaded(&$field) {
    $cascaded = [];
    if ($field) {
      foreach (explode('|2', $field) as $r) {
        $r = (object) array_combine(['name', 'data_type', 'multiple', 'array_key', 'data'], explode('|1', $r, 5));
        if ($r->data_type == 'int') {
          $r->data = (int) $r->data;
        }

        if ($r->multiple) {
          if (!isset($cascaded[$r->name]) || !is_array($cascaded[$r->name])) {
            $cascaded[$r->name] = array();
          }
          if ($r->array_key != '') {
            $cascaded[$r->name][$r->array_key] = $r->data;
          }
          else {
            $cascaded[$r->name][] = $r->data;
          }
        }
        else {
          $cascaded[$r->name] = $r->data;
        }
      }
    }
    $field = $cascaded;
  }

  /**
   * Get all extended settings.
   *
   * @return array
   *   The extended settings.
   */
  public function &getExtendedSettings() {
    if (!$this->id()) {
      $this->extendedSettings = static::$defaultSettings;
    }
    else if (!is_array($this->extendedSettings)) {
      $query = $this->getDatabase()
        ->select('mm_tree', 't');
      $query->condition('t.mmtid', $this->id());
      $query->leftJoin('mm_group', 'g', 'g.gid = t.mmtid');
      foreach (static::$extendedFields as $field => $data) {
        $query->leftJoin($data[0], $data[1], $data[2]);
        if (is_string($data[3])) {
          $query->addExpression($data[3], $field);
        }
        else if ($data[3]) {
          foreach ($data[3] as $key => $name) {
            if (is_string($key)) {
              $query->addExpression($key, $name);
            }
            else {
              $query->addExpression("MAX($name)", $name);
            }
          }
        }
      }
      $query->groupBy('t.mmtid');
      $row = $query->execute()->fetchAssoc();
      foreach (static::$extendedFields as $field => $data) {
        if (isset($data[4]) && is_callable($data[4])) {
          $this->{$data[4]}($row[$field]);
        }
      }
      $row['perms'] = mm_content_get_perms($this->id(), TRUE, TRUE, FALSE, $this->getDatabase());
      $row['members'] = mm_content_get_uids_in_group($this->id(), NULL, TRUE, FALSE, FALSE, $this->getDatabase());
      $this->extendedSettings = $row + static::$defaultSettings;
    }
    return $this->extendedSettings;
  }

  /**
   * Save all extended settings. Note that if the entity is new, the remainder
   * of it must be saved first, so that it will have an ID before this function
   * is called.
   */
  public function saveExtendedSettings() {
    // If this is an import, the extendedSettings element is set. Copy it into
    // the main property.
    if ($settings = $this->get('extendedSettings')->value) {
      $this->extendedSettings = $settings;
    }
    if (is_array($this->extendedSettings) && ($mmtid = $this->id())) {
      $extended = $this->getExtendedSettings();
      $clear_old = !$this->isNew();
      $database = $this->getDatabase();
      mm_content_set_flags($mmtid, $extended['flags'], $clear_old, $database);
      mm_content_set_perms($mmtid, $extended['perms'], $this->isGroup(), $clear_old, $database);
      mm_content_set_cascaded_settings($mmtid, $extended['cascaded'], $clear_old, $database);
      if ($this->isGroup()) {
        mm_content_set_group_members($mmtid, NULL, $extended['qfield'], $extended['qfrom'], $extended['members'], $extended['large_group_form_token'], $database);
      }
      else {
        $database
          ->delete('mm_tree_block')
          ->condition('mmtid', $mmtid)
          ->execute();
        if ($extended['menu_start'] && $extended['menu_start'] != Constants::MM_MENU_UNSET || isset($extended['max_depth']) && $extended['max_depth'] >= 0 || isset($extended['max_parents']) && $extended['max_parents'] >= 0) {
          $database
            ->insert('mm_tree_block')
            ->fields(array(
              'mmtid' => $mmtid,
              'bid' => is_null($extended['menu_start']) ? Constants::MM_MENU_UNSET : $extended['menu_start'],
              'max_depth' => $extended['max_depth'],
              'max_parents' => $extended['max_parents'],
            ))
            ->execute();
        }

        $database->delete('mm_archive')
          ->condition('main_mmtid', $mmtid)
          ->execute();
        if ($extended['archive_mmtid']) {
          $this->database->insert('mm_archive')
            ->fields(array(
              'main_mmtid' => $mmtid,
              'archive_mmtid' => $extended['archive_mmtid'],
              'frequency' => $extended['frequency'],
              'main_nodes' => $extended['main_nodes'],
            ))
            ->execute();
          // Don't allow custom ordering
          mm_content_reset_custom_node_order($mmtid);
        }
      }
    }
  }

  /**
   * Sets all extended settings.
   *
   * @param array $settings
   *   Array containing extended settings.
   */
  public function setExtendedSettings(array $settings) {
    $this->extendedSettings = $settings;
  }

  /**
   * Delete all extended settings from the database, usually when the entity is
   * being deleted.
   */
  public function deleteExtendedSettings() {
    static::deleteMultipleExtendedSettings([$this->id()]);
  }

  /**
   * Delete the extended settings for multiple MMTree entities.
   *
   * @param array $mmtids
   *   The list of tree entries to delete.
   */
  public static function deleteMultipleExtendedSettings($mmtids) {
    $database = Database::getConnection();

    $database->delete('mm_tree_flags')
      ->condition('mmtid', $mmtids, 'IN')
      ->execute();
    $database->delete('mm_tree_block')
      ->condition('mmtid', $mmtids, 'IN')
      ->execute();
    $or = new Condition('OR');
    $database->delete('mm_archive')
      ->condition($or
        ->condition('main_mmtid', $mmtids, 'IN')
        ->condition('archive_mmtid', $mmtids, 'IN')
      )
      ->execute();
    $database->delete('mm_cascaded_settings')
      ->condition('mmtid', $mmtids, 'IN')
      ->execute();

    // Remove virtual groups; mm_virtual_group is cleaned up automatically
    // in cron. It's far faster to use $database->query(), since DBTNG doesn't
    // allow JOIN.
    if ($database->databaseType() == 'mysql') {
      $database->query('DELETE vg FROM {mm_vgroup_query} vg INNER JOIN {mm_group} g ON g.vgid = vg.vgid WHERE g.gid IN(:mmtids[])', array(':mmtids[]' => $mmtids));
    }
    else {
      // DELETE FROM {mm_vgroup_query} WHERE
      //   (SELECT 1 FROM {mm_group} g WHERE g.vgid = {mm_vgroup_query}.vgid
      //     AND g.gid IN(:mmtids))
      $mm_group = $database->select('mm_group', 'g');
      $mm_group->addExpression(1);
      $mm_group->where('g.vgid = {mm_vgroup_query}.vgid')
        ->condition('g.gid', $mmtids, 'IN');
      $database->delete('mm_vgroup_query')
        ->condition($mm_group)
        ->execute();
    }
    // Remove group membership.
    $database->delete('mm_group')
      ->condition('gid', $mmtids, 'IN')
      ->execute();
  }

  /**
   * Gets the database connection.
   *
   * @return Connection
   */
  public function getDatabase() {
    if (empty($this->database)) {
      $this->database = Database::getConnection();
    }
    return $this->database;
  }

  /**
   * Sets the database connection.
   */
  public function setDatabase(Connection $new_database) {
    $this->database = $new_database;
  }

}
