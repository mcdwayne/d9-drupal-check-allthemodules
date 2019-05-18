<?php

namespace Drupal\people;

/**
 * Defines an interface for people entity storage classes.
 */
interface PeopleStorageInterface {

  /**
   * Get the people's company.
   *
   * @param \Drupal\people\PeopleInterface $people
   *   The people to which the organization are attached to.
   *
   * @return \Drupal\cbo_organization\OrganizationInterface
   *   The people's company.
   */
  public function getCompany(PeopleInterface $people);

}
