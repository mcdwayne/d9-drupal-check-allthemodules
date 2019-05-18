<?php

namespace Drupal\fillpdf\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FillPdfFormConfirmFormBase.
 *
 * @package Drupal\fillpdf\Form
 */
abstract class FillPdfFormConfirmFormBase extends ContentEntityConfirmFormBase {

  /**
   * Gets the FillPdfForm.
   *
   * The FillPdfForm entity used for populating form element defaults.
   *
   * @return \Drupal\fillpdf\FillPdfFormInterface
   *   The current FillPdfForm entity.
   */
  public function getEntity() {
    // This wrapper is here to add a proper typehint to EntityForm::getEntity().
    return parent::getEntity();
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // @todo: This is a workaround by webform for Core issue #2582295
    // "Confirmation cancel links are incorrect if installed in a subdirectory".
    // Remove after a fix landed there.
    // See: https://www.drupal.org/project/drupal/issues/2582295
    // See: https://www.drupal.org/project/webform/issues/2899166
    $request = $this->getRequest();
    $destination = $request->query->get('destination');
    if ($destination) {
      // Remove subdirectory from destination.
      $update_destination = preg_replace('/^' . preg_quote(base_path(), '/') . '/', '/', $destination);
      $request->query->set('destination', $update_destination);
      $actions = parent::actions($form, $form_state);
      $request->query->set('destination', $destination);
      return $actions;
    }
    else {
      return parent::actions($form, $form_state);
    }
  }

}
