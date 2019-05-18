<?php

namespace Drupal\mass_Contact;

/**
 * A service for managing user opt-outs of mass contact emails.
 */
interface OptOutInterface {

  /**
   * Finds a list of users that have opted out of emails.
   *
   * @param \Drupal\mass_contact\Entity\MassContactCategoryInterface[] $categories
   *   An array of categories.
   *
   * @return int[]
   *   An array of account IDs that have opted out. This includes global opt-out
   *   as well. If opt-outs are disabled, this method always returns an empty
   *   array.
   */
  public function getOptOutAccounts(array $categories = []);

}
