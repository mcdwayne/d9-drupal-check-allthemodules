<?php

namespace Drupal\form_alter_service\Form;

/**
 * Allows attaching a form alter service to form builder.
 *
 * @see \Drupal\form_alter_service\FormAlterCompilerPass::process()
 *
 * @ingroup form_api
 */
interface FormBuilderAlterInterface {

  /**
   * Sets a form alter service.
   *
   * @param \Drupal\form_alter_service\Form\FormAlter $form_alter
   *   A form alter service.
   */
  public function setFormAlter(FormAlter $form_alter);

}
