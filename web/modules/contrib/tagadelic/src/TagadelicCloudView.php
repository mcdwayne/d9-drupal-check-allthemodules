<?php

namespace Drupal\tagadelic;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Link;
use Drupal\tagadelic\TagadelicCloudBase;
use Drupal\tagadelic\TagadelicTag;

/**
 * Class TagadelicCloudView.
 *
 * @package Drupal\tagadelic
 */
class TagadelicCloudView extends TagadelicCloudBase {

  /**
   * A Drupal\views\ViewExecutable object.
   *
   */
  protected $view;

  /**
   * The field on each ResultRow object that will be used to create a tag.
   *
   */
  protected $count_field;

  /**
   * The field on each ResultRow object that will be used to create a tag.
   *
   */
  protected $override_sort = 0;

  /**
   * @param $view_results. An array of Drupal\views\ResultRow objects.
   *
   * @return $this; for chaining.
   */
  public function setView(\Drupal\views\ViewExecutable $view) {
    $this->view = $view;
    return $this;
  }

  /**
   * @param $override_sort. Flag to override the sort order set by the view.
   *
   * @return $this; for chaining.
   */
  public function setOverrideSort($override_sort) {
    $this->override_sort = $override_sort;
    return $this;
  }

  /**
   * @param $count_field. The field on each ResultRow object that will be used to create a tag.
   *
   * @return $this; for chaining.
   */
  public function setCountField($count_field) {
    $this->count_field = $count_field;
    return $this;
  }
  
  /**
   * {@inheritdoc}
   */
  public function createTags(Array $options = array()) {
    if (!empty($options['view'])) {
      $this->setView($options['view']);
    }
    else {
      return;
    }

    if (!empty($options['count_field'])) {
      $this->setCountField($options['count_field']);
    }
    else {
      return;
    }

    if (!empty($options['override_sort'])) {
      $this->setOverrideSort($options['override_sort']);
    }

    // The field for the count_field may be aliased
    // We may have a Drupal field such as comment count on node as the count field
    // First check to see if there is an alias in the query's fields
    foreach ($this->view->build_info['query']->getFields() as $id => $field) {
      if ($field['field'] == $this->count_field) {
        $count_field_alias = $id;
        break;
      }
    }
    
    // If there is no alias in the fields there may be a field that uses a count in the aggregation settings
    // We therefore need to check the expressions
    if (!isset($count_field_alias)) {
      foreach ($this->view->build_info['query']->getExpressions() as $id => $expression) {
        if (strpos($expression['expression'], 'COUNT') !== FALSE && strpos($expression['expression'], $this->count_field) !== FALSE) {
          $count_field_alias = $id;
        }
      }
    }

    foreach ($this->view->result as $id => $row) {
      // As the tags are not going to be used in the markup we can pass in dummy names and ids
      $name = md5(time() . $id);
      $count_field = $this->count_field;

      // If we there is no alias for the field assume it is present on the Resultrow object
      $prop = isset($count_field_alias) ? $count_field_alias : $count_field;
      if (isset($row->{$prop})) {
        $tag = new TagadelicTag($id + 1, $name, $row->{$prop});
        $this->addTag($tag);
      }
    }
  }

  protected function cmp($a, $b) {
    return strcmp($b->getCount(), $a->getCount());
  }
}
