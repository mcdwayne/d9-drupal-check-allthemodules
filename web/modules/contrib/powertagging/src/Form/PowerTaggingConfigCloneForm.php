<?php

/**
 * @file
 * Contains \Drupal\powertagging\Form\PowerTaggingConfigCloneForm.
 */

namespace Drupal\powertagging\Form;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\powertagging\Entity\PowerTaggingConfig;

class PowerTaggingConfigCloneForm extends EntityConfirmFormBase{
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to clone the PowerTagging configuration "@title"?', array('@title' => $this->entity->get('title')));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '<b>ATTENTION:</b> '
      . $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelURL() {
    return new Url('entity.powertagging.collection');
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
    /** @var PowerTaggingConfig $entity */
    $entity = $this->getEntity();

    $new_entity = PowerTaggingConfig::createConfig(
      $entity->getTitle() . ' (CLONE)',
      $entity->getProjectId(),
      $entity->getConnectionId(),
      $entity->getConfig()
    );

    drupal_set_message(t('PowerTagging configuration "%title" was successfully cloned.', array('%title' => $entity->getTitle())));
    $form_state->setRedirect('entity.powertagging.edit_config_form', array('powertagging' => $new_entity->id()));
  }
}