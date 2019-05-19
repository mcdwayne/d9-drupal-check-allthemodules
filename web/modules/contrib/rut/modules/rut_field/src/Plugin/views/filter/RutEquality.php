<?php

namespace Drupal\rut_field\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\Equality;
use Drupal\rut\Rut;

/**
 * Filter by rut.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("rut")
 */
class RutEquality extends Equality {

  /**
   * Overrides \Drupal\views\Plugin\views\filter\Equality::query().
   */
  public function query() {
    $this->ensureMyTable();

    $field = "$this->tableAlias.$this->realField";
    // The real field was the rut and dv.
    $field_rut = $field . '_rut';
    $field_dv = $field . '_dv';

    list($rut, $dv) = Rut::separateRut($this->value);

    if ($this->operator == '!=') {
      $or = db_or()
        ->condition($field_rut, $rut, '<>')
        ->condition($field_dv, $dv, '<>');
      $this->query->addWhere($this->options['group'], $or);
    }
    else {
      $this->query->addWhere($this->options['group'], $field_rut, $rut, $this->operator);
      $this->query->addWhere($this->options['group'], $field_dv, $dv, $this->operator);
    }
  }


  /**
   * Provide the rut element.
   */
  protected function valueForm(&$form, &$form_state) {
    parent::valueForm($form, $form_state);
    $form['value']['#type'] = 'rut_field';
    $form['value']['#validate_js'] = TRUE;
    // Define not validate when submitting the form.
    $form['value']['#validate_submit'] = FALSE;
    $form['value']['#message_js'] = t('The Rut/Run is invalid in %label', ['%label' => $this->definition['title']]);
  }
}
