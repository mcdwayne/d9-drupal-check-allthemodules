<?php

namespace Drupal\Tests\crm_core_match\Kernel;

use Drupal\crm_core_contact\Entity\Individual;
use Drupal\crm_core_contact\Entity\IndividualType;
use Drupal\crm_core_match\Entity\Matcher;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the field matchers of the default matching engine.
 *
 * @group crm_core
 */
class FieldMatcherTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'field',
    'text',
    'crm_core_contact',
    'crm_core_match',
    'name',
    'views',
    'system',
    'datetime',
    'options',
  ];

  /**
   * The mocked match field plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['crm_core_contact']);
    $this->installConfig(['crm_core_match']);
    $this->installEntitySchema('action');
    $this->installEntitySchema('crm_core_individual');

    IndividualType::create([
      'name' => 'Customer',
      'type' => 'customer',
      'description' => 'A single customer.',
      'primary_fields' => [],
    ])->save();

    $this->pluginManager = $this->container->get('plugin.manager.crm_core_match.match_field');
  }

  /**
   * Tests fields and rules configuration.
   */
  public function testFieldsConfiguration() {
    // Load an existing matcher.
    $individual_matcher = Matcher::load('individual');
    $default_engine = $individual_matcher->getPlugin();
    $configuration = $default_engine->getConfiguration();

    // Add a sample fields configuration.
    $configuration['rules']['name']['title']['status'] = FALSE;
    $configuration['rules']['name']['given']['status'] = TRUE;
    $configuration['rules']['name']['family']['status'] = FALSE;
    $configuration['rules']['type']['value']['status'] = TRUE;
    $configuration['rules']['individual_id']['value']['status'] = FALSE;

    $default_engine->setConfiguration($configuration);
    $individual_matcher->set('plugin', $default_engine);

    // Rules should contain enabled "type" and "name" fields. Name field is
    // enabled because it has (at least) one enabled property.
    $this->assertEquals(['name', 'type'], array_keys($default_engine->getRules()));
  }

  /**
   * Test the text field.
   */
  public function testName() {
    $config = [
      'title' => [
        'score' => 1,
      ],
      'given' => [
        'score' => 10,
      ],
      'middle' => [
        'score' => 1,
      ],
      'family' => [
        'score' => 20,
      ],
      'generational' => [
        'score' => 1,
      ],
      'credentials' => [
        'score' => 1,
      ],
    ];
    /** @var \Drupal\crm_core_contact\Entity\Individual $individual_needle */
    $individual_needle = Individual::create(['type' => 'customer']);
    $individual_needle->set('name', [
      'title' => 'Mr.',
      'given' => 'Gimeno',
      'family' => 'Boomer',
    ])->save();
    /** @var \Drupal\crm_core_contact\Entity\Individual $individual_match */
    $individual_match = Individual::create(['type' => 'customer']);
    $individual_match->set('name', [
      'title' => 'Mr.',
      'given' => 'Gimeno',
      'family' => 'Boomer',
    ])->save();
    /** @var \Drupal\crm_core_contact\Entity\Individual $individual_match2 */
    $individual_match2 = Individual::create(['type' => 'customer']);
    $individual_match2->set('name', [
      'title' => 'Mr.',
      'given' => 'Rodrigo',
      'family' => 'Boomer',
    ])->save();

    $config['field'] = $individual_needle->getFieldDefinition('name');
    /* @var \Drupal\crm_core_match\Plugin\crm_core_match\field\FieldHandlerInterface $text */
    $text = $this->pluginManager->createInstance('name', $config);

    $ids = $text->match($individual_needle);
    $this->assertTrue(array_key_exists($individual_match->id(), $ids), 'Text match returns expected match.');
    $this->assertTrue(array_key_exists($individual_match2->id(), $ids), 'Text match returns expected match.');
    $this->assertEquals(20, $ids[$individual_match->id()]['name.family'], 'Got expected match score.');
    $this->assertEquals(20, $ids[$individual_match2->id()]['name.family'], 'Got expected match score.');

    $ids = $text->match($individual_needle, 'given');
    $this->assertTrue(array_key_exists($individual_match->id(), $ids), 'Text match returns expected match.');
    $this->assertFalse(array_key_exists($individual_match2->id(), $ids), 'Text match does not return wrong match.');
    $this->assertEquals(10, $ids[$individual_match->id()]['name.given'], 'Got expected match score.');
  }

  /**
   * Test the text field.
   */
  public function testText() {
    FieldStorageConfig::create([
      'entity_type' => 'crm_core_individual',
      'type' => 'string',
      'field_name' => 'individual_text',
    ])->save();
    FieldConfig::create([
      'field_name' => 'individual_text',
      'entity_type' => 'crm_core_individual',
      'bundle' => 'customer',
      'label' => t('Text'),
      'required' => FALSE,
    ])->save();
    $config = [
      'value' => [
        'operator' => '=',
        'score' => 42,
      ],
    ];
    /** @var \Drupal\crm_core_contact\Entity\Individual $individual_needle */
    $individual_needle = Individual::create(['type' => 'customer']);
    $individual_needle->set('individual_text', 'Boomer');
    $individual_needle->save();
    /** @var \Drupal\crm_core_contact\Entity\Individual $individual_match */
    $individual_match = Individual::create(['type' => 'customer']);
    $individual_match->set('individual_text', 'Boomer');
    $individual_match->save();

    $config['field'] = $individual_needle->getFieldDefinition('individual_text');
    /* @var \Drupal\crm_core_match\Plugin\crm_core_match\field\FieldHandlerInterface $text */
    $text = $this->pluginManager->createInstance('text', $config);

    $ids = $text->match($individual_needle);
    $this->assertTrue(array_key_exists($individual_match->id(), $ids), 'Text match returns expected match');
    $this->assertEqual(42, $ids[$individual_match->id()]['individual_text.value'], 'Got expected match score');
  }

  /**
   * Test the email field.
   */
  public function testEmail() {
    FieldStorageConfig::create([
      'entity_type' => 'crm_core_individual',
      'type' => 'email',
      'field_name' => 'individual_mail',
    ])->save();
    FieldConfig::create([
      'field_name' => 'individual_mail',
      'entity_type' => 'crm_core_individual',
      'bundle' => 'customer',
      'label' => t('Email'),
      'required' => FALSE,
    ])->save();

    $config = [
      'value' => [
        'operator' => '=',
        'score' => 42,
      ],
    ];
    /** @var \Drupal\crm_core_contact\Entity\Individual $individual_needle */
    $individual_needle = Individual::create(['type' => 'customer']);
    $individual_needle->set('individual_mail', 'boomer@example.com');
    $individual_needle->save();
    /** @var \Drupal\crm_core_contact\Entity\Individual $individual_match */
    $individual_match = Individual::create(['type' => 'customer']);
    $individual_match->set('individual_mail', 'boomer@example.com');
    $individual_match->save();

    $config['field'] = $individual_needle->getFieldDefinition('individual_mail');
    /* @var \Drupal\crm_core_match\Plugin\crm_core_match\field\FieldHandlerInterface $text */
    $text = $this->pluginManager->createInstance('email', $config);

    $ids = $text->match($individual_needle);
    $this->assertTrue(array_key_exists($individual_match->id(), $ids), 'Text match returns expected match');
    $this->assertEqual(42, $ids[$individual_match->id()]['individual_mail.value'], 'Got expected match score');
  }

}
