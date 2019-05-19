<?php

namespace Drupal\zendesk_tickets;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Zendesk Ticket Form type entity.
 */
interface ZendeskTicketFormTypeInterface extends ConfigEntityInterface {

  /**
   * Flag the entity as having a local status set in Drupal.
   *
   * This can be used in an admin UI to force a form to be disabled.
   * A locally disabled form will not be enabled by the next import.
   *
   * @param bool $has_local_status
   *   TRUE to flag the status as being locally set.
   */
  public function setHasLocalStatus($has_local_status = TRUE);

  /**
   * Determines if the entity's status has been set locally in Drupal.
   *
   * @return bool
   *   TRUE if the forced status was set.
   */
  public function hasLocalStatus();

  /**
   * Returns the imported timestamp.
   *
   * @return int
   *   The imported timestamp.
   */
  public function getImportedTime();

  /**
   * Sets the imported timestamp.
   *
   * @param int $timestamp
   *   The imported timestamp.
   */
  public function setImportedTime($timestamp);

  /**
   * Get the form weight in lists.
   *
   * @return int
   *   The form weight.
   */
  public function getWeight();

  /**
   * Sets the form weight in lists.
   *
   * @param int $weight
   *   The form weight.
   */
  public function setWeight($weight);

  /**
   * Determines the form status based on the Zendesk form data object.
   *
   * @return bool
   *   TRUE if the form is considered to be enabled.
   */
  public function ticketFormStatus();

  /**
   * Returns the raw Zendesk form JSON string.
   *
   * @return string
   *   The encoded form data JSON string.
   */
  public function getTicketFormData();

  /**
   * Set the raw Zendesk form JSON string.
   *
   * Note: Implementing classes should clear the related form data object.
   *
   * @param string $string
   *   The encoded form data JSON string.
   */
  public function setTicketFormData($string);

  /**
   * Returns the raw Zendesk form data JSON object.
   *
   * @return object
   *   The decoded form data JSON.
   */
  public function getTicketFormObject();

  /**
   * Sets the raw Zendesk form data JSON object.
   *
   * Note: Implementing classes should set the related form data string.
   *
   * @param object $object
   *   The decoded form data JSON object.
   */
  public function setTicketFormObject($object);

  /**
   * Returns the Drupal form array.
   *
   * @return array
   *   The Drupal form array.
   */
  public function buildTicketForm();

  /**
   * Determines if this ticket form can be submitted.
   *
   * @return bool
   *   TRUE if the form can be submitted.
   */
  public function canSubmit();

  /**
   * Determines if this ticket form supports file uploads.
   */
  public function supportsFileUploads();

}
