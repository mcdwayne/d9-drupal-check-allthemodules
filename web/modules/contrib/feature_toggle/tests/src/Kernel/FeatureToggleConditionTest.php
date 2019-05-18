<?php

namespace Drupal\Tests\feature_toggle\Kernel;

use Drupal\feature_toggle\Feature;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Feature Toggle Condition Plugin.
 *
 * @group feature_toggle
 */
class FeatureToggleConditionTest extends KernelTestBase {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The feature manager service.
   *
   * @var \Drupal\feature_toggle\FeatureManagerInterface
   */
  protected $featureManager;

  /**
   * The feature status service.
   *
   * @var \Drupal\feature_toggle\FeatureStatusInterface
   */
  protected $featureStatus;

  /**
   * The 1st feature.
   *
   * @var \Drupal\feature_toggle\FeatureInterface
   */
  protected $feature1;

  /**
   * The 2nd feature.
   *
   * @var \Drupal\feature_toggle\FeatureInterface
   */
  protected $feature2;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'feature_toggle'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->conditionManager = $this->container->get('plugin.manager.condition');
    $this->featureManager = $this->container->get('feature_toggle.feature_manager');
    $this->featureStatus = $this->container->get('feature_toggle.feature_status');

    $this->feature1 = new Feature('feature1', $this->randomMachineName());
    $this->feature2 = new Feature('feature2', $this->randomMachineName());

    $this->featureManager->addFeature($this->feature1);
    $this->featureManager->addFeature($this->feature2);
  }

  /**
   * Tests the feature_toggle condition.
   */
  public function testConditions() {
    /** @var \Drupal\Core\Condition\ConditionInterface $condition */
    $condition = $this->conditionManager->createInstance('feature_toggle')
      ->setConfig('features', [$this->feature1->name() => $this->feature1->name()]);
    $this->assertFalse($condition->execute());
    // Check for the proper summary.
    // Summaries require an extra space due to negate handling in summary().
    $this->assertEquals($condition->summary(), 'One of these features ' . $this->feature1->label() . ' are enabled');

    $this->featureStatus->setStatus($this->feature1, TRUE);
    $condition = $this->conditionManager->createInstance('feature_toggle')
      ->setConfig('features', [$this->feature1->name() => $this->feature1->name()]);
    $this->assertTrue($condition->execute());

    $condition = $this->conditionManager->createInstance('feature_toggle')
      ->setConfig('features', [$this->feature1->name() => $this->feature1->name()])
      ->setConfig('negate', TRUE);
    $this->assertFalse($condition->execute());

    $condition = $this->conditionManager->createInstance('feature_toggle')
      ->setConfig('features', [$this->feature2->name() => $this->feature2->name()]);
    $this->assertFalse($condition->execute());

    $this->featureStatus->setStatus($this->feature2, TRUE);
    $condition = $this->conditionManager->createInstance('feature_toggle')
      ->setConfig('features', [$this->feature1->name() => $this->feature1->name(), $this->feature2->name() => $this->feature2->name()]);
    $this->assertTrue($condition->execute());

    $this->assertEquals($condition->summary(), 'One of these features ' . $this->feature1->label() . ', ' . $this->feature2->label() . ' are enabled');
  }

}
