<?php
/**
 * @file
 * Contains \Drupal\powertagging\Form\PowerTaggingConfigDeleteForm.
 */
namespace Drupal\powertagging\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\powertagging\PowerTaggingConfigListBuilder;

/**
 * Builds the form to delete PowerTagging entities.
 */
class PowerTaggingConfigDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete %name?', ['%name' => $this->entity->getTitle()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $description = '';
    if ($fields = $this->entity->getFields()) {
      $description = '<div>' . t('This PowerTagging configuration is still used in following fields') . ':';
      $description .= $this->entity->renderFields($fields);
      $description .= '<div class="messages messages--warning">' . t('By deleting this PowerTagging configuration all these fields will automatically be deleted too.') . '</div>';
      $description .= '</div>';
    }
    return $description . parent::getDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.powertagging.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    drupal_set_message(t('PowerTagging configuration "%title" has been deleted.', array('%title' => $this->entity->getTitle())));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
