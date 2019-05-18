<?php

namespace Drupal\Tests\adva\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\adva\Plugin\adva\AccessProvider\AnonymousAccessProvider;
use Drupal\adva\Plugin\adva\AccessConsumer;
use Drupal\user\Entity\User;

/**
 * Provides basic tests for the AnonymousAccessProvider plugin.
 *
 * @group adva
 */
class AnonymousAccessProviderTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['adva', 'user'];

  /**
   * Tests record generation.
   */
  public function testProviderTest() {
    $consumer_definition = [
      'id' => 'user',
      'entityType' => 'user',
    ];
    $consumer_config = [
      'status' => TRUE,
      'dependencies' => [],
      'id' => 'user',
      'settings' => [],
      'providers' => [
        'anonymous' => 'anonymous',
      ],
      'provider_config' => [
        'anonymous' => [
          'operations' => [
            'view' => [
              'anonymous' => 1,
            ],
            'update' => [],
            'delete' => [],
          ],
        ],
      ],
    ];

    $consumer = AccessConsumer::create($this->container, $consumer_config, 'user', $consumer_definition);

    $providers = $consumer->getAccessProviders();
    $this->assertEquals(1, count($providers), 'Exactly one provider should be configued, ' . count($providers) . ' exist.');

    $user = User::create();

    $records = $consumer->getAccessRecords($user);

    $expected_records = [
      [
        'realm' => 'anonymous',
        'gid' => 1,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
        'langcode' => $user->language()->getId(),
      ],
    ];

    $this->assertEquals($expected_records, $records, 'Generated records did not match expectations.');
  }

  /**
   * Tests record generation.
   */
  public function testRecordsFromConfig() {

    $consumer_definition = [
      'id' => 'user',
      'entityType' => 'user',
    ];
    $provider_definition = [
      'id' => 'anonymous',
      'label' => 'Anonymous Access',
      'operations' => [
        'view',
        'update',
        'delete',
      ],
    ];

    $array = [];

    $consumer = AccessConsumer::create($this->container, $array, 'user', $consumer_definition);

    $anonProvider = AnonymousAccessProvider::create($this->container, $array, 'anonymous', $provider_definition, $consumer);

    $config_variants = [
      // Test none.
      [],
      // Test disabled.
      [
        'view' => [
          'anonymous' => 0,
        ],
      ],
      // Test view only, other empty.
      [
        'view' => [
          'anonymous' => 1,
        ],
      ],
      // Test update only, other empty.
      [
        'update' => [
          'anonymous' => 1,
        ],
      ],
      // Test delete only, other empty.
      [
        'delete' => [
          'anonymous' => 1,
        ],
      ],
      // Test all false.
      [
        'view' => [
          'anonymous' => 0,
        ],
        'update' => [
          'anonymous' => 0,
        ],
        'delete' => [
          'anonymous' => 0,
        ],
      ],
      // Test view only.
      [
        'view' => [
          'anonymous' => 1,
        ],
        'update' => [
          'anonymous' => 0,
        ],
        'delete' => [
          'anonymous' => 0,
        ],
      ],
      // Test update only.
      [
        'view' => [
          'anonymous' => 0,
        ],
        'update' => [
          'anonymous' => 1,
        ],
        'delete' => [
          'anonymous' => 0,
        ],
      ],
      // Test delete only.
      [
        'view' => [
          'anonymous' => 0,
        ],
        'update' => [
          'anonymous' => 0,
        ],
        'delete' => [
          'anonymous' => 1,
        ],
      ],
      // Test view and update.
      [
        'view' => [
          'anonymous' => 1,
        ],
        'update' => [
          'anonymous' => 1,
        ],
        'delete' => [
          'anonymous' => 0,
        ],
      ],
      // Test update and delete.
      [
        'view' => [
          'anonymous' => 0,
        ],
        'update' => [
          'anonymous' => 1,
        ],
        'delete' => [
          'anonymous' => 1,
        ],
      ],
      // Test view and delete.
      [
        'view' => [
          'anonymous' => 1,
        ],
        'update' => [
          'anonymous' => 0,
        ],
        'delete' => [
          'anonymous' => 1,
        ],
      ],
      // Tesst all.
      [
        'view' => [
          'anonymous' => 1,
        ],
        'update' => [
          'anonymous' => 1,
        ],
        'delete' => [
          'anonymous' => 1,
        ],
      ],
    ];

    $default_result = [
      'view' => [
        'anonymous' => 0,
      ],
      'update' => [
        'anonymous' => 0,
      ],
      'delete' => [
        'anonymous' => 0,
      ],
    ];

    foreach ($config_variants as $config_variant) {
      $rules = $anonProvider->getAccessRecordsFromConfig($config_variant);
      $this->assertFalse(empty($rules), 'Access provider should generate atleast 1 rule.');
      foreach ($rules as $rule) {
        $expected_result = $config_variant + $default_result;
        $this->assertEquals('anonymous', $rule['realm'], 'Anonymous rules should have realm anonymous.');
        $this->assertEquals(1, $rule['gid'], 'Anonymous rules should have gid 1');
        $this->assertEquals($expected_result['view']['anonymous'], $rule['grant_view'], 'Incorrect view grant for input ' . var_export($config_variant, TRUE));
        $this->assertEquals($expected_result['update']['anonymous'], $rule['grant_update'], 'Incorrect update grant for input ' . var_export($config_variant, TRUE));
        $this->assertEquals($expected_result['delete']['anonymous'], $rule['grant_delete'], 'Incorrect delete grant for input ' . var_export($config_variant, TRUE));
      }
    }
  }

}
