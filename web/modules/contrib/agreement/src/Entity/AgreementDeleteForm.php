<?php

namespace Drupal\agreement\Entity;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * Agreement entity delete form.
 */
class AgreementDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $this->t('Are you sure you want to permanently delete this agreement? All agreement records will be removed.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.agreement.collection');
  }

}
