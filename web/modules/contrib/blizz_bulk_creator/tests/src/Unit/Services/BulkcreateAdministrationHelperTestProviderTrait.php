<?php

namespace Drupal\Tests\blizz_bulk_creator\Unit\Services;

use Drupal\blizz_bulk_creator\Entity\BulkcreateUsageInterface;

/**
 * Trait BulkcreateAdministrationHelperTestProviderTrait.
 *
 * Contains methods that provides test data providers
 * for the BulkcreateAdministrationHelper test.
 *
 * @package Drupal\Tests\blizz_bulk_creator\Unit\Services
 */
trait BulkcreateAdministrationHelperTestProviderTrait {

  /**
   * Data provider for the getBulkcreationsByEntityType test.
   *
   * @return array
   *   The data.
   */
  public function providerGetBulkcreationsByEntityType() {
    // Create some test usages.
    $usage1 = $this->prophesize(BulkcreateUsageInterface::class);
    $usage1->get('entity_type_id')->willReturn(10);

    $usage2 = $this->prophesize(BulkcreateUsageInterface::class);
    $usage2->get('entity_type_id')->willReturn(20);

    $usage3 = $this->prophesize(BulkcreateUsageInterface::class);
    $usage3->get('entity_type_id')->willReturn(20);

    return [
      'case 1' => [
        'entity_type_id' => 10,
        'usages' => [
          $usage1->reveal(),
          $usage2->reveal(),
          $usage3->reveal(),
        ],
        'expected_result' => [$usage1->reveal()],
      ],

      'case 2' => [
        'entity_type_id' => 20,
        'usages' => [
          $usage1->reveal(),
          $usage2->reveal(),
          $usage3->reveal(),
        ],
        'expected_result' => [
          1 => $usage2->reveal(),
          2 => $usage3->reveal(),
        ],
      ],

      'case 3' => [
        'entity_type_id' => 40,
        'usages' => [
          $usage1->reveal(),
          $usage2->reveal(),
          $usage3->reveal(),
        ],
        'expected_result' => [],
      ],

      'case 4' => [
        'entity_type_id' => NULL,
        'usages' => [
          $usage1->reveal(),
          $usage2->reveal(),
          $usage3->reveal(),
        ],
        'expected_result' => [
          10 => [
            $usage1->reveal(),
          ],
          20 => [
            1 => $usage2->reveal(),
            2 => $usage3->reveal(),
          ],
        ],
      ],
    ];
  }

  /**
   * Provides test data for the getFieldWidget method.
   *
   * @return array
   *   The test data.
   */
  public function providerGetFieldWidget() {
    return [
      'field type: entity_reference' => [
        'field_definition_type' => 'entity_reference',
      ],
      'field type: entity_reference_revisions' => [
        'field_definition_type' => 'entity_reference_revisions',
      ],
      'other field type' => [
        'field_definition_type' => 'whatever',
      ],
    ];
  }

}
