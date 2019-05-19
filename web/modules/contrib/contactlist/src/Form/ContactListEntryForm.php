<?php

namespace Drupal\contactlist\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class ContactListEntryForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    drupal_set_message($this->t('Contact entry <b>@name</b> has been saved.', ['@name' => $this->entity->getContactName()]));
    $form_state->setRedirect('entity.contactlist_entry.collection');
  }

}
