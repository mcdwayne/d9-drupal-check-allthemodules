<?php
namespace Drupal\monster_menus\GetTreeIterator;
use Drupal\monster_menus\GetTreeIterator;

class ContentTestShowpageIter extends GetTreeIterator {

  public $match;
  private $path, $router, $pindex;

  public function __construct($path) {
    $this->match = FALSE;
    $this->path = $path;
    $this->pindex = count($path) - 1;
    $this->router = array_keys(_mm_showpage_router());
  }

  /**
   * {@inheritdoc}
   */
  public function iterate($item) {
    $this->path = array_slice($this->path, 0, $this->pindex + $item->level);
    $this->path[] = $item->alias;
    $txt_path = implode('/', $this->path);

    foreach ($this->router as $key) {
      if (preg_match($key, $txt_path)) {
        $this->match = $item->mmtid;
        return 0;   // stop iterating
      }
    }

    return 1;   // continue
  }

}
