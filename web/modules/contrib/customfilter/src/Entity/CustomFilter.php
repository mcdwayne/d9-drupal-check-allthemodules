<?php

namespace Drupal\customfilter\Entity;

// Base class for all configuration entities.
use Drupal\Core\Config\Entity\ConfigEntityBase;
// Interface for this entity.
use Drupal\customfilter\CustomFilterInterface;

use Drupal\Core\Entity\EntityStorageInterface;

use Drupal\Core\Cache\Cache;

/**
 * Defines the entify for a filter in customfilter.
 *
 * @ConfigEntityType(
 *   id = "customfilter",
 *   config_prefix = "filters",
 *   label = @Translation("Custom Filter"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "list_builder" = "Drupal\customfilter\CustomFilterListBuilder",
 *     "form" = {
 *       "add" = "Drupal\customfilter\Form\CustomFilterForm",
 *       "delete" = "Drupal\customfilter\Form\CustomFilterDeleteForm",
 *       "edit" = "Drupal\customfilter\Form\CustomFilterForm"
 *     }
 *   },
 *   admin_permission = "administer customfilter",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "cache" = "cache",
 *     "description" = "description",
 *     "shorttip" = "shorttip",
 *     "longtip" = "longtip",
 *     "rules" = "rules"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/content/customfilter/{customfilter}",
 *     "edit-form" = "/admin/config/content/customfilter/{customfilter}/edit",
 *     "delete-form" = "/admin/config/content/customfilter/{customfilter}/delete",
 *   }
 * )
 */
class CustomFilter extends ConfigEntityBase implements CustomFilterInterface {

  /**
   * The id of the filter.
   *
   * @var string
   */
  public $id;

  /**
   * The UUID of the filter.
   *
   * @var string
   */
  public $uuid;

  /**
   * The label of the filter.
   *
   * @var string
   */
  public $name;

  /**
   * When use cache.
   *
   * @var bool
   */
  public $cache;

  /**
   * The description of the filter.
   *
   * @var string
   */
  public $description;

  /**
   * The shortip of the filter.
   *
   * @var string
   */
  public $shorttip;

  /**
   * The longtip of the filter.
   *
   * @var string
   */
  public $longtip;

  /**
   * The rules for the filter.
   *
   * This is an associative array with all rules.
   *
   * @var array
   */
  public $rules = array();

  /**
   * Add a new rule.
   *
   * @param array $rule
   *   An array with a rule.
   *
   * @todo trown an exception when the rule exist.
   */
  public function addRule(array $rule) {
    $this->rules[$rule['rid']] = $rule;
  }

  /**
   * Delete a rule.
   *
   * @param string $rid
   *   The id of the rule.
   */
  public function deleteRule($rid) {
    $ids[] = $rid;
    $i = 0;
    while ($i < count($ids)) {
      $rules = $this->getRules($ids[$i]);
      foreach ($rules as $rule) {
        $ids[] = $rule['rid'];
      }
      $i++;
    }
    foreach ($ids as $id) {
      unset($this->rules[$id]);
    }
  }

  /**
   * If this filter uses cache or not.
   *
   * @return Bool
   *   If this filter uses cache or not.
   */
  public function getCache() {
    return $this->cache;
  }

  /**
   * Get the description of this filter.
   *
   * @return string
   *   Return the description of this filter.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Get all filters.
   *
   * @return array
   *   Get an array with all available filters from customfilter. The array
   *   elements are objects from this class.
   */
  public static function getFilters() {
    $filters = entity_load_multiple('customfilter');
    if (!is_array($filters)) {
      $filters = array();
    }
    return $filters;
  }

  /**
   * Get the longtip of this filter.
   *
   * @return string
   *   Return the longtip of this filter
   */
  public function getLongtip() {
    return $this->longtip;
  }

  /**
   * Get one rule.
   *
   * @param string $rid
   *   The id of the requested rule.
   *
   * @return array
   *   The rule requested.
   *
   * @todo trown an exception if the rule do not exist.
   */
  public function getRule($rid) {
    if (isset($this->rules[$rid])) {
      return $this->rules[$rid];
    }
    else {
      return array();
    }
  }

  /**
   * Get all rules for same parent rule.
   *
   * @param string $prid
   *   The parent id of the rules which you want all the childrens.
   *
   * @return array
   *   An array with all child rules from specified prid.
   */
  public function getRules($prid = '', $sort = FALSE) {
    // If rules is not an array(is empty) return a new empty array.
    if (!is_array($this->rules)) {
      return array();
    }

    $answer = array();
    foreach ($this->rules as $rule) {
      if ($rule['prid'] == $prid) {
        $answer[$rule['rid']] = $rule;
      }
    }
    if ($sort) {
      $this->sortRules($answer);
    }

    return $answer;

  }

  /**
   * Get a tree of rules.
   *
   * @param string $parent
   *   The parent id of the rules which you want the tree.
   *
   * @return array
   *   An array with all subrules(recursive) from parent rule.
   */
  public function getRulesTree($parent = '') {
    $rules = $this->getRules($parent);
    foreach ($rules as $rule) {
      $rules[$rule['rid']]['sub'] = $this->getRulesTree($rule['rid']);
    }
    return $rules;
  }

  /**
   * Get the shortip of this filter.
   *
   * @return string
   *   Return the shorttip of this filter.
   */
  public function getShorttip() {
    return $this->shorttip;
  }

  /**
   * Sort an array by a column.
   *
   * @param array $arr
   *   The array to be sorted.
   */
  private function sortRules(array &$arr) {
    $sort_col = array();
    foreach ($arr as $key => $row) {
      $sort_col[$key] = $row['weight'];
    }

    array_multisort($sort_col, SORT_ASC, $arr);
  }

  /**
   * Update a existing rule.
   *
   * @param array $rule
   *   An array with a rule.
   *
   * @todo trown an exception when the rule do not exist.
   */
  public function updateRule(array $rule) {
    // If the rule is not complete, use the previous values of the rule.
    $previous = $this->rules[$rule['rid']];
    $property = array('prid', 'fid', 'name', 'description', 'enabled',
      'matches', 'pattern', 'replacement', 'code', 'weight');
    foreach ($property as $p) {
      if (!isset($rule[$p])) {
        $rule[$p] = $previous[$p];
      }
    }
    $this->rules[$rule['rid']] = $rule;
  }
}
