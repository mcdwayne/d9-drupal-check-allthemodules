<?php

namespace Drupal\entity_pilot\Plugin\Field\FieldType;

use Drupal\Core\Session\AccountInterface;
use Drupal\options\Plugin\Field\FieldType\ListStringItem;

/**
 * Plugin implementation of the 'ep_approved_passengers' field type.
 *
 * @FieldType(
 *   id = "ep_approved_passengers",
 *   label = @Translation("Approved Passengers"),
 *   description = @Translation("Stores list of approved passengers"),
 *   default_widget = "ep_approved_passengers",
 *   default_formatter = "list_default",
 *   no_ui = TRUE
 * )
 */
class ApprovedPassengers extends ListStringItem {

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    /** @var \Drupal\entity_pilot\ArrivalInterface $entity */
    $entity = $this->getEntity();
    $passengers = $entity->getPassengers();
    $options = [];
    foreach ($passengers as $id => $passenger) {
      $options[$id] = $passenger['_links']['self']['href'];
    }
    return $options;
  }

}
