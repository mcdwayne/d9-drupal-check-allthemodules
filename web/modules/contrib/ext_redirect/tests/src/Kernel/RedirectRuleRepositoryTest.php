<?php
/**
 * Created by PhpStorm.
 * User: marek.kisiel
 * Date: 25/07/2017
 * Time: 12:26
 */

namespace Drupal\Tests\ext_redirect\Kernel;

use Drupal\ext_redirect\Entity\RedirectRule;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class ExtRedirectConfigSchemaTest
 *
 * @package Drupal\Tests\ext_redirect\Kernel
 * @group ext_redirect
 */
class RedirectRuleRepositoryTest extends KernelTestBase {

  /**
   * @var \Drupal\ext_redirect\Service\RedirectRuleRepository
   */
  protected $repository;

  protected static $modules = ['ext_redirect', 'options', 'system', 'user', 'link'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('redirect_rule');
    $this->repository = \Drupal::service('ext_redirect.repository');
  }

  /**
   * @dataProvider redirectRuleWithoutPathData
   */
  public function testGetRuleForHostWithoutPath(array $ruleData, $ruleData2) {
    $this->createRedirectRules(func_get_args());

    $repository = $this->repository;

    self::assertInstanceOf('\Drupal\ext_redirect\Service\RedirectRuleRepository', $repository);
    /** @var RedirectRule $rule */
    $rule = $repository->getRuleForHostWithoutPath($ruleData['source_site']);
    self::assertInstanceOf('\Drupal\ext_redirect\Entity\RedirectRule', $rule);
    $this->assertEquals($ruleData['source_path'], $rule->getSourcePath());
    $this->assertEquals($ruleData['destination_uri']['uri'], $rule->getDestination());
  }

  /**
   * @dataProvider redirectRuleData
   */
  public function testGetHostRules() {
    $this->createRedirectRules(func_get_args());

    $rules = $this->repository->getHostRules('alias.dev');

    self::assertEquals(3, sizeof($rules));
    /** @var RedirectRule $rule */
    $rule = $rules[0];
    self::assertEquals('any', $rule->getSourceSite());

    $rule = $rules[2];
    self::assertEquals(99, $rule->getWeight());
  }

  /**
   * @dataProvider redirectRuleWithoutPathData
   */
  public function testRedirectRuleArraySource(array $ruleData) {
    $rule = RedirectRule::createFromArray($ruleData);
    $rule->save();
    self::assertInstanceOf('\Drupal\ext_redirect\Entity\RedirectRule', $rule);
    self::assertEquals($rule->getDestination(), $ruleData['destination_uri']['uri']);
    self::assertEquals($rule->getSourcePath(), $ruleData['source_path']);
  }

  /**
   * @dataProvider redirectRuleData
   */
  public function testGetGlobalRules() {
    $this->createRedirectRules(func_get_args());
    $rules = $this->repository->getGlobalRules();

    self::assertEquals(1, count($rules));
    /** @var RedirectRule $rule */
    $rule = $rules[0];
    self::assertEquals('any', $rule->getSourceSite());
  }

  public static function redirectRuleData() {
    return [
      [
        [
          'source_site' => 'alias.dev',
          'source_path' => '/foobar',
          'weight' => 99,
          'destination_uri' => ['uri' => 'internal:/foo'],
        ],
        [
          'source_site' => 'any',
          'source_path' => '/bar',
          'weight' => -1,
          'destination_uri' => ['uri' => 'internal:/foo'],
        ],
        [
          'source_site' => 'alias2.dev',
          'source_path' => '/path',
          'weight' => 77,
          'destination_uri' => ['uri' => 'internal:/foo'],
        ],
        [
          'source_site' => 'alias.dev',
          'source_path' => '/sample',
          'weight' => 82,
          'destination_uri' => ['uri' => 'internal:/foo'],
        ],
      ]
    ];
  }

  public static function redirectRuleWithoutPathData() {
    return [
      [
        [
          'source_site' => 'alias1.dev',
          'source_path' => '*',
          'destination_uri' => ['uri' => 'internal:/foo'],
        ],
        [
          'source_site' => 'alias2.dev',
          'source_path' => "*",
          'destination_uri' => ['uri' => 'internal:/foo'],
        ],

      ]
    ];
  }

  private function createRedirectRules($data) {
    array_map(function($item) {
      RedirectRule::createFromArray($item)->save();
    }, $data);
  }
}