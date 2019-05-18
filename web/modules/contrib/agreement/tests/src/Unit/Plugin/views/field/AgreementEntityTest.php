<?php

namespace Drupal\Tests\agreement\Unit\Plugin\views\field;

use Drupal\agreement\Entity\Agreement;
use Drupal\agreement\Plugin\views\field\AgreementEntity;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\views\ResultRow;

/**
 * Tests the agreement entity views field plugin.
 *
 * @group agreement
 */
class AgreementEntityTest extends UnitTestCase {

  /**
   * Agreement entity plugin.
   *
   * @var \Drupal\agreement\Plugin\views\field\AgreementEntity
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $agreement = new Agreement([
      'id' => 'default',
      'label' => 'Default agreement',
      'path' => '/agreement',
      'agreement' => '',
      'settings' => [
        'visibility' => ['settings' => 0, 'pages' => []],
        'roles' => ['authenticated'],
        'frequency' => -1,
        'title' => 'Our agreement',
      ],
    ], 'agreement');

    $styleProphet = $this->prophesize('\Drupal\views\Plugin\views\style\DefaultStyle');

    $viewProphet = $this->prophesize('\Drupal\views\ViewExecutable');
    $viewProphet->getStyle()->willReturn($styleProphet->reveal());

    $storageProphet = $this->prophesize('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $storageProphet
      ->loadMultiple()
      ->willReturn(['default' => $agreement]);

    $entityManagerProphet = $this->prophesize('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $entityManagerProphet
      ->getStorage('agreement')
      ->willReturn($storageProphet->reveal());

    $definition = ['id' => 'agreement_entity'];

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entityManagerProphet->reveal());
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->plugin = new AgreementEntity(
      [],
      'agreement_entity',
      $definition,
      $container->get('entity_type.manager'));
    $this->plugin->view = $viewProphet->reveal();
    $this->plugin->field_alias = 'type';
  }

  /**
   * Asserts the default display option.
   */
  public function testDefineOptions() {
    $expected = ['default' => ['label']];
    $this->assertEquals($expected, $this->plugin->defineOptions()['display']);
  }

  /**
   * Asserts agreement entity set on results.
   */
  public function testPreRender() {
    $values = [
      new ResultRow(['type' => 'default', 'uid' => 1]),
      new ResultRow(['type' => 'default', 'uid' => 2]),
      new ResultRow(['uid' => 3]),
    ];

    $this->plugin->preRender($values);
    $this->assertObjectHasAttribute('_agreement', $values[0]);
  }

  /**
   * Asserts that render builds markup based on options.
   *
   * @param array $options
   *   An array of "display" options.
   * @param string $expected_key
   *   The expected array key or result.
   *
   * @dataProvider renderProvider
   */
  public function testRender(array $options, $expected_key) {
    $this->plugin->options += ['display' => $options];
    $values = [
      new ResultRow(['type' => 'default', 'uid' => 2]),
    ];
    $this->plugin->preRender($values);
    $markup = $this->plugin->render($values[0]);

    if (empty($options)) {
      $this->assertEquals($expected_key, $markup);
    }
    else {
      $this->assertArrayHasKey($expected_key, $markup);
    }
  }

  /**
   * Provides test arguments for testing render method.
   *
   * @return array
   *   An indexed array of tests to run with test arguments.
   */
  public function renderProvider() {
    return [
      [[], 'default'],
      [['id'], 'id'],
      [['label'], 'label'],
      [['path'], 'path'],
      [['roles'], 'roles'],
      [['title'], 'title'],
    ];
  }

}
