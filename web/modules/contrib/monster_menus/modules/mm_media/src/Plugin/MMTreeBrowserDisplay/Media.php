<?php

namespace Drupal\mm_media\Plugin\MMTreeBrowserDisplay;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\MMTreeBrowserDisplay\MMTreeBrowserDisplayInterface;
use Drupal\monster_menus\Plugin\MMTreeBrowserDisplay\Fallback;

/**
 * Provides the MM Tree display generator for pages containing Media entities.
 *
 * @MMTreeBrowserDisplay(
 *   id = "mm_tree_browser_display_media",
 *   admin_label = @Translation("MM Tree media display"),
 * )
 */
class Media extends Fallback implements MMTreeBrowserDisplayInterface {

  const BROWSER_MODE_MEDIA = 'med';

  /**
   * The database connection.
   *
   * @var Connection
   */
  protected $database;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * The HTTP query.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $httpQuery;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->entityFieldManager = \Drupal::service('entity_field.manager');
    $this->entityTypeBundleInfo = \Drupal::service('entity_type.bundle.info');
    $this->httpQuery = \Drupal::request()->query;
    $this->database = Database::getConnection();
  }

  /**
   * @inheritDoc
   */
  public static function supportedModes() {
    return [self::BROWSER_MODE_MEDIA];
  }

  /**
   * @inheritDoc
   */
  public function label($mode) {
    return t('Select a file upload');
  }

  /**
   * {@inheritdoc}
   */
  public function alterLeftQuery($mode, $query, &$params) {
    $segments = array();
    $mime_join = $mime_where = '';
    if ($mimes = $this->getMimeTypes()) {
      $mime_join = ' INNER JOIN {file_managed} fm ON fm.fid = fu.fid';
      foreach ($mimes as &$mime) {
        $mime = addcslashes($mime, "'");
      }
      $mime_where = " AND fm.type IN('" . implode("', '", $mimes) . "')";
    }

    foreach ($this->getFields() as $alias => $fields) {
      $join = [];
      foreach ($fields as $field) {
        $join[] = '{' . $field[0] . '} ' . $field[1] . ' ON ' . $field[2];
      }
      $joined = join(' INNER JOIN ', $join);
      $segments[] = "SELECT GROUP_CONCAT(fu.fid) FROM {node_field_data} n INNER JOIN $joined INNER JOIN {file_usage} fu ON fu.type = 'media' AND fu.id = $alias.mid INNER JOIN {mm_node2tree} n2 ON n2.nid = n.nid$mime_join WHERE n.status = 1 AND n2.mmtid = o.container$mime_where";
    }

    // Ideally, we would use a UNION to get all the distinct fids, but can't
    // because then MySQL doesn't let us use t.mmtid from the outer query.
    // Instead, get a concatenated list of all the fids in nodecount and
    // squash the duplicates in PHP to get the count.
    $params[Constants::MM_GET_TREE_ADD_SELECT] = "CONCAT_WS(',', (" . join('), (', $segments) . ')) AS fid_list';
    $params[Constants::MM_GET_TREE_FILTER_NORMAL] = $params[Constants::MM_GET_TREE_FILTER_USERS] = TRUE;
  }

  /**
   * @return array
   */
  private function getFields() {
    $output = [];

    // Perform a dummy entity query, in order to have it map the table
    // relationships.
    $query = \Drupal::entityQuery(  'node');
    foreach ($this->entityFieldManager->getActiveFieldStorageDefinitions('node') as $field_name => $field_def) {
      if ($field_def->getType() == 'entity_reference' && $field_def->getSetting('target_type') == 'media') {
        $query->condition("$field_name.entity.bundle", 'image');
      }
    }

    // Sadly, entityQuery doesn't have public methods for most things, so hack
    // it.
    $prepare = new \ReflectionMethod(get_class($query), 'prepare');
    $prepare->setAccessible(true);
    $prepare->invoke($query);
    $compile = new \ReflectionMethod(get_class($query), 'compile');
    $compile->setAccessible(true);
    $compile->invoke($query);
    $rp = new \ReflectionProperty(get_class($query), 'sqlQuery');
    $rp->setAccessible(true);
    $sqlQuery = $rp->getValue($query);
    $join = [];
    $last_alias = '';
    foreach ($sqlQuery->getTables() as $table_name => $table_def) {
      if ($table_name != 'base_table' && $table_name != 'media_field_data') {
        if ($join && strpos($table_def['condition'], ' base_table.') !== FALSE) {
          $output[$last_alias] = $join;
          $join = [];
        }
        $last_alias = $table_def['alias'];
        $join[] = [ $table_def['table'], $table_def['alias'], str_replace(' base_table.', ' n.', $table_def['condition']) ];
      }
    }
    $output[$last_alias] = $join;

    return $output;
  }

  /**
   * Expand the browserFileTypes query parameter.
   *
   * @return array
   */
  private function getMimeTypes() {
    if ($types = $this->httpQuery->get('browserFileTypes')) {
      return explode(',', $types);
    }
    return array();
  }

  /**
   * @inheritDoc
   */
  public function showReservedEntries($mode) {
    return FALSE;
  }

  /**
   * Get a list of file upload thumbnails for the right hand column.
   *
   * {@inheritdoc}
   */
  public function viewRight($mode, $params, $perms, $item, $database) {
    $mmtid = $item->mmtid;
    if (!$perms[Constants::MM_PERMS_APPLY]) {
      $out = '';
      if ($mmtid > 0) {
        $out = '<div id="mmtree-browse-thumbnails"><br />' . t('You do not have permission to use the file uploads on this page.') . '</div>';
      }
      $json = array(
        'title' => mm_content_get_name($mmtid),
        'body' => $out,
      );
      return mm_json_response($json);
    }

    foreach ($this->getFields() as $alias => $fields) {
      $segment = $this->database->select('node_field_data', 'n');
      $segment->addField('fu', 'fid');
      if ($mode == self::BROWSER_MODE_MEDIA) {
        $segment->addField('fu', 'id', 'mid');
      }
      foreach ($fields as $i => $field) {
        if (!$i) {
          $segment->addTag(__FUNCTION__ . '__' . $field[0] . '.' . $field[1]);
        }
        $segment->join($field[0], $field[1], $field[2]);
      }
      $segment->join('file_usage', 'fu', "fu.type = 'media' AND fu.id = $alias.mid");
      $segment->join('mm_node2tree', 'n2', 'n2.nid = n.nid');
      $segment->condition('n.status', 1, '=')
        ->condition('n2.mmtid', $mmtid, '=');

      if (empty($query)) {
        $query = $segment;
      }
      else {
        $query->union($segment);
      }
    }

    $content = [];
    if (!empty($query)) {
      $query = $this->database->select($query, 'subquery');
      $query->addTag(__FUNCTION__);
      $query->join('file_managed', 'm', 'm.fid = subquery.fid');
      $query->fields('m');
      if ($mode == self::BROWSER_MODE_MEDIA) {
        $query->addField('subquery', 'mid');
      }

      $min_wh[0] = $this->httpQuery->getInt('browserMinW', 0);
      $min_wh[1] = $this->httpQuery->getInt('browserMinH', 0);

      if ($mimes = $this->getMimeTypes()) {
        $query->condition('m.type', $mimes, 'IN');
      }

      $result = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
        ->orderBy('changed', 'DESC')
        ->limit(mm_get_setting('nodes.nodelist_pager_limit'))
        ->execute();

      foreach ($result as $file) {
        $content['icons'][] = [
          '#theme' => 'mm_browser_thumbnail',
          '#file' => $file,
          '#style_name' => 'thumbnail',
          '#mode' => $mode,
          '#mmtid' => $mmtid,
          '#min_wh' => $min_wh,
        ];
      }
    }

    if (!$content) {
      $content = [['#prefix' => '<p>', '#markup' => t('There is no selectable content on this page.'), '#suffix' => '</p>']];
    }
    else {
      $content['pager'] = ['#type' => 'pager'];
    }

    return ['#prefix' => '<div id="mmtree-browse-thumbnails">', $content, '#suffix' => '</div>'];
  }

}
