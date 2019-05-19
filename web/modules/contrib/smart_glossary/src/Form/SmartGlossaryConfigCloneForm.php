<?php

/**
 * @file
 * Contains \Drupal\smart_glossary\Form\SmartGlossaryConfigCloneForm.
 */

namespace Drupal\smart_glossary\Form;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\smart_glossary\Entity\SmartGlossaryConfig;
use Drupal\smart_glossary\SmartGlossary;

class SmartGlossaryConfigCloneForm extends EntityConfirmFormBase{
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to clone the SmartGlossary configuration "@title"?', array('@title' => $this->entity->get('title')));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '<b>ATTENTION:</b> '
      . $this->t('Make sure to adapt the base path of the cloned SmartGlossary after its creation.')
      . '<br />'
      . $this->t('This action cannot be undone.');
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
    return $this->t('Clone configuration');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var SmartGlossaryConfig $entity */
    $entity = $this->getEntity();

    $new_entity = SmartGlossary::createConfiguration(
      $entity->getTitle() . ' (CLONE)',
      '<none>',
      $entity->getConnectionID(),
      $entity->getLanguageMapping(),
      $entity->getVisualMapperSettings(),
      $entity->getAdvancedSettings()
    );

    drupal_set_message(t('Smart Glossary configuration "%title" was successfully cloned.', array('%title' => $entity->getTitle())));
    $form_state->setRedirect('entity.smart_glossary.edit_form', array('smart_glossary' => $new_entity->id()));
  }
}