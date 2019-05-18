<?php

namespace Drupal\merci_line_item\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\merci_line_item\Form\MerciLineItemForm;

/**
 * Form controller for Merci Line Item edit forms.
 *
 * @ingroup merci_line_item
 */
class MerciLineItemEditForm extends MerciLineItemForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\merci_line_item\Entity\MerciLineItem */
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

}
