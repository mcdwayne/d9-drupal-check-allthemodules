<?php

namespace Drupal\lightbox_campaigns\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class LightboxCampaignDeleteForm.
 *
 * @ingroup lightbox_campaigns
 */
class LightboxCampaignDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * Confirmation question.
   *
   * @return string
   *   Translated string.
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %label?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * Confirmation text.
   *
   * @return string
   *   Translated string.
   */
  public function getConfirmText() {
    return $this->t('Delete Campaign');
  }

  /**
   * Redirect to campaigns list.
   *
   * @return \Drupal\Core\Url
   *   The URL to go to if the user cancels the deletion.
   */
  public function getCancelUrl() {
    return new Url('entity.lightbox_campaign.collection');
  }

  /**
   * The submit handler for the confirm form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->delete();

    \Drupal::service('messenger')->addMessage(
      $this->t(
        '%label was deleted.',
        ['%label' => $this->entity->label()]
      )
    );
  }

}
