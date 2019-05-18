<?php

namespace Drupal\Tests\freelinking\Unit\Plugin\Filter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\freelinking\Plugin\Filter\Freelinking;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the freelinking plugin.
 *
 * @group freelinking
 */
class FreelinkingTest extends UnitTestCase {

  /**
   * Freelinking filter plugin.
   *
   * @var \Drupal\freelinking\Plugin\Filter\Freelinking
   */
  protected $filter;

  /**
   * Translation interface mock.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationInterfaceMock;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Mock string translation service.
    $tProphet = $this->prophesize('\Drupal\Core\StringTranslation\TranslationInterface');
    $tProphet
      ->translateString(Argument::type('string'))
      ->will(function ($args) {
        return $args[0];
      });
    $this->translationInterfaceMock = $tProphet->reveal();

    // Create a freelinking plugin mock.
    $pluginProphet = $this->prophesize('\Drupal\freelinking\Plugin\FreelinkingPluginInterface');
    $pluginProphet->getPluginDefinition()->willReturn(['title' => 'Dummy']);
    $pluginProphet->getIndicator()->willReturn('indicator');
    $pluginProphet->getTip()->willReturn('tip');
    $mockPlugin = $pluginProphet->reveal();

    // Create a mock of the freelinking plugin manager.
    $managerProphet = $this->prophesize('\Drupal\freelinking\FreelinkingManagerInterface');
    $managerProphet
      ->createInstance(Argument::type('string'), Argument::type('array'))
      ->willReturn($mockPlugin);

    // Create a mock of the current user.
    $userProphet = $this->prophesize('\Drupal\Core\Session\AccountProxyInterface');

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->translationInterfaceMock);
    $container->set('freelinking.manager', $managerProphet->reveal());
    $container->set('current_user', $userProphet->reveal());

    \Drupal::setContainer($container);

    $definition = [
      'id' => 'freelinking',
      'title' => 'Freelinking',
      'description' => 'Allowms for a flexible format for linking content.',
      'type' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      'provider' => 'freelinking',
      'status' => FALSE,
      'settings' => [
        'default' => 'nodetitle',
        'global_options' => ['ignore_upi' => FALSE],
        'plugins' => [],
        'external_http_request' => FALSE,
      ],
      'weight' => 0,
    ];
    $configuration = [
      'settings' => [
        'plugins' => [
          'dummy' => [
            'plugin' => 'dummy',
            'enabled' => TRUE,
          ],
        ],
      ],
      'weight' => 0,
      'status' => TRUE,
    ];
    $this->filter = new Freelinking(
      $configuration,
      'freelinking',
      $definition,
      $container->get('freelinking.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Asserts that a short tip is returned.
   */
  public function testShortTip() {
    $expected = new TranslatableMarkup(
      'Freelinking helps you easily create HTML links. Links take the form of <code>[[indicator:target|Title]].</code>',
      [],
      [],
      $this->translationInterfaceMock
    );
    $this->assertEquals($expected, $this->filter->tips());
  }

  /**
   * Asserts that a long tip is returned.
   */
  public function testLongTip() {
    $expectedText = <<<EOF
<p>Freelinking helps you easily create HTML links. Links take the form of <code>[[indicator:target|Title]].</code><br />
Below is a list of available types of freelinks you may use, organized as <strong>Plugin Name</strong>: [<em>indicator</em>].</p>
<ul><li><strong>Dummy</strong> [<em>indicator</em>]: tip</li></ul>
EOF;

    $expected = new TranslatableMarkup(
      $expectedText,
      [],
      [],
      $this->translationInterfaceMock
    );
    $this->assertEquals($expected, $this->filter->tips(TRUE));
  }

}
