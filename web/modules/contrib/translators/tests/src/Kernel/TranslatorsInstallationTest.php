<?php

namespace Drupal\Tests\translators\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\translators\Services\TranslatorSkills;

/**
 * Class TranslatorsInstallationTest.
 *
 * @package Drupal\Tests\translators\Kernel
 *
 * @group translators
 */
class TranslatorsInstallationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['translators'];

  /**
   * Simply check that the module has been installed.
   */
  public function testModuleInstallation() {
    $this->assertTrue($this->container->get('module_handler')
      ->moduleExists('translators'));
    $this->assertTrue($this->container->has('translators.skills'));
    $this->assertInstanceOf(TranslatorSkills::class, $this->container->get('translators.skills'));
  }

}
