<?php

namespace Drupal\Tests\local_translation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\local_translation\Services\LocalTranslationUserSkills;

/**
 * Class LocalTranslationInstallationTest.
 *
 * @package Drupal\Tests\local_translation\Kernel
 *
 * @group local_translation
 */
class LocalTranslationInstallationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['local_translation'];

  /**
   * Simply check that the module has been installed.
   */
  public function testModuleInstallation() {
    $this->assertTrue($this->container->get('module_handler')
      ->moduleExists('local_translation'));
    $this->assertTrue($this->container->has('local_translation.user_skills'));
    $this->assertInstanceOf(LocalTranslationUserSkills::class, $this->container->get('local_translation.user_skills'));
  }

}
