<?php

namespace Drupal\content_fixtures\Fixture;

use Drupal\content_fixtures\Service\ReferenceRepositoryInterface;

/**
 * Interface SharedFixtureInterface
 */
interface SharedFixtureInterface extends FixtureInterface {
  /**
   * Set the reference repository.
   *
   * @param ReferenceRepositoryInterface $referenceRepository
   */
  public function setReferenceRepository(ReferenceRepositoryInterface $referenceRepository);
}
