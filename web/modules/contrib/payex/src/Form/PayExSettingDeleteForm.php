<?php

/**
 * @file
 * Contains \Drupal\payex\Form\PayExSettingDeleteForm.
 */

namespace Drupal\payex\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a deletion confirmation form for the PayExSetting entity.
 */
class PayExSettingDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('payex.admin_page');
  }

}
