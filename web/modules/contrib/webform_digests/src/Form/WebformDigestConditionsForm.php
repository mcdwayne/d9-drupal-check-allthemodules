<?php

namespace Drupal\webform_digests\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform_digests\Entity\WebformDigestInterface;

/**
 * Class WebformDigestConditionsForm.
 */
class WebformDigestConditionsForm extends WebformDigestConditionsFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformDigestInterface $webform_digest = NULL) {
    $form = parent::buildForm($form, $form_state, $webform_digest);
    return $form;
  }

}
