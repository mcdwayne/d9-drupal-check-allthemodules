<?php
namespace Drupal\monster_menus\GetTreeIterator;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\GetTreeIterator;

class SitemapDumpIter extends GetTreeIterator {

  /**
   * Database Service Object.
   *
   * @var Connection
   */
  protected $database;

  protected $fp, $file_path, $tree_path, $max_level, $exclude, $in_home;

  /**
   * Constructs a SitemapDumpIter object.
   *
   * @param $max_level
   *   Maximum depth to dump
   */
  public function __construct($max_level) {
    $this->file_path = 'public://sitemap.xml';
    $this->fp = fopen($this->file_path . '.temp', 'w');
    if ($this->fp) {
      $this->tree_path = [];
      fwrite($this->fp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n");
    }
    else {
      \Drupal::logger('mm')->error('Could not create @path', ['@path' => $this->file_path . '.temp']);
      exit();
    }
    $this->max_level = $max_level;
    $this->exclude = mm_get_setting('sitemap.exclude_list');
    $this->database = Database::getConnection();
  }

  /**
   * {@inheritdoc}
   */
  public function iterate($item) {
    // Root level.
    if (!$item->level) {
      return 1; // No error.
    }

    if ($item->level == 1) {
      if ($item->mmtid == mm_home_mmtid()) {
        $this->tree_path = [];
        $this->in_home = 1;
      }
      else {
        $this->in_home = 0;
      }
    }

    // Not publicly readable, hidden, or recycled? Not the normal menu block?
    if ($item->level > $this->max_level + $this->in_home || !$item->perms[Constants::MM_PERMS_READ] || $item->hidden || $item->perms[Constants::MM_PERMS_IS_RECYCLED] || !in_array($item->bid, [Constants::MM_MENU_UNSET, Constants::MM_MENU_DEFAULT, Constants::MM_MENU_BID]) ) {
      return -1; // Skip children.
    }

    // Only traverse /users and the current site home.
    if ($item->level == 1 && $item->mmtid != mm_content_users_mmtid() && $item->mmtid != mm_home_mmtid()) {
      return -1; // Skip children.
    }

    if ($item->level >= 1 + $this->in_home) {
      $node_name = empty($item->alias) ? $item->mmtid : $item->alias;
      array_splice($this->tree_path, $item->level - 1 - $this->in_home);
      $this->tree_path[] = $node_name;
    }
    $path = join('/', $this->tree_path);

    // Check the exclusions list.
    if (in_array($path, $this->exclude)) {
      return -1; // Skip children.
    }

    // Figure out if the node or container is newer and use that date.
    // Comment this out if it is too intensive (not required for the xml spec).
    $select = $this->database->select('mm_tree', 't');
    $select->leftJoin('mm_tree_revision', 'r', 'r.vid = t.vid');
    $select->leftJoin('mm_node2tree', 'm', 't.mmtid = m.mmtid');
    $select->leftJoin('node', 'n', 'm.nid = n.nid');
    $select->condition('t.mmtid', $item->mmtid)
      ->groupBy('t.mmtid')
      ->groupBy('r.mtime');
    $select->addField('r', 'mtime', 't_changed');
    $select->addExpression('MAX(n.changed)', 'n_changed');
    $mod_time = $select->execute()->fetchAssoc();
    $max_mod_time = max($mod_time['t_changed'], $mod_time['n_changed']);

    fwrite($this->fp, "<url>\n");
    fwrite($this->fp, "\t<loc>" . htmlspecialchars(base_path() . $path, ENT_QUOTES) . "</loc>\n");
    if (!is_null($max_mod_time)) {
      fwrite($this->fp, "\t<lastmod>" . date('Y-m-d', $max_mod_time) . "</lastmod>\n");
    }
    // fwrite($this->fp, "\t<priority>".round(1/$item->level, 2)."</priority>\n");
    fwrite($this->fp, "</url>\n");

    return 1; // No error.
  }

  public function finish() {
    fwrite($this->fp, "</urlset>\n");
    fclose($this->fp);
    $source = $this->file_path . '.temp'; // Necessary for pass-by-reference.
    file_unmanaged_move($source, $this->file_path, FILE_EXISTS_REPLACE);
  }

}