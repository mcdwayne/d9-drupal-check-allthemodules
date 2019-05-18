<?php

namespace Drupal\nodeownership\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * Provides a form for declining nodeowernship claim.
 *
 * @ingroup nodeowership
 */
class NodeownershipClaimDeclineForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to decline this claim');
  }

  /**
   * {@inheritdoc}
   *
   * If the approve command is canceled, return to the claims list.
   */
  public function getCancelUrl() {
    return new Url('entity.nodeownership_claim.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Decline');
  }

  /**
   * {@inheritdoc}
   *
   * Approve the entity and log the event.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    // Update Claimed Entity Status to approved.
    $entity->setStatus(NODEOWNERSHIP_CLAIM_DECLINED);
    $entity->save();

    \Drupal::logger('nodeowernship_claim')->notice('Declined claim @claim_id by user @uid for node @nid',
    array(
      '@claim_id' => $this->entity->id(),
      '@uid' => $this->entity->getOwnerId(),
      '@nid' => $this->entity->getNodeId(),
    ));
    $form_state->setRedirect('entity.nodeownership_claim.collection');
  }

}
