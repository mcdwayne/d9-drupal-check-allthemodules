<?php

namespace Drupal\zendesk_tickets;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface to build the Zendesk Ticket Submit Form.
 */
interface ZendeskTicketFormTypeSubmitFormBuilderInterface extends EntityHandlerInterface {

  /**
   * Build the Drupal form array for the Zendesk Ticket form.
   *
   * @param EntityInterface|null $entity
   *   The form type entity to build the form. If not provided, then only the
   *   "ticket_form_id" select element should be provided.
   */
  public function buildForm(EntityInterface $entity = NULL);

}
