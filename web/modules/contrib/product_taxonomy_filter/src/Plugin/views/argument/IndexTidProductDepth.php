<?php

namespace Drupal\product_taxonomy_filter\Plugin\views\argument;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Plugin\views\argument\IndexTidDepth;

/**
 * Argument handler for products with taxonomy terms with depth.
 *
 * Normally taxonomy terms with depth contextual filter can be used
 * only for content. This handler can be used for Drupal commerce products.
 *
 * Handler expects reference field name, gets reference table and column and
 * builds sub query on that table. That is why handler does not need special
 * relation table like taxonomy_index.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("taxonomy_index_tid_product_depth")
 */
class IndexTidProductDepth extends IndexTidDepth {

  /**
   * @var EntityStorageInterface
   */
  protected $termStorage;

  /**
   * Extend options.
   *
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['reference_field'] = ['default' => 'field_product_category'];
    return $options;
  }


  /**
   * @inheritdoc
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
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
      '#description' => $this->t('The depth will match product tagged with terms in the hierarchy. For example, if you have the term "fruit" and a child term "apple", with a depth of 1 (or higher) then filtering for the term "fruit" will get products that are tagged with "apple" as well as "fruit". If negative, the reverse is true; searching for "apple" will also pick up nodes tagged with "fruit" if depth is -1 (or lower).'),
    ];

    $form['break_phrase'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple values'),
      '#description' => $this->t('If selected, users can enter multiple values in the form of 1+2+3. Due to the number of JOINs it would require, AND will be treated as OR with this filter.'),
      '#default_value' => !empty($this->options['break_phrase']),
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @inheritdoc
   */
  public function query($group_by = FALSE) {
    // Get the DB table and reference column name from the reference field name.
    $refFieldName = $this->options['reference_field'] . '_target_id';
    $refTableName = 'commerce_product__' . $this->options['reference_field'];

    $this->ensureMyTable();

    if (!empty($this->options['break_phrase'])) {
      $break = static::breakString($this->argument);
      if ($break->value === [-1]) {
        return FALSE;
      }

      $operator = (count($break->value) > 1) ? 'IN' : '=';
      $tids = $break->value;
    }
  else {
      $operator = "=";
      $tids = $this->argument;
    }

    // Now build the subqueries.
    $subquery = db_select($refTableName, 'pt');
    $subquery->addField('pt', 'entity_id');
    $where = db_or()->condition('pt.' . $refFieldName, $tids, $operator);
    $last = "pt";

    if ($this->options['depth'] > 0) {
      $subquery->leftJoin('taxonomy_term__parent', 'tp', "tp.entity_id = pt." . $refFieldName);
      $last = "tp";
      foreach (range(1, abs($this->options['depth'])) as $count) {
        $subquery->leftJoin('taxonomy_term__parent', "tp$count", "$last.parent_target_id = tp$count.entity_id");
        $where->condition("tp$count.entity_id", $tids, $operator);
        $last = "tp$count";
      }
    }
    elseif ($this->options['depth'] < 0) {
      foreach (range(1, abs($this->options['depth'])) as $count) {
        $subquery->leftJoin('taxonomy_term__parent', "tp$count", "$last.entity_id = tp$count.parent_target_id");
        $where->condition("tp$count.entity_id", $tids, $operator);
        $last = "tp$count";
      }
    }

    $subquery->condition($where);
    $this->query->addWhere(0, "$this->tableAlias.$this->realField", $subquery, 'IN');
  }

  /**
   * @inheritdoc
   */
  public function title() {
    $term = $this->termStorage->load($this->argument);
    if (!empty($term)) {
      $title = $term->getName();
      return $title;
    }
    return $this->t('No name');
  }

}
