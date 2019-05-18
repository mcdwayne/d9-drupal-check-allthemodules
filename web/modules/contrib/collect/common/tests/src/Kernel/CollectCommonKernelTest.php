<?php

namespace Drupal\Tests\collect_common\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests features of the collect_common module.
 *
 * @group collect_common
 */
class CollectCommonKernelTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'entity_test',
    'collect_common',
  ];

  /**
   * Tests whether entity is eligible to be captured.
   */
  public function testEntityCaptureCheck() {
    // Tests the case where all entities of an entity type are eligible for
    // continuous capturing.
    $configuration = [
      'entity_test' => [
        'continuous' => [
          'default' => 'all',
          'bundles' => [],
        ]
      ],
    ];
    $entity = EntityTest::create(['type' => 'penguin']);
    $user = User::create([]);
    $this->assertTrue(collect_common_entity_capture_check($entity, $configuration));
    $this->assertFalse(collect_common_entity_capture_check($user, $configuration));

    // Tests the case where all entities of an entity type are eligible for
    // continuous capturing except entities from excluded bundles.
    $configuration['entity_test']['continuous']['bundles'] = ['penguin'];
    $this->assertFalse(collect_common_entity_capture_check($entity, $configuration));

    // Tests the case where entities of an entity type are not eligible for
    // continuous capturing except entities from included bundles.
    $configuration['entity_test']['continuous']['default'] = 'none';
    $this->assertTrue(collect_common_entity_capture_check($entity, $configuration));
    $entity = EntityTest::create(['type' => 'lion']);
    $this->assertFalse(collect_common_entity_capture_check($entity, $configuration));
  }
}
