<?php

namespace Drupal\monster_menus\Plugin\MMTreeBrowserDisplay;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\file\Entity\File;
use Drupal\filter\Render\FilteredMarkup;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\MMTreeBrowserDisplay\MMTreeBrowserDisplayInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\NodeType;

/**
 * Provides the MM Tree display generator for node entities.
 *
 * @MMTreeBrowserDisplay(
 *   id = "mm_tree_browser_display_node",
 *   admin_label = @Translation("MM Tree node display"),
 * )
 */
class Nodes extends Fallback implements MMTreeBrowserDisplayInterface {

  const BROWSER_MODE_NODE = 'nod';

  public static function supportedModes() {
    return [self::BROWSER_MODE_NODE];
  }

  /**
   * @inheritDoc
   */
  public function label($mode) {
    return t('Select a piece of content');
  }

  public function alterLeftQuery($mode, $query, &$params) {
    $allowed_node_types = array();
    if ($field_id = $query->get('browserFieldID')) {
      list($field_name, $bundle) = explode(',', $field_id);
      if ($widget = EntityFormDisplay::load('node.' . $bundle . '.default')->getRenderer($field_name)) {
        foreach ($widget->getSetting('mm_list_nodetypes') as $node_type) {
          if (!empty($node_type)) {
            $allowed_node_types[] = "'" . $node_type . "'";
          }
        }
      }
    }
    $wheres = $allowed_node_types ? 'AND nd.type IN (' . implode(', ', $allowed_node_types) . ') ' : '';
    $wheres .= 'AND nfd.status = 1';
    $params[Constants::MM_GET_TREE_ADD_SELECT] = "(SELECT COUNT(DISTINCT n.nid) FROM {mm_node2tree} n INNER JOIN {node} nd ON nd.nid = n.nid INNER JOIN {node_field_data} nfd ON nfd.nid = n.nid WHERE n.mmtid = o.container {$wheres}) AS nodecount ";
    $params[Constants::MM_GET_TREE_FILTER_NORMAL] = $params[Constants::MM_GET_TREE_FILTER_USERS] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRightButtons($mode, $query, $item, $permissions, &$actions, &$dialogs) {
  }

  /**
   * {@inheritdoc}
   */
  public function viewRight($mode, $query, $perms, $item, $database) {
    // This retrieves the attributes of the content type.
    // Allow all node types by default.
    $mmtid = $item->mmtid;
    $settings = array('mm_list_nodetypes' => array());
    if ($field_id = $query->get('browserFieldID', '')) {
      list($field_name, $bundle) = explode(',', $field_id);
      if ($widget = EntityFormDisplay::load('node.' . $bundle . '.default')->getRenderer($field_name)) {
        $settings = $widget->getSettings();
      }
    }

    if (empty($settings['mm_list_selectable'])) {
      $settings['mm_list_selectable'] = Constants::MM_PERMS_READ;
    }

    if (empty($perms[$settings['mm_list_selectable']])) {
      $out = '';
      if ($mmtid > 0) {
        $out = '<div id="mmtree-browse-thumbnails"><br /><p>' . t('You do not have permission to use the content on this page.') . '</p>';
        $options = array(
          Constants::MM_PERMS_WRITE => t('delete it or change its settings'),
          Constants::MM_PERMS_SUB   => t('append subpages to it'),
          Constants::MM_PERMS_APPLY => t('add content to it'),
          Constants::MM_PERMS_READ  => t('read it'));
        if (isset($options[$settings['mm_list_selectable']])) {
          $out .= t('<p>To use content from this page, you must be able to @do.</p>', array('@do' => $options[$settings['mm_list_selectable']]));
        }
        $out .= '</div>';
      }
      $json = array(
        'title' => mm_content_get_name($mmtid),
        'body' => $out,
      );
      return mm_json_response($json);
    }

    $all_types = [];
    /** @var NodeType $type */
    foreach (NodeType::loadMultiple() as $id => $type) {
      $all_types[$id] = $type->label();
    }

    $allowed_node_types = array();
    foreach ($settings['mm_list_nodetypes'] as $node_type) {
      if (!empty($node_type)) {
        $allowed_node_types[] = $node_type;
      }
    }

    $table_header = array(
      array('data' => t('Type'), 'field' => 'fd.type'),
      array('data' => t('Title'), 'field' => 'fd.title'),
      array('data' => t('Last Modified'), 'field' => 'fd.changed', 'sort' => 'desc'),
    );

    $select = $database->select('node', 'n');
    $select->addTag(__FUNCTION__);
    $select->join('mm_node2tree', 'm', 'm.nid = n.nid');
    $select->join('node_field_data', 'fd', 'fd.nid = n.nid');
    $select->fields('fd', array('nid', 'title', 'type', 'changed'));
    $select->condition('m.mmtid', $mmtid);
    $select->condition('n.type', $allowed_node_types ? $allowed_node_types : array_keys($all_types), 'IN');
    $select->condition('fd.status', 1);
    $result = $select->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($table_header)
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit(mm_get_setting('nodes.nodelist_pager_limit'))
      ->execute();

    $nids = array();
    foreach ($result as $r) {
      $nids[$r->nid] = $all_types[$r->type];
    }
    $nodes = Node::loadMultiple(array_keys($nids));

    $rows = array();
    foreach ($nids as $nid => $name) {
      $row = array();
      /** @var NodeInterface $node */
      $node = $nodes[$nid];
      $file = NULL;
      if ($node->getType() == 'mm_media' && isset($node->field_multimedia[$node->language][0]['fid'])) {
        $file = file_load($node->field_multimedia[$node->language][0]['fid']);
        if ($file) {
          $name .= ' - ' . $file->getMimeType();
          if (!empty($node->label())) {
            $file->set('title', Html::escape($node->label()));
          }
        }
      }
      $link = $this->getLink($mode, $node, $mmtid, $file);
      $row[] = Html::escape($name);
      $row[] = FilteredMarkup::create($link);
      $row[] = mm_format_date($node->getChangedTime(), 'custom', 'M j, Y g:i A');
      $rows[] = $row;
    }

    if (!$rows) {
      $content = ['#markup' => '<p>' . t('There is no selectable content on this page.') . '</p>'];
    }
    else {
      $content = [
        [
          '#type' => 'table',
          '#header' => $table_header,
          '#rows' => $rows,
        ],
        [
          '#type' => 'pager',
          '#route_name' => 'monster_menus.browser_getright',
          '#tags' => NULL,
          '#element' => 0,
        ],
      ];
    }
    return $content;
  }

  /**
   * Get the appropriate link for the current mode.
   *
   * @param string $mode
   *   Display mode constant
   * @param NodeInterface|File $item
   *   May be a node, may be a file, etc. depends on browser mode
   * @param $mmtid
   *   MMTID of the current page
   * @param File $file
   *   Optional File entity to generate a thumbnail
   * @return string|array
   *   The HTML code or render array of the link
   */
  private function getLink($mode, &$item, $mmtid, File $file = NULL) {
    $onclick = "Drupal.mm_browser_nodepicker_add({$mmtid}, '" . mm_ui_js_escape($item->label()) . "', {$item->id()});";
    if ($file) {
      // @FIXME  move to Media.php implementation
      // theme() has been renamed to _theme() and should NEVER be called directly.
      // Calling _theme() directly can alter the expected output and potentially
      // introduce security issues (see https://www.drupal.org/node/2195739). You
      // should use renderable arrays instead.
      //
      //
      // @see https://www.drupal.org/node/2195739
      // return theme('mm_browser_thumbnail', array('file' => $file, 'mode' => $mode, 'mmtid' => $mmtid, 'onclick' => $onclick));

    }
    return mm_empty_anchor($item->label(), ['onclick' => $onclick]);
  }

}
