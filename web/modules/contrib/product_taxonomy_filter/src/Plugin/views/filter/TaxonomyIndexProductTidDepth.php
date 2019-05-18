<?php

namespace Drupal\product_taxonomy_filter\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;


/**
 * Filter handler for taxonomy terms with depth.
 *
 * This handler is actually part of the node table and has some restrictions,
 * because it uses a subquery to find nodes with.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("taxonomy_index_tid_product_depth")
 */
class TaxonomyIndexProductTidDepth extends TaxonomyIndexTid {

  public function operatorOptions($which = 'title') {
    return [
      'or' => $this->t('Is one of'),
    ];
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['depth'] = ['default' => 0];
    $options['reference_field'] = ['default' => 'field_product_category'];

    return $options;
  }

  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildExtraOptionsForm($form, $form_state);

    $form['reference_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reference field'),
      '#default_value' => $this->options['reference_field'],
      '#description' => $this->t('The field name (machine name) in the product type, which is referencing to a taxonomy. For example field_product_category.'),
    ];

    $form['depth'] = [
      '#type' => 'weight',
      '#title' => $this->t('Depth'),
      '#default_value' => $this->options['depth'],
      '#description' => $this->t('The depth will match nodes tagged with terms in the hierarchy. For example, if you have the term "fruit" and a child term "apple", with a depth of 1 (or higher) then filtering for the term "fruit" will get nodes that are tagged with "apple" as well as "fruit". If negative, the reverse is true; searching for "apple" will also pick up nodes tagged with "fruit" if depth is -1 (or lower).'),
    ];
  }

  public function query() {

    // Get the DB table and reference column name from the reference field name.
    $refFieldName = $this->options['reference_field'] . '_target_id';
    $refTableName = 'commerce_product__' . $this->options['reference_field'];

    // If no filter values are present, then do nothing.
    if (count($this->value) == 0) {
      return;
    }
    elseif (count($this->value) == 1) {
      // Sometimes $this->value is an array with a single element so convert it.
      if (is_array($this->value)) {
        $this->value = current($this->value);
      }
      $operator = '=';
    }
    else {
      $operator = 'IN';
    }

    // The normal use of ensureMyTable() here breaks Views.
    // So instead we trick the filter into using the alias of the base table.
    //   See https://www.drupal.org/node/271833.
    // If a relationship is set, we must use the alias it provides.
    if (!empty($this->relationship)) {
      $this->tableAlias = $this->relationship;
    }
    // If no relationship, then use the alias of the base table.
    else {
      $this->tableAlias = $this->query->ensureTable($this->view->storage->get('base_table'));
    }

    // Now build the subqueries.
    $subquery = db_select($refTableName, 'tn');
    $subquery->addField('tn', 'entity_id');
    $where = (new Condition('OR'))->condition('tn.'. $refFieldName, $this->value, $operator);
    $last = "tn";

    if ($this->options['depth'] > 0) {
      $subquery->leftJoin('taxonomy_term__parent', 'tp', "tp.entity_id = tn." . $refFieldName);
      $last = "tp";
      foreach (range(1, abs($this->options['depth'])) as $count) {
        $subquery->leftJoin('taxonomy_term__parent', "tp$count", "$last.parent_target_id = tp$count.entity_id");
        $where->condition("tp$count.entity_id", $this->value, $operator);
        $last = "tp$count";
      }
    }
    elseif ($this->options['depth'] < 0) {
      foreach (range(1, abs($this->options['depth'])) as $count) {
        $subquery->leftJoin('taxonomy_term__parent', "tp$count", "$last.entity_id = tp$count.parent_target_id");
        $where->condition("tp$count.entity_id", $this->value, $operator);
        $last = "tp$count";
      }
    }

    $subquery->condition($where);
    $this->query->addWhere($this->options['group'], "$this->tableAlias.$this->realField", $subquery, 'IN');
  }

}
