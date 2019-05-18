<?php

namespace Drupal\merci_line_item\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\merci_line_item\Form\MerciLineItemForm;
use Drupal\Core\Render\Element;

/**
 * Form controller for Merci Line Item edit forms.
 *
 * @ingroup merci_line_item
 */
class MerciLineItemAddForm extends MerciLineItemForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\merci_line_item\Entity\MerciLineItem */
    $form = parent::buildForm($form, $form_state);
    /*
    foreach (Element::children($form) as $key) {
      if (!in_array($key, ['actions', 'merci_reservation_date', 'merci_reservation_items'])) {
        unset($form[$key]);
      }
  }
     */
    return $form;
  }

}
