<?php

namespace Drupal\wisski_core\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

class WisskiTitlePatternDeleteForm extends EntityConfirmFormBase {

  public function getQuestion() {
    $bundle = $this->entity;
    return $this->t('Do you really want to delete the title pattern for %label (bundle %bundle)?',array('%label'=>$bundle->label(),'%bundle'=>$bundle->id()));
  }
  
  public function getCancelUrl() {
    $bundle = $this->entity;
    return $bundle->urlInfo('title-form');
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bundle = $this->entity;
    $bundle->removeTitlePattern();
    $bundle->save();
    drupal_set_message(t('Removed title pattern for bundle %name.', array('%name' => $bundle->label())));
    $form_state->setRedirectUrl($bundle->urlInfo('edit-form'));
  }
}