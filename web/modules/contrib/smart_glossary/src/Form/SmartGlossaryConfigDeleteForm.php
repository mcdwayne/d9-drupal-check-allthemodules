<?php

/**
 * @file
 * Contains \Drupal\smart_glossary\Form\SmartGlossaryConfigDeleteForm.
 */

namespace Drupal\smart_glossary\Form;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\smart_glossary\Entity\SmartGlossaryConfig;

class SmartGlossaryConfigDeleteForm extends EntityConfirmFormBase{
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete entity %name?', array('%name' => $this->entity->get('title')));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelURL() {
    return new Url('entity.smart_glossary.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var SmartGlossaryConfig $entity */
    $entity = $this->getEntity();
    $entity->delete();

    drupal_set_message(t('SmartGlossary configuration "%title" has been deleted.', array('%title' => $entity->getTitle())));
    $form_state->setRedirect('entity.smart_glossary.collection');
  }
}