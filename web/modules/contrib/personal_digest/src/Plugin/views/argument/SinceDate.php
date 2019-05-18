<?php

namespace Drupal\personal_digest\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Date;

/**
 * Argument handler for a full date (CCYYMMDD)
 *
 * @ViewsArgument("date_fulldate_since")
 */
class SinceDate extends Date {

  /**
   * Provide a link to the next level of the view
   */
  public function summaryName($data) {
    $created = $data->{$this->name_alias};
    return t('Since last digest');
  }

  /**
   * Provide a link to the next level of the view
   */
  function title() {
    return t('Since last digest');
  }


  /**
   * {@inheritdoc}
   *
   * Differs from parent::query only with the > sign
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    // Now that our table is secure, get our formula.
    $placeholder = $this->placeholder();
    $formula = $this->getFormula() . ' > ' . $placeholder;
    $placeholders = array(
      $placeholder => $this->argument,
    );
    $this->query->addWhere(0, $formula, $placeholders, 'formula');
  }
}
