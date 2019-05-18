<?php

namespace Drupal\Tests\freelinking\Unit\Plugin\freelinking;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\freelinking\Plugin\freelinking\User;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the user plugin when the current user does not have access.
 *
 * @group freelinking
 */
class UserUnauthorizedTest extends UnitTestCase {

  /**
   * The translation interface.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationInterfaceMock;

  /**
   * The plugin to test.
   *
   * @var \Drupal\freelinking\Plugin\freelinking\Node
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Mock string translation interface.
    $tProphet = $this->prophesize('\Drupal\Core\StringTranslation\TranslationInterface');
    $tProphet->translateString(Argument::any())->willReturn('Unauthorized to view user profile.');
    $this->translationInterfaceMock = $tProphet->reveal();

    // Mock current user interface.
    $currentUserProphet = $this->prophesize('\Drupal\Core\Session\AccountProxyInterface');
    $currentUserProphet->hasPermission('access user profiles')->willReturn(FALSE);

    // Mock entity type manager;
    $entityManagerProphet = $this->prophesize('\Drupal\Core\Entity\EntityTypeManagerInterface');

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entityManagerProphet->reveal());
    $container->set('string_translation', $this->translationInterfaceMock);
    $container->set('current_user', $currentUserProphet->reveal());
    \Drupal::setContainer($container);

    $this->container = $container;

    $plugin_definition = [
      'id' => 'user',
      'title' => 'User',
      'weight' => 0,
      'hidden' => FALSE,
      'settings' => [],
    ];
    $this->plugin = User::create($container, [], 'user', $plugin_definition);
  }

  /**
   * Assert the buildLink method returns correct render array.
   */
  public function testBuildLink() {
    $expected = [
      '#theme' => 'freelink_error',
      '#plugin' => 'user',
      '#message' => new TranslatableMarkup(
        'Unauthorized to view user profile.',
        [],
        [],
        $this->translationInterfaceMock
      ),
    ];
    $target = [
      'target' => 'uid:2|Some user',
      'dest' => '2',
      'language' => NULL,
    ];

    $this->assertArrayEquals($expected, $this->plugin->buildLink($target));
  }

}
