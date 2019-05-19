<?php

namespace Drupal\tagadelic;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Url;
use Drupal\tagadelic\TagadelicTag;
use Drupal\tagadelic\TagadelicCloudInterface;

/**
 * Defines a base TagadelicCloud implementation.
 *
 * All subclasses are responsible for their own implementation of the createTags function
 *
 */
abstract class TagadelicCloudBase implements TagadelicCloudInterface {

  /**
   * An array of TagadelicTag objects.
   *
   */
  protected $tags;
  
  /**
   * #Amount of steps to weight the cloud in. Defaults to 6. Means: 6 different sized tags.
   *
   */
  private $steps = 6;  

  /**
   * Constructor
   */
  function __construct() {
    $this->tags = array();
  }

  /**
   * @param $options. An array of properties that may be needed to create the tags array.
   *
   * Poulate the member array of TagadelicTag objects
   *
   * @return NULL.
   */
  abstract public function createTags(Array $options = array());

  /**
   * {@inheritdoc}
   */
  public function getTags(Array $options = array()) {
    $this->resetTags();
    $this->createTags($options);
    $this->recalculate();
    return $this->tags;
  }

  /**
   * {@inheritdoc}
   */
  public function addTag(TagadelicTag $tag) {
    $this->tags[] = $tag;
    return $this;
  }

  /**
   * @param $by_property. The property on which to sort.
   *
   * Sorts the tags by given property.
   * @return $this; for chaining.
   */
  protected function sort($by_property) {
    if ($by_property == "random") {
      $this->drupal()->shuffle($this->tags);
    }
    else {
      //Bug in PHP https://bugs.php.net/bug.php?id=50688, lets supress the error.
      @usort($this->tags, array($this, "cb_sort_by_{$by_property}"));
    }
    return $this;
  }

  /**
   * (Re)calculates the weights on the tags.
   * @param $recalculate. Optional flag to enfore recalculation of the weights for the tags in this cloud.
   *        defaults to FALSE, meaning the value will be calculated once per cloud.
   *  @return $this; for chaining
   */
  protected function recalculate() {
    $tags = array();
    // Find minimum and maximum log-count.
    $min = 1e9;
    $max = -1e9;
    foreach ($this->tags as $id => $tag) {
      $min = min($min, $tag->distributed());
      $max = max($max, $tag->distributed());
      $tags[$id] = $tag;
    }
    // Note: we need to ensure the range is slightly too large to make sure even
    // the largest element is rounded down.
    $range = max(.01, $max - $min) * 1.0001;
    foreach ($tags as $id => $tag) {
      $this->tags[$id]->setWeight(1 + floor($this->steps * ($tag->distributed() - $min) / $range));
    }
    return $this;
  }

  private function cb_sort_by_name($a, $b) {
    return strcoll($a->getName(), $b->getName());
  }

  private function cb_sort_by_count($a, $b) {
    $ac = $a->getCount();
    $bc = $b->getCount();
    if ($ac == $bc) {
      return 0;
    }
    //Highest first, High to low
    return ($ac < $bc) ? +1 : -1;
  }

  private function resetTags() {
    $this->tags = array();
  }

}
