<?php

namespace Drupal\contacts_events\Controller;

use Drupal\contacts_events\Entity\Event;
use Drupal\Core\Controller\ControllerBase;

/**
 * Provides ticket summary report.
 */
class TicketsController extends ControllerBase {

  /**
   * Render the Event tickets report page.
   *
   * @param \Drupal\contacts_events\Entity\Event|null $contacts_event
   *   The Event to link to Tickets reports for.
   *
   * @return array
   *   Renderable ticket summary data.
   */
  public function summary(Event $contacts_event = NULL) {
    $output = [];

    if (empty($contacts_event)) {
      return $output;
    }

    // @todo This needs styling.

    $db = \Drupal::database();
    $query = $db->select('contacts_ticket', 't');
    // @todo Do we need this tag?
    $query->addTag('contacts_events_ticket_stats');
    $query->addExpression('COUNT(DISTINCT t.id)', 'count');
    $query->condition('t.event', $contacts_event->id());

    // Ignore pending tickets.
    $statuses = ['cancelled'];

    // Find other unconfirmed statuses.
    $workflow = \Drupal::service('plugin.manager.workflow')
      ->createInstance('contacts_events_order_item_process');
    foreach ($workflow->getStates() as $state) {
      if (in_array('confirm', array_keys($workflow->getPossibleTransitions($state->getId())))) {
        $statuses[] = $state->getId();
      }
    }

    // Get ticket status from Order Item state.
    // @todo Should we use ticket status instead?
    $query->join('commerce_order_item', 'ti', 't.id = %alias.purchased_entity and %alias.type = :contacts_ticket', [':contacts_ticket' => 'contacts_ticket']);
    $query->join('commerce_order_item__state', 'ts', 'ti.order_item_id = %alias.entity_id and ti.type = %alias.bundle');
    $query->condition('ts.state_value', $statuses, 'NOT IN');

    // Group by status.
    $query->groupBy('ts.state_value');
    $query->addField('ts', 'state_value');

    // Get hold of all our counts.
    $counts = $query->execute()->fetchAllKeyed();
    $total = array_sum($counts);

    $output['general'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['ocrm-summary'],
      ],
    ];
    $output['general']['total'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['ocrm-stat'],
      ],
    ];
    $output['general']['total']['count'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $this->formatPlural($total, '@count ticket', '@count tickets'),
    ];

    if ($contacts_event->hasField('capacity') && !$contacts_event->get('capacity')->isEmpty() && $capacity = $contacts_event->get('capacity')->value) {
      $output['general']['total']['percent'] = [
        '#markup' => t('@percent% of @total spaces', [
          '@percent' => floor($total / $capacity * 100),
          '@total' => $capacity,
        ]),
      ];
    }

    \Drupal::moduleHandler()->alter('contacts_events_ticket_stats', $output, $counts);

    return $output;
  }

}
