<?php

namespace Drupal\contacts_events\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\state_machine\Plugin\views\filter\State as MachineState;

/**
 * Filter by workflow state.
 *
 * Overrides GetBundles() to prevent undefined index warnings. The warnings are
 * because \Drupal\state_machine\Plugin\views\filter\State expects all bundles
 * of an entity to have a state or none of them and, for contacts events, only
 * Ticket bundles have a state field.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("contacts_state_machine_state")
 */
class State extends MachineState {

  /**
   * {@inheritdoc}
   */
  protected function getBundles(EntityTypeInterface $entity_type, $field_name) {
    return [
      'contacts_ticket' => 'contacts_ticket',
    ];
  }

}
