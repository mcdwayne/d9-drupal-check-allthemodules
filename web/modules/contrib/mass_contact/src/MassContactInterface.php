<?php

namespace Drupal\mass_contact;

use Drupal\mass_contact\Entity\MassContactMessageInterface;

/**
 * Defines an interface for the Mass Contact helper service.
 */
interface MassContactInterface {

  /**
   * User opt-out is disabled.
   */
  const OPT_OUT_DISABLED = 'disabled';

  /**
   * Global opt-out enabled.
   */
  const OPT_OUT_GLOBAL = 'global';

  /**
   * Per-category opt-out enabled.
   */
  const OPT_OUT_CATEGORY = 'category';

  /**
   * The user opt-out field ID.
   */
  const OPT_OUT_FIELD_ID = 'mass_contact_opt_out';

  /**
   * Determines if HTML emails are supported.
   *
   * @return bool
   *   Returns TRUE if the system is capable of sending HTML emails.
   */
  public function htmlSupported();

  /**
   * Main entry point for queuing mass contact emails.
   *
   * @param \Drupal\mass_contact\Entity\MassContactMessageInterface $message
   *   The mass contact message entity.
   * @param array $configuration
   *   An array of configuration. Default values are provided by the mass
   *   contact settings.
   */
  public function processMassContactMessage(MassContactMessageInterface $message, array $configuration = []);

  /**
   * Takes a mass contact, calculates recipients and queues them for delivery.
   *
   * @param \Drupal\mass_contact\Entity\MassContactMessageInterface $message
   *   The mass contact message entity.
   * @param array $configuration
   *   An array of configuration. Default values are provided by the mass
   *   contact settings.
   */
  public function queueRecipients(MassContactMessageInterface $message, array $configuration = []);

  /**
   * Sends a message to a list of recipient user IDs.
   *
   * @param int[] $recipients
   *   An array of recipient user IDs.
   * @param \Drupal\mass_contact\Entity\MassContactMessageInterface $message
   *   The mass contact message entity.
   * @param array $configuration
   *   An array of configuration. Default values are provided by the mass
   *   contact settings.
   */
  public function sendMessage(array $recipients, MassContactMessageInterface $message, array $configuration = []);

  /**
   * Given categories, returns an array of recipient IDs.
   *
   * @param \Drupal\mass_contact\Entity\MassContactCategoryInterface[] $categories
   *   An array of mass contact categories.
   * @param bool $respect_opt_out
   *   Whether to respect opt outs when getting the list of recipients.
   *
   * @return int[]
   *   An array of recipient user IDs.
   */
  public function getRecipients(array $categories, $respect_opt_out);

  /**
   * Get groups of recipients for batch processing.
   *
   * @param int[] $all_recipients
   *   An array of all recipients.
   *
   * @return array
   *   An array of arrays of recipients.
   */
  public function getGroupedRecipients(array $all_recipients);

}
