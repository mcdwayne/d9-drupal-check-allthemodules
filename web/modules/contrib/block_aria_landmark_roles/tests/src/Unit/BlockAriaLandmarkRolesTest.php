<?php

namespace Drupal\Tests\block_aria_landmark_roles\Unit;

use Drupal\block_aria_landmark_roles\BlockAriaLandmarkRoles;
use Drupal\Tests\UnitTestCase;

/**
 * Test the BlockAriaLandmarkRoles helper class.
 *
 * @group block_aria_landmark_roles
 * @coversDefaultClass \Drupal\block_aria_landmark_roles\BlockAriaLandmarkRoles
 */
class BlockAriaLandmarkRolesTest extends UnitTestCase {

  /**
   * Test getting the ARIA landmark roles.
   *
   * @covers ::get
   */
  public function testGetRoles() {
    $this->assertEquals([
      'application',
      'banner',
      'complementary',
      'contentinfo',
      'form',
      'main',
      'navigation',
      'search',
    ], BlockAriaLandmarkRoles::get());
  }

  /**
   * Test getting the ARIA landmark roles as an associative array.
   *
   * @covers ::getAssociative
   */
  public function testGetRolesAssociative() {
    $this->assertEquals([
      'application' => 'application',
      'banner' => 'banner',
      'complementary' => 'complementary',
      'contentinfo' => 'contentinfo',
      'form' => 'form',
      'main' => 'main',
      'navigation' => 'navigation',
      'search' => 'search',
    ], BlockAriaLandmarkRoles::getAssociative());
  }

}
