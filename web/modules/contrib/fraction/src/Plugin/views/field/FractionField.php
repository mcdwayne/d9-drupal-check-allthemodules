<?php

namespace Drupal\fraction\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityField;
use Drupal\Core\Form\FormStateInterface;

/**
 * Field handler for Fraction fields.
 *
 * Overrides the clickSort() method to use a formula that divides
 * the numerator by the denominator.
 *
 * Overrides the buildOptionsForm() method to remove the 'click_sort_column'
 * form element.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("fraction_field")
 */
class FractionField extends EntityField {

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {

    // Ensure the main table for this field is included.
    $this->ensureMyTable();

    // Formula for calculating the final value, by dividing numerator by denominator.
    // These are available as additional fields.
    $formula = $this->tableAlias . '.' . $this->field . '_numerator / ' . $this->tableAlias . '.' . $this->field . '_denominator';

    // Add the orderby.
    $this->query->addOrderBy(NULL, $formula, $order, $this->tableAlias . '_decimal');
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    // Inherit the parent options form.
    parent::buildOptionsForm($form, $form_state);

    // Remove the 'click_sort_column' form element.
    unset($form['click_sort_column']);
  }
}
