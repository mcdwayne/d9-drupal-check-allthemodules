<?php

namespace Drupal\peytz_mail\Form;

/**
 * {@inheritdoc}
 */
class PeytzMailSignUpBlockForm extends PeytzMailSignUpFormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'peytz_mail_sign_up_block_form';
  }

}
