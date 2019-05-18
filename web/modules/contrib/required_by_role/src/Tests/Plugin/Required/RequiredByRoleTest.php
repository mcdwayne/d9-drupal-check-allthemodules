<?php

/**
 * @file
 * Contains \Drupal\required_by_role\Tests\Plugin\Required\RequiredByRoleTest.
 */

namespace Drupal\required_by_role\Tests\Plugin\Required;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Transliteration\PHPTransliteration;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\required_by_role\Plugin\Required\RequiredByRole;

/**
 * Tests the requird_by_role plugin.
 *
 * @group Required API
 * @see \Drupal\required_by_role\Plugin\RequiredByRoleTest
 */
class RequiredByRoleTest extends UnitTestCase {

  /**
   * The required plugin.
   *
   * @var \Drupal\required_by_role\Plugin\Required\RequiredByRole
   */
  protected $plugin;

  /**
   * Method getInfo.
   *
   * @return array
   *   Information regarding to the tests.
   */
  public static function getInfo() {
    return [
      'name' => 'Required by role plugin',
      'description' => 'Test the required by role logic.',
      'group' => 'Required API',
    ];
  }

  /**
   * Caching the plugin instance in the $plugin property.
   */
  public function setUp() {

    $this->plugin = $this->getMockBuilder('Drupal\required_by_role\Plugin\Required\RequiredByRole')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();
  }

  /**
   * Tests the required by role behavior.
   *
   * @dataProvider getRequiredCases
   */
  public function testRequiredByRole($result, $user_roles, $required_roles) {

    $required = $this->plugin->getMatches($user_roles, $required_roles);
    $this->assertEquals($result, $required);

  }

  /**
   * Provides a cases to test.
   */
  public function getRequiredCases() {

    // array(bool $result, array $user_roles, array $required_roles)
    return [
      // User with matching roles.
      [
        TRUE,
        [
          AccountInterface::AUTHENTICATED_ROLE,
          'administrator',
        ],
        [
          'administrator',
        ],
      ],
      // User with no matching roles.
      [
        FALSE,
        [
          AccountInterface::AUTHENTICATED_ROLE,
          'administrator',
        ],
        [
          AccountInterface::ANONYMOUS_ROLE,
        ],
      ],
      // No required roles set.
      [
        FALSE,
        [
          AccountInterface::AUTHENTICATED_ROLE,
          'administrator',
        ],
        [],
      ],
      // Required roles is not an array.
      [
        FALSE,
        [
          AccountInterface::AUTHENTICATED_ROLE,
          'administrator',
        ],
        NULL,
      ],
      // The user has no roles.
      [
        FALSE,
        NULL,
        [
          AccountInterface::AUTHENTICATED_ROLE,
          'administrator',
        ],
      ],
      // The user has no roles and there is no required roles.
      [
        FALSE,
        NULL,
        NULL,
      ],
    ];
  }
}
