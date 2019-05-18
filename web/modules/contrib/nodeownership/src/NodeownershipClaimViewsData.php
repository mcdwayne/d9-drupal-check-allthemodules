<?php

namespace Drupal\nodeownership;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the nodeownership_claim entity type.
 */
class NodeownershipClaimViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['nodeownership']['status'] = array(
      'title' => $this->t('Claim Status'),
      'help' => $this->t('The status of a given claim. E.g., pending, approved, or declined.'),
      'field' => array(
        'id' => 'nodeownership_claim_status_field',
        'click sortable' => TRUE,
      ),
      'filter' => array(
        'id' => 'nodeownership_claim_status_filter',
        'label' => $this->t('Claim Status'),
        'use equal' => TRUE,
      ),
    );

    // Accept link.
    $data['nodeownership']['approve_link'] = array(
      'field' => array(
        'field' => 'id',
        'title' => $this->t('Approve link'),
        'help' => $this->t('Approve claim'),
        'id' => 'nodeownership_claim_approve_field',
      ),
    );

    // Decline link.
    $data['nodeownership']['decline_link'] = array(
      'field' => array(
        'field' => 'id',
        'title' => $this->t('Decline link'),
        'help' => $this->t('Decline claim.'),
        'id' => 'nodeownership_claim_decline_field',
      ),
    );
    return $data;
  }

}
