<?php

namespace Drupal\Tests\freelinking\Unit\Plugin\freelinking;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\freelinking\User;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the user plugin.
 *
 * @group freelinking
 */
class UserTest extends UnitTestCase {

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
    $tProphet->translateString(Argument::any())->willReturn('Click to view user profile.');
    $this->translationInterfaceMock = $tProphet->reveal();

    // Mock current user interface.
    $currentUserProphet = $this->prophesize('\Drupal\Core\Session\AccountProxyInterface');
    $currentUserProphet->hasPermission('access user profiles')->willReturn(TRUE);

    // Mock user account entity.
    $userProphet = $this->prophesize('\Drupal\user\Entity\User');
    $userProphet->id()->willReturn(1);
    $userProphet->getDisplayName()->willReturn('admin');

    // Mock user storage interface.
    $userStorageProphet = $this->prophesize('\Drupal\user\UserStorageInterface');
    $userStorageProphet->load(1)->willReturn($userProphet->reveal());
    $userStorageProphet->load(2)->willReturn(NULL);

    // Mock entity type manager;
    $entityManagerProphet = $this->prophesize('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $entityManagerProphet->getStorage('user')->willReturn($userStorageProphet->reveal());

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entityManagerProphet->reveal());
    $container->set('string_translation', $this->translationInterfaceMock);
    $container->set('current_user', $currentUserProphet->reveal());
    \Drupal::setContainer($container);

    $this->container = $container;

    $plugin_definition = [
      'id' => 'user',
      'title' => 'User',
      'hidden' => FALSE,
      'weight' => 0,
      'settings' => [],
    ];
    $this->plugin = User::create($container, [], 'user', $plugin_definition);
  }

  /**
   * Assert that getIndicator method returns correct value.
   *
   * @param string $indicator
   *   The indicator string to test.
   * @param int $expected
   *   The expected result from preg_match().
   *
   * @dataProvider indicatorProvider
   */
  public function testGetIndicator($indicator, $expected) {
    $this->assertEquals($expected, preg_match($this->plugin->getIndicator(), $indicator));
  }

  /**
   * Assert the getTip method returns correct value.
   */
  public function testGetTip() {
    $this->assertEquals('Click to view user profile.', $this->plugin->getTip()->render());
  }

  /**
   * Assert the buildLink method returns render array.
   *
   * @param array $target
   *   The target array.
   * @param array $expected
   *   The expected render array.
   * @param boolean $shouldFailover
   *   Test logic to place container-dependent render array items.
   *
   * @dataProvider buildLinkProvider
   */
  public function testBuildLink(array $target, array $expected, $shouldFailover = FALSE) {

    if ($shouldFailover) {
      $expected['#message'] = new TranslatableMarkup(
        $expected['#message'],
        ['%user' => '2'],
        [],
        $this->translationInterfaceMock
      );
    }
    else {
      $expected['#url'] = Url::fromRoute('entity.user.canonical', ['user' => 1], ['language' => NULL]);
      $expected['#attributes']['title'] = new TranslatableMarkup(
        'Click to view user profile.',
        [],
        [],
        $this->translationInterfaceMock
      );
    }

    $this->assertArrayEquals($expected, $this->plugin->buildLink($target));
  }

  /**
   * Provide test parameters for ::testGetIndicator.
   *
   * @return array
   *   An array of test parameters.
   */
  public function indicatorProvider() {
    return [
      ['nomatch', 0],
      ['u', 1],
      ['user', 1],
      ['username', 1],
      ['uid', 1],
      ['userid', 1],
    ];
  }

  /**
   * Provide test parameters for ::testBuildLink.
   *
   * @return array
   *   An array of test parameters.
   */
  public function buildLinkProvider() {
    $failoverTarget = ['target' => 'uid:2', 'dest' => '2', 'language' => NULL];
    $failoverExpected = [
      '#theme' => 'freelink_error',
      '#plugin' => 'user',
      '#message' => 'User %user not found',
    ];
    $successTarget = ['target' => 'uid:1', 'dest' => '1', 'language' => NULL];
    $successExpected = [
      '#type' => 'link',
      '#title' => 'admin',
      '#attributes' => [
        'title' => 'Click to view user profile.',
      ],
    ];
    return [
      [$failoverTarget, $failoverExpected, TRUE],
      [$successTarget, $successExpected, FALSE],
    ];
  }

}
