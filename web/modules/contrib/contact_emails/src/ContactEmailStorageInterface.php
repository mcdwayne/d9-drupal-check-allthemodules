<?php

namespace Drupal\contact_emails;


use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for contact email storage.
 */
interface ContactEmailStorageInterface extends ContentEntityStorageInterface {

  /**
   * Checks if there are contact emails for the provided form.
   *
   * @param string $contact_form_id
   *   The contact form ID to check for.
   * @param bool $enabled_only
   *   Whether or not to filter by enabled emails only.
   *
   * @return bool
   *   TRUE if there are contact emails defined, FALSE otherwise.
   */
  public function hasContactEmails($contact_form_id, $enabled_only = FALSE);

  /**
   * Loads the valid contact emails for the given contact form.
   *
   * @param string $contact_form_id
   *   The contact form ID to load emails for.
   * @param bool $enabled_only
   *   Whether or not to filter by enabled emails only.
   *
   * @return \Drupal\contact_emails\Entity\ContactEmailInterface[]
   *   The valid contact emails.
   */
  public function loadValid($contact_form_id, $enabled_only = FALSE);

}
