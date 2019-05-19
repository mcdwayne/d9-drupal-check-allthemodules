<?php

namespace Drupal\Tests\theme_by_author\Kernel;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\NodeCreationTrait;
use Drupal\simpletest\UserCreationTrait;
use Drupal\theme_by_author\AuthorThemeNegotiator;

/**
 * Tests AuthorThemeNegotiator functionality.
 *
 * @coversDefaultClass \Drupal\theme_by_author\AuthorThemeNegotiator
 * @group theme_by_author
 */
class AuthorThemeNegotiatorTest extends KernelTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'field',
    'filter',
    'options',
    'text',
    'system',
    'user',
    'theme_by_author',
  ];

  /**
   * First test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user1;

  /**
   * Second test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user2;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['router', 'sequences']);
    $this->installConfig(['field', 'filter']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    // Create test users.
    $this->user1 = $this->createUser();
    $this->user1->set('theme', [
      [
        'value' => 'my_custom_theme',
      ],
    ])->save();
    $this->user2 = $this->createUser();
    $this->user2->set('theme', [
      [
        'value' => '',
      ],
    ])->save();
  }

  /**
   * Tests the applies() function.
   *
   * @covers ::applies
   */
  public function testApplies() {
    $test_node = $this->createNode();
    $test_node->setOwner($this->user1);
    $test_node2 = $this->createNode();
    $test_node2->setOwner($this->user2);
    $route_match = $this->prophesize(RouteMatchInterface::class);
    $route_match->getRouteName()->willReturn('entity.node.canonical');
    $route_match->getParameter('node')->willReturn($test_node);

    $author_theme_negotiator = new AuthorThemeNegotiator();
    $this->assertTrue($author_theme_negotiator->applies($route_match->reveal()));

    $route_match->getParameter('node')->willReturn($test_node2);
    $this->assertFalse($author_theme_negotiator->applies($route_match->reveal()));
  }

  /**
   * Tests the determineActiveTheme() function.
   *
   * @covers ::determineActiveTheme
   */
  public function testDetermineActiveTheme() {
    $test_node = $this->createNode();
    $test_node->setOwner($this->user1);
    $test_node2 = $this->createNode();
    $test_node2->setOwner($this->user2);
    $route_match = $this->prophesize(RouteMatchInterface::class);
    $route_match->getRouteName()->willReturn('entity.node.canonical');
    $route_match->getParameter('node')->willReturn($test_node);

    $author_theme_negotiator = new AuthorThemeNegotiator();
    $this->assertEquals('my_custom_theme', $author_theme_negotiator->determineActiveTheme($route_match->reveal()));

    $route_match->getParameter('node')->willReturn($test_node2);
    $this->assertNull($author_theme_negotiator->determineActiveTheme($route_match->reveal()));
  }

}
