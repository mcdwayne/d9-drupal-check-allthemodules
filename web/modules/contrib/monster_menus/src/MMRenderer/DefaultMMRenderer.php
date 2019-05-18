<?php
namespace Drupal\monster_menus\MMRenderer;

/**
 * @file
 * The default renderer for the monster_menus.tree_renderer service.
 */

use Drupal\Core\Template\Attribute;
use Drupal\monster_menus\Constants;

class DefaultMMRenderer implements MMRendererInterface {

  protected $tree, $current;
  protected $state_to_css, $state_cache, $state_max;

  public function create(array $tree, $start = 0) {
    // To avoid having to sort, this array must be in increasing order
    $this->state_to_css = array(     // Do not translate these with t()
      Constants::MM_GET_TREE_STATE_COLLAPSED => 'menu-item--collapsed',
      Constants::MM_GET_TREE_STATE_DENIED =>    'menu-item--denied',
      Constants::MM_GET_TREE_STATE_EXPANDED =>  'menu-item--expanded',
      Constants::MM_GET_TREE_STATE_HERE =>      'menu-item--active-trail',
      Constants::MM_GET_TREE_STATE_HIDDEN =>    'menu-item--hidden-entry',
      Constants::MM_GET_TREE_STATE_LEAF =>      'menu-item--leaf',
      Constants::MM_GET_TREE_STATE_NOT_WORLD => 'menu-item--not-world',
      Constants::MM_GET_TREE_STATE_RECYCLE =>   'menu-item--recycle-bin',
    );
    $this->tree = $tree;
    $this->current = $start;
    $this->state_cache = array();
    $keys = array_keys($this->state_to_css);
    $this->state_max = array_pop($keys);
    return $this;
  }

  public function render() {
    // Speed up future calls to mm_content_get() by pre-fetching everything all
    // at once (well, up to 50 at once.)
    foreach (array_chunk($this->tree, 50) as $chunk) {
      $mmtids = array();
      foreach ($chunk as $item) {
        $mmtids[] = $item->mmtid;
      }
      mm_content_get($mmtids, Constants::MM_GET_PARENTS);
    }

    return [
      '#theme' => 'mm_tree_menu',
      '#items' => $this->walk(),
    ];
  }

  public function walk($is_top = TRUE) {
    if ($this->current >= count($this->tree)) {
      return [];
    }

    $items = [];
    $lev0 = $this->tree[$this->current]->level;
    while ($this->current < count($this->tree) && $this->tree[$this->current]->level == $lev0) {
      $leaf = $this->tree[$this->current];
      $name = mm_content_get_name($leaf);
      $plain_name = is_string($name) ? $name : $name->render();

      if (!$this->leafIsVisible($leaf) ||
          $plain_name[0] == '.' && !(isset($leaf->perms) ? $leaf->perms[Constants::MM_PERMS_READ] : mm_content_user_can($leaf->mmtid, Constants::MM_PERMS_READ))) {
        while (++$this->current < count($this->tree) && $this->tree[$this->current]->level > $lev0) ; // skip kids
        continue;   // get next sibling
      }

      $item = [
        'title' => $name,
        'attributes' => new Attribute(),
        'url' => mm_content_get_mmtid_url($leaf->mmtid),
      ];
      if (!empty($leaf->hover)) {
        $item['attributes']->setAttribute('title', $leaf->hover);
      }

      $item = $this->alterItem($leaf, $item);

      while (++$this->current < count($this->tree) && $this->tree[$this->current]->level > $lev0) {
        if ($this->tree[$this->current]->level == $lev0 + 1 && ($is_top || $leaf->state & Constants::MM_GET_TREE_STATE_EXPANDED)) {
          $kids = $this->walk(FALSE);
          if ($kids) {
            $item['below'] = $kids;
          }
          $this->current--;
        }
      }

      $items[] = $item;
    }
    return $items;
  }

  public function getLeafStateClass($leaf) {
    if (!isset($this->state_cache[$leaf->state])) {
      $state = $leaf->state;
      $state_css = array();
      $i = 0;
      $bit = 0;
      while ($state && $bit != $this->state_max) {
        $bit = 1 << $i++;
        if ($state & $bit && isset($this->state_to_css[$bit])) {
          $state_css[] = $this->state_to_css[$bit];
          $state ^= $bit;
        }
      }
      $this->state_cache[$leaf->state] = join(' ', $state_css);
    }
    return 'menu-item ' . $this->state_cache[$leaf->state];
  }

  public function alterItem(\stdClass $leaf, array $item) {
/** if (isset($leaf->nodecount) && $leaf->nodecount === '0') {
      $item['title'] .= t(' [no pages]');
    }**/

    if ($item['url'] != mm_get_current_path() &&
        ($leaf->state & (Constants::MM_GET_TREE_STATE_EXPANDED|Constants::MM_GET_TREE_STATE_HERE)) == (Constants::MM_GET_TREE_STATE_EXPANDED|Constants::MM_GET_TREE_STATE_HERE)) {
      $item['attributes']->addClass('active');
    }

    if ($leaf->state & Constants::MM_GET_TREE_STATE_HIDDEN) {
      $item['title'] .= ' ' . t('(hidden)');
    }

    if ($leaf->state & Constants::MM_GET_TREE_STATE_DENIED) {
      $item['attributes']->setAttribute('rel', 'nofollow');
    }

    $item['attributes']->addClass($this->getLeafStateClass($leaf));

    return $item;
  }

  public function leafIsVisible(\stdClass $leaf) {
    if ($leaf->name == Constants::MM_ENTRY_NAME_RECYCLE) {
      return mm_content_user_can_recycle($leaf->mmtid, Constants::MM_PERMS_READ);
    }

    if (isset($leaf->perms) && ($leaf->perms[Constants::MM_PERMS_WRITE] || $leaf->perms[Constants::MM_PERMS_SUB] || $leaf->perms[Constants::MM_PERMS_APPLY])) {
      return TRUE;
    }

    return !isset($leaf->nodecount) || $leaf->nodecount !== '0' || !mm_get_setting('pages.hide_empty_pages_in_menu');
  }
}
