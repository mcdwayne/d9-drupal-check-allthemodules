<?php

namespace Drupal\nodeownership\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\nodeownership\NodeownershipClaimUsage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the nodeownership_claim entity add/edit forms.
 *
 * @ingroup nodeownership
 */
class NodeownershipClaimForm extends ContentEntityForm {

  protected $nodeonweshipClaim;

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
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $text = NULL;
    $form = parent::buildForm($form, $form_state);
    $isNodeClaimed = $this->nodeownershipClaim->claimedStatus($node);
    if ($isNodeClaimed == NODEOWNERSHIP_CLAIM_APPROVED) {
      $text = $this->t('This node is already claimed.');
    }
    else {
      $claimedByMe = $this->nodeownershipClaim->claimedByMe($node);
      if ($claimedByMe != NULL) {
        switch ($claimedByMe) {
          case NODEOWNERSHIP_CLAIM_DECLINED:
            $text = $this->t('Your claim has been declined by the site administrator.');
            break;

          case NODEOWNERSHIP_CLAIM_PENDING:
            $text = $this->t('Your claim reqeust has been recieved by the site administrator and is pending for approval.');
            break;
        }
      }
      else {
        $form['nid'] = array(
          '#type' => 'value',
          '#value' => $node,
        );
        $form['status'] = array(
          '#type' => 'value',
          '#value' => NODEOWNERSHIP_CLAIM_PENDING,
        );
      }
    }

    if (!empty($text)) {
      $form = array();
      $form['claim_exists'] = array(
        '#markup' => $text,
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->save();
  }

}
