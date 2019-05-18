<?php

namespace Drupal\eform\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Defines a eform submission operations bulk form element.
 *
 * @ViewsField("eform_submission_bulk_form")
 */
class EFormSubmissionBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No users selected.');
  }

}
