<?php

namespace Drupal\pagerer;

/**
 * Provides a factory for Pagerer pagers.
 */
class PagererFactory implements PagererFactoryInterface {

  /**
   * The array of pager objects.
   *
   * @var \Drupal\pagerer\Pagerer[]
   */
  protected $pagers = [];

  /**
   * {@inheritdoc}
   */
  public function initPagers() {
    global $pager_total;
    if (!empty($pager_total)) {
      for ($i = 0; $i <= max(array_keys($pager_total)); $i++) {
        $this->get($i);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($element) {
    if (!isset($this->pagers[$element])) {
      // Fill any gap in the sequence.
      for ($i = max(count($this->pagers) - 1, 0); $i < $element; $i++) {
        $this->get($i);
      }
      $this->pagers[$element] = Pagerer::create(\Drupal::getContainer(), $element);
    }
    return $this->pagers[$element];
  }

  /**
   * {@inheritdoc}
   */
  public function all() {
    return $this->pagers;
  }

}
