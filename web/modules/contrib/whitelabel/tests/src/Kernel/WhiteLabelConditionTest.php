<?php

namespace Drupal\Tests\whitelabel\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\whitelabel\Entity\WhiteLabel;

/**
 * Tests that conditions, provided by the white label module, are working.
 *
 * @group whitelabel
 */
class WhiteLabelConditionTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['whitelabel', 'options', 'image', 'file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('whitelabel');
  }

  /**
   * Tests white label condition functionality.
   */
  public function testConditions() {
    $manager = $this->container->get('plugin.manager.condition', $this->container->get('container.namespaces'));
    $this->createUser();

    // Create a white label.
    $whitelabel = WhiteLabel::create(['token' => $this->randomMachineName(), 'uid' => 1]);
    $whitelabel->save();

    // Let condition check for enabled white labels only and provide a white
    // label.
    $condition = $manager->createInstance('whitelabel')
      ->setConfig('enabled', ['white labeled' => 'white labeled'])
      ->setContextValue('whitelabel', $whitelabel);
    $this->assertTrue($condition->execute(), 'White label check passes when white label is provided.');
    // Check for correct summary.
    $this->assertEquals('Page is white labeled', $condition->summary());

    // Let condition check for enabled and disabled white labels.
    $condition->setConfig('enabled', ['white labeled' => 'white labeled', 'not white labeled' => 'not white labeled']);
    $this->assertTrue($condition->execute(), 'White label and no white label check passes when white label is provided.');
    // Check for correct summary.
    $this->assertEquals('Page is either white labeled or not', $condition->summary());

    // Let condition check for disabled white labels only.
    $condition->setConfig('enabled', ['not white labeled' => 'not white labeled']);
    $this->assertFalse($condition->execute(), 'No white label check fails when white label is provided.');
    // Check for correct summary.
    $this->assertEquals('Page is not white labeled', $condition->summary());

    // Remove the white label and test for enabled white labels only.
    $condition
      ->setContextValue('whitelabel', NULL)
      ->setConfig('enabled', ['white labeled' => 'white labeled']);
    $this->assertFalse($condition->execute(), 'White label check fails when no white label is provided.');

    // Let condition check for enabled and disabled white labels.
    $condition->setConfig('enabled', ['white labeled' => 'white labeled', 'not white labeled' => 'not white labeled']);
    $this->assertTrue($condition->execute(), 'White label and no white label check passes when no white label is provided.');

    // Let condition check for disabled white labels only.
    $condition->setConfig('enabled', ['not white labeled' => 'not white labeled']);
    $this->assertTrue($condition->execute(), 'No white label check passes when no white label is provided.');
  }

}
