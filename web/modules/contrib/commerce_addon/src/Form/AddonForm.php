<?php

namespace Drupal\commerce_addon\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class AddonForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_addon\Entity\AddonInterface $addon */
    $addon = $this->getEntity();
    $addon->save();
    drupal_set_message($this->t('The addon %label has been successfully saved.', ['%label' => $addon->label()]));
    $form_state->setRedirect('entity.commerce_addon.collection');
  }

}
