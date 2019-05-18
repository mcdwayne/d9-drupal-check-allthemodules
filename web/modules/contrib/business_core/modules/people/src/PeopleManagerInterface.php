<?php

namespace Drupal\people;

/**
 * People manager contains common functions to manage people.
 */
interface PeopleManagerInterface {

  /**
   * Gets the current active user's company.
   *
   * @return \Drupal\cbo_organization\OrganizationInterface
   *   The current company.
   */
  public function currentCompany();

  /**
   * Gets the current active user's organization.
   *
   * @return \Drupal\cbo_organization\OrganizationInterface
   *   The current organization.
   */
  public function currentOrganization();

  /**
   * Gets the current active user's people entity.
   *
   * @return \Drupal\people\PeopleInterface
   *   The current people.
   */
  public function currentPeople();

}
