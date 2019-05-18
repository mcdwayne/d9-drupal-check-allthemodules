<?php

namespace Drupal\Tests\bynder\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\media\Entity\MediaType;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Test the Bynder search widget.
 *
 * @group bynder
 */
class BynderSearchWidgetTest extends JavascriptTestBase {

  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'ctools',
    'entity_browser_bynder_test',
    'bynder',
    'bynder_test_module',
    'node',
    'file',
    'image',
    'system',
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
      'administer bynder configuration',
      'access content',
      'administer entity browsers',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Test search bynder widget.
   */
  public function testBynderSearchWidget() {
    $this->drupalGet('admin/config/content/entity_browser/bynder/widgets');
    $this->assertSession()->pageTextContains('Unable to connect to Bynder. Check if the configuration is set properly or contact support');

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
      'test_multiselect_filter' => [
        'label' => 'Test multiselect filter',
        'name' => 'test_multiselect_filter',
        'isFilterable' => TRUE,
        'isMultiselect' => TRUE,
        'isRequired' => FALSE,
        'zindex' => 1,
        'options' => [
          'optionm1' => [
            'label' => 'multi option 1',
            'id' => 'optionm1',
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
    $this->drupalGet('admin/config/content/entity_browser/bynder/widgets');

    $this->assertSession()->pageTextContains('Allowed metadata properties');
    $this->assertSession()->pageTextContains('Select filters that should be available in the Entity Browser widget.');
    $this->assertSession()->selectExists('Allowed metadata properties');
    $this->getSession()->getPage()->selectFieldOption('Allowed metadata properties', 'test_filter');
    $this->assertSession()->optionExists('Allowed metadata properties', 'test_another_filter');
    $this->assertSession()->optionExists('Allowed metadata properties', 'test_multiselect_filter');
    $this->assertSession()->optionNotExists('Allowed metadata properties', 'test_not_filterable');
    $this->assertSession()->optionNotExists('Allowed metadata properties', 'test_empty_options');

    $this->getSession()->getPage()->pressButton('Finish');

    $image_1 = $this->getTestFiles('image')[1];
    $image_2 = $this->getTestFiles('image')[2];
    $bynder_data = [
      'media' => [
        [
          'type' => 'image',
          'id' => '4DFD39C5-1234-1234-8714AFEE1A617618',
          'name' => 'Photo from London',
          'property_test_multiselect_filter' => [
            'optionm1',
          ],
          'tags' => [
            '234',
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
          'property_test_filter' => [
            'option1',
          ],
          'tags' => [
            '123',
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
    // Test message when bynder connection is not established.
    \Drupal::state()->set('bynder.bynder_test_media_list', FALSE);
    \Drupal::state()->set('bynder.bynder_test_metaproperties', FALSE);
    $this->drupalGet('node/add/media_entity_ct');
    $this->getSession()->getPage()->pressButton('Select assets');
    $this->getSession()->switchToIFrame('entity_browser_iframe_bynder');

    $this->assertSession()->pageTextContains('Unable to connect to Bynder. Check if the configuration is set properly or contact support.');

    \Drupal::service('config.factory')->getEditable('bynder.settings')
      ->set('consumer_key', 'key')
      ->set('consumer_secret', 'secret')
      ->set('token', 'token')
      ->set('token_secret', 'secret')
      ->set('account_domain', 'https://dam.bynder.com')
      ->save();

    $this->drupalGet('node/add/media_entity_ct');
    $this->getSession()->getPage()->pressButton('Select assets');
    $this->getSession()->switchToIFrame('entity_browser_iframe_bynder');

    $this->assertSession()->responseContains('You need to <a href="#login" class="oauth-link">log into Bynder</a> before importing assets.');

    \Drupal::state()->set('bynder.bynder_test_access_token', TRUE);

    $this->drupalGet('node/add/media_entity_ct');
    $this->getSession()->getPage()->pressButton('Select assets');
    $this->getSession()->switchToIFrame('entity_browser_iframe_bynder');
    $this->assertSession()->responseNotContains('You need to <a href="#login" class="oauth-link">log into Bynder</a> before importing assets.');

    $this->assertSession()->pageTextContains('Unable to connect to Bynder. Check if the configuration is set properly or contact support');
    // Test response with bynder data.
    \Drupal::state()->set('bynder.bynder_test_metaproperties', $metaproperties);
    \Drupal::state()->set('bynder.bynder_test_media_list', $bynder_data);

    // Delete all media type and test message on Entity Browser widget.
    $types = \Drupal::entityTypeManager()
      ->getStorage('media_type')
      ->loadMultiple();
    \Drupal::entityTypeManager()->getStorage('media_type')->delete($types);
    $this->drupalGet('admin/config/content/entity_browser/bynder/widgets');
    $this->assertSession()->pageTextContains('You must create a Bynder media type before using this widget.');

    $this->drupalGet('entity-browser/modal/bynder');
    $this->assertSession()->pageTextContains('Media type bynder does not exist. Please fix the Bynder search widget configuration.');

    MediaType::create([
      'id' => 'bynder',
      'label' => 'Bynder assets',
      'source' => 'bynder',
    ])->save();

    $this->drupalGet('admin/config/content/entity_browser/bynder/widgets');
    // Save media bundle.
    $this->getSession()->getPage()->pressButton('Finish');

    $this->drupalGet('entity-browser/modal/bynder');
    $this->assertSession()->responseContains($image_1->name);
    $this->assertSession()->responseContains($image_2->name);
    $this->getSession()->getPage()->fillField('filters[search_bynder]', 'london');
    // Search with Bynder media bundle.
    $this->getSession()->getPage()->pressButton('Search');
    $this->assertSession()->responseNotContains('Photo from Paris');
    $this->assertSession()->responseContains('Photo from London');
    // Assert pager buttons exists.
    $this->assertSession()->pageTextContains('Page 1');
    $this->assertSession()->buttonNotExists('< Previous');
    $this->assertSession()->buttonNotExists('Next >');

    // Test tags filter.
    $this->drupalGet('admin/config/content/entity_browser/bynder/widgets');
    $this->assertSession()->checkboxNotChecked('Enable tags filter');

    $tags = [
      0 => ['id' => '123', 'tag' => 'First tag'],
      1 => ['id' => '234', 'tag' => 'Second tag'],
    ];
    \Drupal::state()->set('bynder.bynder_test_tags', $tags);

    // Make sure tags filter don't appear when disabled.
    $this->drupalGet('entity-browser/modal/bynder');
    $this->assertSession()->elementNotExists('css', '#edit-filters-tag');

    // Enable and test tags filter.
    $this->drupalGet('admin/config/content/entity_browser/bynder/widgets');
    $this->getSession()->getPage()->checkField('Enable tags filter');
    $this->getSession()->getPage()->pressButton('Finish');
    $this->drupalGet('entity-browser/modal/bynder');
    $this->assertSession()->selectExists('Tags');
    $this->assertSession()->optionExists('Tags', 'First tag');
    $this->assertSession()->optionExists('Tags', 'Second tag');
    $this->assertSession()->responseContains($image_1->name);
    $this->assertSession()->responseContains($image_2->name);
    $this->getSession()->getPage()->selectFieldOption('filters[tags][]', 'Second tag');
    $this->getSession()->getPage()->pressButton('Search');
    $this->assertSession()->responseNotContains('Photo from Paris');

    // Test the single meta-property filter.
    $this->drupalGet('admin/config/content/entity_browser/bynder/widgets');
    $this->getSession()->getPage()->selectFieldOption('Allowed metadata properties', 'test_filter');
    $this->getSession()->getPage()->pressButton('Finish');

    $this->drupalGet('entity-browser/modal/bynder');
    $this->assertSession()->responseContains($image_1->name);
    $this->assertSession()->responseContains($image_2->name);
    $this->getSession()->getPage()->selectFieldOption('filters[meta_properties][test_filter]', 'option1');
    $this->getSession()->getPage()->pressButton('Search');
    $this->assertSession()->responseContains('Photo from Paris');
    $this->assertSession()->responseNotContains('Photo from London');

    // Test multi select meta-property filter.
    $this->drupalGet('admin/config/content/entity_browser/bynder/widgets');
    $this->getSession()->getPage()->selectFieldOption('Allowed metadata properties', 'test_multiselect_filter');
    $this->getSession()->getPage()->pressButton('Finish');

    $this->drupalGet('entity-browser/modal/bynder');
    $this->assertSession()->responseContains($image_1->name);
    $this->assertSession()->responseContains($image_2->name);
    $this->getSession()->getPage()->selectFieldOption('filters[meta_properties][test_multiselect_filter][]', 'optionm1');
    $this->getSession()->getPage()->pressButton('Search');
    $this->assertSession()->responseNotContains('Photo from Paris');
    $this->assertSession()->responseContains('Photo from London');
  }

}
