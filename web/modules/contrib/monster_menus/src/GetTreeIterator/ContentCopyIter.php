<?php
namespace Drupal\monster_menus\GetTreeIterator;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\GetTreeIterator;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

class ContentCopyIter extends GetTreeIterator {

  /**
   * The database connection.
   *
   * @var Connection
   */
  protected $database;

  public $first_mmtid, $dest_mmtid, $error, $cont_userlist, $cont_grouplist, $nodemap, $options, $predefined_flags;

  /**
   * Constructs a ContentCopyIter object.
   *
   * @param int $src_mmtid
   *   MMTID of copy source
   * @param int $dest_mmtid
   *   MMTID of copy destination
   * @param array $options
   *   Options for copy operation
   */
  public function __construct($src_mmtid, $dest_mmtid, array $options) {
    $defaults = array(
      Constants::MM_COPY_ALIAS =>              NULL,
      Constants::MM_COPY_COMMENTS =>           FALSE,
      Constants::MM_COPY_CONTENTS =>           FALSE,
      Constants::MM_COPY_ITERATE_ALTER =>      NULL,
      Constants::MM_COPY_NAME =>               NULL,
      Constants::MM_COPY_NODE_PRESAVE_ALTER => NULL,
      Constants::MM_COPY_OWNER =>              NULL,
      Constants::MM_COPY_READABLE =>           FALSE,
      Constants::MM_COPY_RECUR =>              TRUE,
      Constants::MM_COPY_TREE =>               TRUE,
      Constants::MM_COPY_TREE_PRESAVE_ALTER => NULL,
      Constants::MM_COPY_TREE_SKIP_DUPS =>     FALSE,
    );
    $this->options = array_merge($defaults, $options);
    $this->dest_mmtid = $dest_mmtid;
    $this->nodemap = array(mm_content_get_parent($src_mmtid) => $dest_mmtid);

    $hooks = array(
      Constants::MM_COPY_ITERATE_ALTER =>      'mm_copy_tree_iterate_alter',
      Constants::MM_COPY_TREE_PRESAVE_ALTER => 'mm_copy_tree_tree_alter',
      Constants::MM_COPY_NODE_PRESAVE_ALTER => 'mm_copy_tree_node_alter',
    );
    foreach ($hooks as $constant => $hook) {
      $this->options[$constant] = isset($this->options[$constant]) ? (is_array($this->options[$constant]) ? $this->options[$constant] : array($this->options[$constant])) : array();
      foreach (mm_module_implements($hook) as $module) {
        $this->options[$constant][] = "{$module}_$hook";
      }
    }

    if ($this->options[Constants::MM_COPY_CONTENTS] && !$this->options[Constants::MM_COPY_TREE]) {
      mm_content_get_default_node_perms($dest_mmtid, $this->cont_grouplist, $this->cont_userlist, 0);
    }
    $this->predefined_flags = \Drupal::moduleHandler()->invokeAll('mm_tree_flags');

    $this->database = Database::getConnection();
  }

  /**
   * {@inheritdoc}
   */
  public function iterate($item) {
    if (isset($this->error)) {
      // was set in a previous invocation
      return 0;
    }

    if ($this->options[Constants::MM_COPY_READABLE] && !$item->perms[Constants::MM_PERMS_READ]) {
      // skip this node and kids
      return -1;
    }

    if ($item->name == Constants::MM_ENTRY_NAME_RECYCLE) {
      // recycle bin: skip this node and kids
      return -1;
    }

    $options_temp = $this->options;
    if (is_array($options_temp[Constants::MM_COPY_ITERATE_ALTER])) {
      foreach ($options_temp[Constants::MM_COPY_ITERATE_ALTER] as $alter) {
        switch (call_user_func_array($alter, array(&$item, &$options_temp))) {
          case 1:   // skip this one
            return 1;
          case -1:  // skip this one and kids
            return -1;
          case 0:   // completely stop
            return 0;
        }
      }
    }

    if ($options_temp[Constants::MM_COPY_CONTENTS] && !$options_temp[Constants::MM_COPY_TREE]) {
      if (!isset($this->first_mmtid)) {
        $this->nodemap[$item->mmtid] = $this->dest_mmtid;
      }
      else {
        $exists = mm_content_get(array('parent' => $this->nodemap[$item->parent], 'alias' => $item->alias));
        if ($exists) {
          $this->nodemap[$item->mmtid] = $exists[0]->mmtid;
        }
        else {
          $this->error = t('There is no destination page with the URL alias %alias to copy the content to.', array('%alias' => $item->alias));
          return 0;
        }
      }
    }
    else {
      $dest_mmtid = $this->nodemap[$item->parent];
      $alias = is_null($options_temp[Constants::MM_COPY_ALIAS]) ? $item->alias : $options_temp[Constants::MM_COPY_ALIAS];

      $exists = FALSE;
      if ($options_temp[Constants::MM_COPY_TREE_SKIP_DUPS] && !empty($alias)) {
        $tree = mm_content_get(array('parent' => $dest_mmtid, 'alias' => $alias));
        if ($tree) {
          $this->nodemap[$item->mmtid] = $tree[0]->mmtid;
          $exists = TRUE;
        }
      }

      if (!$exists) {
        $perms = array();
        $select = $this->database->select('mm_tree', 't');
        $select->join('mm_tree_access', 'a', 't.mmtid = a.gid');
        $select->fields('t', array('mmtid'))
          ->fields('a', array('mode'))
          ->condition('a.gid', 0, '>=')
          ->condition('a.mmtid', $item->mmtid);
        $result = $select->execute();
        foreach ($result as $r) {
          $perms[$r->mode]['groups'][] = $r->mmtid;
        }

        $select = $this->database->select('mm_tree_access', 'a');
        $select->join('mm_group', 'g', 'g.gid = a.gid');
        $select->fields('a', array('mode'))
          ->fields('g', array('uid'))
          ->condition('a.gid', 0, '<')
          ->condition('a.mmtid', $item->mmtid);
        $result = $select->execute();
        foreach ($result as $r) {
          $perms[$r->mode]['users'][] = $r->uid;
        }

        $result = $this->database->select('mm_tree_block', 'b')
          ->fields('b', array('bid', 'max_depth', 'max_parents'))
          ->condition('b.mmtid', $item->mmtid)
          ->execute();
        if ($item->is_group || !($block = $result->fetchAssoc())) {
          $block = array('bid' => Constants::MM_MENU_DEFAULT, 'max_depth' => -1, 'max_parents' => -1);
        }

        if (!isset($this->nodemap[$item->parent])) {
          $this->error = t('Unexpected tree structure');
          return 0;
        }

        $new = array(
          'name' => !empty($options_temp[Constants::MM_COPY_NAME]) ? $options_temp[Constants::MM_COPY_NAME]  : $item->name,
          'alias' => $alias,
          'default_mode' => $item->default_mode,
          'uid' => $options_temp[Constants::MM_COPY_OWNER],
          'cascaded' => mm_content_get_cascaded_settings($item->mmtid),
          'perms' => $perms,
          'menu_start' => $block['bid'],
          'max_depth' => $block['max_depth'],
          'max_parents' => $block['max_parents'],
        );
        foreach (array('theme', 'flags', 'rss', 'node_info', 'previews', 'hidden', 'comment') as $field) {
          $new[$field] = $item->$field;
        }
        if (isset($this->first_mmtid)) {
          $new['weight'] = $item->weight;
        }

        foreach ($this->predefined_flags as $flag => $elem) {
          if (isset($elem['#flag_copy']) && $elem['#flag_copy'] === FALSE) {
            unset($new['flags'][$flag]);
          }
        }

        if (is_array($options_temp[Constants::MM_COPY_TREE_PRESAVE_ALTER])) {
          foreach ($options_temp[Constants::MM_COPY_TREE_PRESAVE_ALTER] as $alter) {
            call_user_func_array($alter, array(&$new, $dest_mmtid));
          }
        }

        $this->nodemap[$item->mmtid] = mm_content_insert_or_update(TRUE, $dest_mmtid, $new);
      }
    }   // $options_temp[MM_COPY_CONTENTS] && !$options_temp[MM_COPY_TREE]

    if (!isset($this->first_mmtid)) {
      $this->first_mmtid = $this->nodemap[$item->mmtid];
      $this->options[Constants::MM_COPY_NAME] = $this->options[Constants::MM_COPY_ALIAS] = NULL;
    }

    if ($options_temp[Constants::MM_COPY_CONTENTS]) {
      /** @var NodeInterface $node */
      foreach (Node::loadMultiple(mm_content_get_nids_by_mmtid($item->mmtid)) as $nid => $node) {
        if (!empty($nid)) {
          $copy = $node->createDuplicate();
          $copy->mm_catlist = array($this->nodemap[$item->mmtid] => '');
          $copy->mm_catlist_restricted = array();
          $copy->setCreatedTime(mm_request_time());
          unset($copy->recycle_date);
          unset($copy->recycle_bins);
          unset($copy->recycle_from_mmtids);
          if ($options_temp[Constants::MM_COPY_CONTENTS] && !$options_temp[Constants::MM_COPY_TREE] && !$copy->others_w) {
            $copy->groups_w = $this->cont_grouplist;
            $copy->users_w = $this->cont_userlist;
          }

          if (is_array($options_temp[Constants::MM_COPY_NODE_PRESAVE_ALTER])) {
            foreach ($options_temp[Constants::MM_COPY_NODE_PRESAVE_ALTER] as $alter) {
              // Yes, $node->mm_catlist is intentional here, since we want the
              // list from the source node.
              call_user_func_array($alter, array(&$copy, $node->mm_catlist));
            }
          }

          $copy->save();

          if (($copy_nid = $copy->id()) && $options_temp[Constants::MM_COPY_COMMENTS] && mm_module_exists('comment')) {
            $comment_map = array();
            $comment_entity_manager = \Drupal::entityTypeManager()->getStorage('comment');
            // Yes, $nid is intentional here, since we want the ID of the source
            // node.
            $result = \Drupal::service('entity.query')
              ->get('comment')
              ->condition('entity_id', $nid)
              ->execute();
            /** @var Comment $comment */
            foreach ($comment_entity_manager->loadMultiple($result) as $old_cid => $comment) {
              $cloned = $comment->createDuplicate();
              $cloned->set('entity_id', $copy_nid)
                ->save();
              $comment_map[$old_cid] = $cloned->id();
            }

            foreach ($comment_map as $old_cid => $new_cid) {
              $result = \Drupal::service('entity.query')
                ->get('comment')
                ->condition('pid', $old_cid)
                ->condition('entity_id', $copy_nid)
                ->execute();
              foreach ($comment_entity_manager->loadMultiple($result) as $comment) {
                $comment->set('pid', $new_cid)
                  ->save();
                \Drupal::service('comment.statistics')->update($comment);
              }
            }
          }

          \Drupal::logger('mm')->notice('%type: During copy, copied node nid=@id1 (%name) to nid=@id2',
            array('%type' => $copy->getType(), '%name' => $copy->label(), '@id1' => $nid, '@id2' => $copy_nid));
        }
      }
    }

    if ($item->is_group) { // copy group entries
      $select = $this->database->select('mm_group', 'g');
      $select->addExpression(':gid', 'gid', array(':gid' => $this->nodemap[$item->mmtid]));
      $select->addField('g', 'uid');
      $select->condition('g.gid', $item->mmtid);
      $this->database->insert('mm_group')
        ->from($select)
        ->execute();
    }

    return 1;
  }

  public function output() {
    if (isset($this->error)) {
      return $this->error;
    }

    return $this->first_mmtid;
  }

}
