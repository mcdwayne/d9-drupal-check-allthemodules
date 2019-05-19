<?php

namespace Drupal\zendesk_tickets\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\zendesk_tickets\ZendeskTicketFormTypeInterface;

/**
 * Returns responses for Zendesk Ticket Form Types UI routes.
 */
class ZendeskTicketFormTypesUIController extends ControllerBase {

  /**
   * Calls a method on a form type and reloads the listing page.
   *
   * @param ZendeskTicketFormTypeInterface $zendesk_ticket_form_type
   *   The form type being acted upon.
   * @param string $op
   *   The operation to perform, e.g., 'enable' or 'disable'.
   * @param Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Either returns a rebuilt listing page as an AJAX response, or redirects
   *   back to the listing page.
   */
  public function ajaxOperation(ZendeskTicketFormTypeInterface $zendesk_ticket_form_type, $op, Request $request) {
    // Set the admin forced status.
    if ($op == 'enable' || $op == 'disable') {
      $zendesk_ticket_form_type->setHasLocalStatus(TRUE);
    }

    // Perform the operation and save.
    $zendesk_ticket_form_type->$op()->save();

    // If the request is via AJAX, return the rendered list as JSON.
    if ($request->request->get('js')) {
      $list = $this->entityManager()->getListBuilder('zendesk_ticket_form_type')->render();
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('#zendesk-ticket-form-types-entity-list', $list));
      return $response;
    }

    // Otherwise, redirect back to the page.
    return $this->redirect('entity.zendesk_ticket_form_type.collection');
  }

  /**
   * Returns the form to "edit" a form type.
   *
   * @param ZendeskTicketFormTypeInterface $zendesk_ticket_form_type
   *   The form type to be edited.
   *
   * @return array
   *   The page render array.
   */
  public function edit(ZendeskTicketFormTypeInterface $zendesk_ticket_form_type) {
    $build['#title'] = $zendesk_ticket_form_type->label();

    $list_builder = $this->entityTypeManager()->getListBuilder($zendesk_ticket_form_type->getEntityTypeId());
    $build['edit'] = [
      '#type' => 'table',
      '#rows' => [$list_builder->buildRow($zendesk_ticket_form_type)],
    ];

    return $build;
  }

}
