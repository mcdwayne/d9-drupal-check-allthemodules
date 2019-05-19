<?php

namespace Drupal\views_negate\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\StringArgument;
use Drupal\Core\Form\FormStateInterface;

/**
 * Basic argument handler to implement string arguments that may have length
 * limits.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("StringNegate")
 */
class StringNegate extends StringArgument {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['negate'] = ['default' => 0];
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['negate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Negate'),
      '#description' => $this->t('If selected, the value entered for the filter will be excluded rather than limiting the view.'),
      '#default_value' => !empty($this->options['negate']),
      '#fieldset' => 'more',
    ];
  }

  /**
   * Build the query based upon the formula
   */
  public function query($group_by = FALSE) {
    if ($this->options['negate'] != 1) {
      return parent::query();
    }
    else {
      $argument = $this->argument;
      if (!empty($this->options['transform_dash'])) {
        $argument = strtr($argument, '-', ' ');
      }

      if (!empty($this->options['break_phrase'])) {
        $this->unpackArgumentValue();
      }
      else {
        $this->value = [$argument];
        $this->operator = 'or';
      }

      if (!empty($this->definition['many to one'])) {
        if (!empty($this->options['glossary'])) {
          $this->helper->formula = TRUE;
        }
        $this->helper->ensureMyTable();
        $this->helper->addFilter();
        return;
      }

      $this->ensureMyTable();
      $formula = FALSE;
      if (empty($this->options['glossary'])) {
        $field = "$this->tableAlias.$this->realField";
      }
      else {
        $formula = TRUE;
        $field = $this->getFormula();
      }

      if (count($this->value) > 1) {
        $operator = 'NOT IN';
        $argument = $this->value;
      }
      else {
        $operator = '!=';
      }

      if ($formula) {
        $placeholder = $this->placeholder();
        if ($operator == 'NOT IN') {
          $field .= " NOT IN($placeholder)";
        }
        else {
          $field .= ' != ' . $placeholder;
        }

        $field .= " OR $this->tableAlias.$this->realField IS NULL";
        $placeholders = [
          $placeholder => $argument,
        ];
        $this->query->addWhereExpression(0, $field, $placeholders);
      }
      else {
        $this->query->addWhere(
          NULL,
          db_or()
            ->condition($field, $argument, $operator)
            ->condition("$this->tableAlias.$this->realField", NULL, 'IS NULL')
        );
      }
    }
  }
}
