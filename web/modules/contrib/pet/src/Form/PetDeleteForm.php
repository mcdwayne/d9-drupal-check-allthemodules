<?php

namespace Drupal\pet\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

class PetDeleteForm extends ContentEntityDeleteForm{

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state){
    $this->entity->delete();
    $this->logger('content')->notice('@type: deleted %title.', array('@type' => $this->entity->bundle(), '%title' => $this->entity->label()));
    $pet_storage = $this->entityManager->getStorage('pet');
    $pet = $pet_storage->load($this->entity->bundle());
    drupal_set_message(t('@type %title has been deleted.', array('@type' => $pet, '%title' => $this->entity->label())));
    $form_state->setRedirect('pet.list');
  }
}
