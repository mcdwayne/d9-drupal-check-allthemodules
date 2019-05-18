<?php

namespace Drupal\Tests\bynder\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests duplicate media on Bynder search widget.
 *
 * @group bynder
 */
class BynderCreateMediaTest extends BrowserTestBase {

  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_browser_bynder_test',
    'bynder',
    'bynder_test_module',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'media_entity_ct', 'name' => 'Media Type']);

    FieldStorageConfig::create([
      'field_name' => 'field_reference',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'media',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_reference',
      'entity_type' => 'node',
      'bundle' => 'media_entity_ct',
      'label' => 'Reference',
      'settings' => [
        'handler' => 'default:media',
        'handler_settings' => [
          'target_bundles' => [
            'bynder' => 'bynder',
          ],
        ],
      ],
    ])->save();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('node.media_entity_ct.default');

    $form_display->setComponent('field_reference', [
      'type' => 'entity_browser_entity_reference',
      'settings' => [
        'entity_browser' => 'bynder',
        'open' => TRUE,
      ],
    ])->save();

    $account = $this->drupalCreateUser([
      'access bynder entity browser pages',
      'create media_entity_ct content',
      'access content',
    ]);
    $this->drupalLogin($account);

    \Drupal::service('config.factory')->getEditable('bynder.settings')
      ->set('consumer_key', 'key')
      ->set('consumer_secret', 'secret')
      ->set('token', 'token')
      ->set('token_secret', 'secret')
      ->set('account_domain', 'https://dam.bynder.com')
      ->save();
  }

  /**
   * Test create media entities on Bynder search widget.
   */
  public function testCreateMediaEntities() {
    \Drupal::state()->set('bynder.bynder_test_access_token', TRUE);
    $metaproperties = [
      'test_filter' => [
        'label' => 'Test filter',
        'name' => 'test_filter',
        'isFilterable' => TRUE,
        'isMultiselect' => FALSE,
        'isRequired' => FALSE,
        'zindex' => 1,
        'options' => [
          'option1' => [
            'label' => 'option',
            'id' => 'option1',
          ],
        ],
      ],
      'test_another_filter' => [
        'label' => 'Test another filter',
        'name' => 'test_another_filter',
        'isFilterable' => TRUE,
        'isMultiselect' => FALSE,
        'isRequired' => FALSE,
        'zindex' => 1,
        'options' => [
          'option1' => [
            'label' => 'option',
            'id' => 'option1',
          ],
        ],
      ],
      'test_not_filterable' => [
        'label' => 'Test not filterable',
        'name' => 'test_not_filterable',
        'isFilterable' => FALSE,
        'isMultiselect' => FALSE,
        'isRequired' => FALSE,
        'zindex' => 1,
        'options' => [
          'option1' => [
            'label' => 'option',
            'id' => 'option1',
          ],
        ],
      ],
      'test_empty_options' => [
        'label' => 'Test no options',
        'isFilterable' => FALSE,
        'zindex' => 1,
        'options' => [],
      ],
    ];
    \Drupal::state()->set('bynder.bynder_test_metaproperties', $metaproperties);

    $image_1 = $this->getTestFiles('image')[1];
    $image_2 = $this->getTestFiles('image')[2];
    $bynder_data = [
      'media' => [
        [
          'type' => 'image',
          'id' => '4DFD39C5-1234-1234-8714AFEE1A617618',
          'name' => 'Photo from London',
          'tags' => [
            0 => 'startups',
            1 => 'london',
          ],
          'extension' => [
            0 => 'jpeg',
          ],
          'keyword' => 'london',
          'thumbnails' => [
            'mini' => file_create_url($image_1->uri),
            'webimage' => file_create_url($image_1->uri),
            'thul' => file_create_url($image_1->uri),
          ],
        ],
        [
          'type' => 'image',
          'id' => '4DFD39C5-4321-4321-8714AFFF1A617618',
          'name' => 'Photo from Paris',
          'tags' => [
            0 => 'start',
            1 => 'paris',
          ],
          'extension' => [
            0 => 'jpeg',
          ],
          'keyword' => 'paris',
          'thumbnails' => [
            'mini' => file_create_url($image_2->uri),
            'webimage' => file_create_url($image_2->uri),
            'thul' => file_create_url($image_2->uri),
          ],
        ],
      ],
      'total' => 2,
    ];
    \Drupal::state()->set('bynder.bynder_test_media_list', $bynder_data);

    // Assert that we have no media created yet.
    $this->assertEquals(0, count(\Drupal::entityTypeManager()->getStorage('media')->loadMultiple()));

    // Fill form with Bynder assets and check if media entities are created.
    $this->drupalGet('entity-browser/modal/bynder');
    $this->getSession()->getPage()->checkField('selection[4DFD39C5-1234-1234-8714AFEE1A617618]');
    $this->getSession()->getPage()->checkField('selection[4DFD39C5-4321-4321-8714AFFF1A617618]');
    $this->getSession()->getPage()->pressButton('Select assets');
    $this->assertEquals(2, count(\Drupal::entityTypeManager()->getStorage('media')->loadMultiple()));

    // Use the same Bynder assets and check if entities are not re-created.
    $this->drupalGet('entity-browser/modal/bynder');
    $this->getSession()->getPage()->checkField('selection[4DFD39C5-1234-1234-8714AFEE1A617618]');
    $this->getSession()->getPage()->checkField('selection[4DFD39C5-4321-4321-8714AFFF1A617618]');
    $this->getSession()->getPage()->pressButton('Select assets');
    $this->assertEquals(2, count(\Drupal::entityTypeManager()->getStorage('media')->loadMultiple()));
  }

}
