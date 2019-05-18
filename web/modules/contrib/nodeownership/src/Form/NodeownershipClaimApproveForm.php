<?php

namespace Drupal\nodeownership\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\nodeownership\NodeownershipClaimUsage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for approving nodeowernship claim.
 *
 * @ingroup nodeowership
 */
class NodeownershipClaimApproveForm extends ContentEntityConfirmFormBase {

  protected $nodeownershipClaim;

  /**
   * {@inheritdoc}
   */
  public function __construct(NodeownershipClaimUsage $nodeonweshipClaim) {
    $this->nodeownershipClaim = $nodeonweshipClaim;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
    $container->get('nodeownership_claim.usage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to approve this claim');
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
    return $this->t('Approve');
  }

  /**
   * {@inheritdoc}
   *
   * Approve the entity and log the event.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();

    // Updated Claimed node ownership.
    $node = $entity->getNode();
    $claimStatus = $this->nodeownershipClaim->claimedStatus($node->id());
    if ($claimStatus != NODEOWNERSHIP_CLAIM_APPROVED) {
      $claimed_uid = $entity->getOwnerId();
      $node->setOwnerId($claimed_uid);
      $node->save();

      // Update Claimed Entity Status to approved.
      $entity->setStatus(NODEOWNERSHIP_CLAIM_APPROVED);
      $entity->save();
      drupal_set_message($this->t('Claim for this node is approved'));
      \Drupal::logger('nodeowernship_claim')->notice('Approved claim @claim_id by user @uid for node @nid',
      array(
        '@claim_id' => $this->entity->id(),
        '@uid' => $claimed_uid,
        '@nid' => $node->id(),
      ));
    }
    else {
      drupal_set_message($this->t('Claim for this node is already approved'));
    }
    $form_state->setRedirect('entity.nodeownership_claim.collection');
  }

}
