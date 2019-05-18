<?php

namespace Drupal\Tests\blizz_bulk_creator\Unit\Services;

use Drupal\blizz_bulk_creator\Entity\BulkcreateConfigurationInterface;
use Drupal\blizz_bulk_creator\Entity\BulkcreateUsageInterface;
use Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelper;
use Drupal\blizz_bulk_creator\Services\EntityHelperInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\blizz_bulk_creator\Unit\UnitTestMocksTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelper
 * @group blizz_bulk_creator
 */
class BulkcreateAdministrationHelperTest extends UnitTestCase {

  use UnitTestMocksTrait;
  use BulkcreateAdministrationHelperTestProviderTrait;

  /**
   * The administration helper to test.
   *
   * @var \Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelperInterface
   */
  private $administrationHelper;

  /**
   * The entity type manager mock object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The entity type bundle info mock object.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  private $entityTypeBundleInfo;

  /**
   * The bulk create entity helper mock object.
   *
   * @var \Drupal\blizz_bulk_creator\Services\EntityHelperInterface
   */
  private $entityHelper;

  /**
   * The plugin manager mock object.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  private $pluginManager;

  /**
   * The string translation mock object.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  private $translation;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $this->entityHelper = $this->prophesize(EntityHelperInterface::class);
    $this->pluginManager = $this->prophesize(PluginManagerInterface::class);
    $this->translation = $this->prophesize(TranslationInterface::class);

    $this->administrationHelper = new BulkcreateAdministrationHelper(
      $this->entityTypeManager->reveal(),
      $this->entityTypeBundleInfo->reveal(),
      $this->pluginManager->reveal(),
      $this->entityHelper->reveal(),
      $this->translation->reveal()
    );

  }

  /**
   * Tests the administrationHelper's getBulkcreateConfigurations method.
   */
  public function testGetBulkcreateConfigurations() {
    // The expected result value.
    $expected_result = ['this is the configuration'];

    // Tell the entity storage mock object to return the expected result.
    $this->entityStorageLoadMultiple(
      $this->entityTypeManager,
      'bulkcreate_configuration',
      $expected_result
    );

    // Get the results of the method to test.
    $result = $this->administrationHelper->getBulkcreateConfigurations();
    $result2 = $this->administrationHelper->getBulkcreateConfigurations();

    $this->assertTrue(is_array($result), 'Expected that the result of the first method run is an array.');
    $this->assertEquals($expected_result, $result, 'Expected that the result of the first method run is equal.');

    $this->assertTrue(is_array($result2), 'Expected that the result of the first method run is an array.');
    $this->assertEquals($expected_result, $result2, 'Expected that the result of the first method run is equal.');
  }

  /**
   * Tests the administrationHelper's getAllActiveBulkcreations method.
   */
  public function testGetAllActiveBulkcreations() {
    // The expected result value.
    $expected_result = ['this is the configuration'];

    // Tell the entity storage mock object to return the expected result.
    $this->entityStorageLoadMultiple(
      $this->entityTypeManager,
      'bulkcreate_usage',
      $expected_result
    );

    // Get the results of the method to test.
    $result = $this->administrationHelper->getAllActiveBulkcreations();
    $result2 = $this->administrationHelper->getAllActiveBulkcreations();

    $this->assertTrue(is_array($result), 'Expected that the result of the first method run is an array.');
    $this->assertEquals($expected_result, $result, 'Expected that the result of the first method run is equal.');

    $this->assertTrue(is_array($result2), 'Expected that the result of the first method run is an array.');
    $this->assertEquals($expected_result, $result2, 'Expected that the result of the first method run is equal.');
  }

  /**
   * Tests the administrationHelper's getBulkcreationsByEntityType method.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param array $usages
   *   An array with usages.
   * @param array $expected_result
   *   The expected result.
   *
   * @dataProvider providerGetBulkcreationsByEntityType
   */
  public function testGetBulkcreationsByEntityType(string $entity_type_id = NULL, array $usages, array $expected_result) {
    // Tell the entity storage mock object to return the expected result.
    $this->entityStorageLoadMultiple($this->entityTypeManager, 'bulkcreate_usage', $usages);

    // Execute the method to test.
    $result = $this->administrationHelper->getBulkcreationsByEntityType($entity_type_id);

    $this->assertEquals($expected_result, $result, 'Expected that the result is equal.');
  }

  /**
   * Tests the administrationHelper's getBulkcreateUsage method.
   */
  public function testGetBulkcreateUsage() {
    $id = '42';

    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $entity_storage
      ->load($id)
      ->willReturn($this->prophesize(EntityInterface::class));

    $this->entityTypeManager
      ->getStorage('bulkcreate_usage')
      ->willReturn($entity_storage->reveal());

    // Execute the method to test.
    $result = $this->administrationHelper->getBulkcreateUsage($id);

    $this->assertTrue($result instanceof EntityInterface, 'Not an Instance of EntityInterface');
  }

  /**
   * Tests the administrationHelper's getBulkcreateUsages method.
   */
  public function testGetBulkcreateUsages() {
    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $entity_storage
      ->loadByProperties(Argument::type('array'))
      ->willReturn(['an', 'array']);

    $this->entityTypeManager
      ->getStorage('bulkcreate_usage')
      ->willReturn($entity_storage->reveal());

    $result = $this->administrationHelper->getBulkcreateUsages(
      $this
        ->prophesize(BulkcreateConfigurationInterface::class)
        ->reveal()
    );

    $this->assertTrue(is_array($result), 'Not an array.');
  }

  /**
   * Tests the administrationHelper's getApplicableTargetFields method.
   */
  public function testGetApplicableTargetFields() {
    $this->entityHelper
      ->getReferenceFieldsForTargetBundle(
        Argument::any(),
        Argument::type('string'),
        Argument::type('string')
      )
      ->willReturn([]);

    $this->entityHelper
      ->flattenReferenceFieldsToOptions(Argument::type('array'))
      ->willReturn([]);

    // Execute the method to test.
    $result = $this->administrationHelper->getApplicableTargetFields(
      $this->prophesize(BulkcreateConfigurationInterface::class)->reveal(),
      '10',
      'some_bundle'
    );

    $this->assertTrue(is_array($result), 'Not an array.');
  }

  /**
   * Tests the administrationHelper's dynamicPermissions method.
   */
  public function testDynamicPermissions() {
    $bulkcreation1_id = 10;
    $bulkcreation1_label = 'bulkcreation1_label';

    $bulkcreation2_id = 20;
    $bulkcreation2_label = 'bulkcreation2_label';

    $bulkcreation3_id = 30;
    $bulkcreation3_label = 'bulkcreation3_label';

    $bulkcreation1 = $this->prophesize(BulkcreateUsageInterface::class);
    $bulkcreation1->id()->willReturn($bulkcreation1_id);
    $bulkcreation1->label()->willReturn($bulkcreation1_label);

    $bulkcreation2 = $this->prophesize(BulkcreateUsageInterface::class);
    $bulkcreation2->id()->willReturn($bulkcreation2_id);
    $bulkcreation2->label()->willReturn($bulkcreation2_label);

    $bulkcreation3 = $this->prophesize(BulkcreateUsageInterface::class);
    $bulkcreation3->id()->willReturn($bulkcreation3_id);
    $bulkcreation3->label()->willReturn($bulkcreation3_label);

    // The expected result value.
    $expected_result = [
      $bulkcreation1->reveal(),
      $bulkcreation2->reveal(),
      $bulkcreation3->reveal(),
    ];

    $this->translation
      ->translate(Argument::type('string'), Argument::type('array'))
      ->willReturn(
        'Use Bulkcreation configuration ' . $bulkcreation1_label,
        'Use Bulkcreation configuration ' . $bulkcreation2_label,
        'Use Bulkcreation configuration ' . $bulkcreation3_label
      );

    // Tell the entity storage mock object to return the expected result.
    $this->entityStorageLoadMultiple(
      $this->entityTypeManager,
      'bulkcreate_configuration',
      $expected_result
    );

    // Execute the method to test.
    $result = $this->administrationHelper->dynamicPermissions();

    $this->assertTrue(is_array($result), 'The result is not an array.');
    $this->assertEquals([
      'use bulkcreation ' . $bulkcreation1_id => 'Use Bulkcreation configuration ' . $bulkcreation1_label,
      'use bulkcreation ' . $bulkcreation2_id => 'Use Bulkcreation configuration ' . $bulkcreation2_label,
      'use bulkcreation ' . $bulkcreation3_id => 'Use Bulkcreation configuration ' . $bulkcreation3_label,
    ],
      $result,
      'The result is not equal to the expected result.'
    );
  }

  /**
   * Tests the administrationHelper's getFieldWidget method.
   *
   * @dataProvider providerGetFieldWidget
   */
  public function testGetFieldWidget($field_definition_type) {
    $field_name = 'field_name';

    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getType()->willReturn($field_definition_type);
    $field_definition->getSettings()->willReturn([]);

    $entity_adapter = $this->prophesize(EntityAdapter::class);
    $form = [];
    $form_state = $this->prophesize(FormStateInterface::class);
    $widget_type = 'default_widget';

    $field_widget_plugin = $this->prophesize(WidgetInterface::class);
    $field_widget_plugin->form(Argument::any(), Argument::any(), Argument::any())->willReturn([]);

    $this->pluginManager->getInstance(Argument::type('array'))->willReturn($field_widget_plugin->reveal());

    // Execute the method to test.
    $result = $this->administrationHelper->getFieldWidget(
      $field_name,
      $field_definition->reveal(),
      $entity_adapter->reveal(),
      $form,
      $form_state->reveal(),
      $widget_type
    );

    $this->assertTrue(is_array($result), 'The return value is not an array.');
  }

  /**
   * Tests the administrationHelper's getStructuredBulkcreateTargetFieldArray().
   */
  public function testGetStructuredBulkcreateTargetFieldArray() {
    $target_field_definition = 'field_main:-1:paragraph:clickstream_element/field_clickstream_item:1:paragraph:clickstream_image/field_media_img:1';

    // Execute the method to test.
    $result = $this->administrationHelper
      ->getStructuredBulkcreateTargetFieldArray(
        'bulkcreate_usage',
        'the_bundle',
        $target_field_definition
      );

    $this->assertTrue(is_array($result), 'The return value is not an array.');

    $this->assertCount(3, $result, 'The number of returned structures is wrong.');

    // Assertions for the first structure returned.
    $this->assertAttributeEquals(
      'field_main',
      'fieldname',
      $result[0],
      'The fieldname of the first structure returned is wrong.'
    );
    $this->assertAttributeEquals(
      -1,
      'cardinality',
      $result[0],
      'The cardinality of the first structure returned is wrong.'
    );
    $this->assertAttributeEquals(
      'paragraph',
      'target_entity_type_id',
      $result[0],
      'The target_entity_type_id of the first structure returned is wrong.'
    );
    $this->assertAttributeEquals(
      'clickstream_element',
      'target_bundle',
      $result[0],
      'The target_bundle of the first structure returned is wrong.'
    );

    // Assertions for the second structure returned.
    $this->assertAttributeEquals(
      'field_clickstream_item',
      'fieldname',
      $result[1],
      'The fieldname of the second structure returned is wrong.'
    );
    $this->assertAttributeEquals(
      1,
      'cardinality',
      $result[1],
      'The cardinality of the second structure returned is wrong.'
    );
    $this->assertAttributeEquals(
      'paragraph',
      'target_entity_type_id',
      $result[1],
      'The target_entity_type_id of the second structure returned is wrong.'
    );
    $this->assertAttributeEquals(
      'clickstream_image',
      'target_bundle',
      $result[1],
      'The target_bundle of the second structure returned is wrong.'
    );

    // Assertions for the third structure returned.
    $this->assertAttributeEquals(
      'field_media_img',
      'fieldname',
      $result[2],
      'The fieldname of the third structure returned is wrong.'
    );
    $this->assertAttributeEquals(
      1,
      'cardinality',
      $result[2],
      'The cardinality of the third structure returned is wrong.'
    );
  }

  /**
   * Tests the administrationHelper's getBulkcreateUsagesForForm method.
   */
  public function testGetBulkcreateUsagesForForm() {
    $bulkcreate_usage1 = $this->prophesize(BulkcreateUsageInterface::class);
    $bulkcreate_usage1->get('entity_type_id')->willReturn('bulkcreate_usage');
    $bulkcreate_usage1->get('bundle')->willReturn('a_bundle');

    $entity = $this->prophesize(EntityInterface::class);
    $entity->bundle()->willReturn('a_bundle');

    $entity_form = $this->prophesize(EntityFormInterface::class);
    $entity_form->getEntity()->willReturn($entity->reveal());

    $form = [];
    $form_state = $this->prophesize(FormStateInterface::class);
    $form_state->getBuildInfo()->willReturn([
      'form_id' => 'bulkcreate_usage_form',
      'base_form_id' => 'base_form',
      'callback_object' => $entity_form->reveal(),
    ]);

    $this->entityStorageLoadMultiple($this->entityTypeManager, 'bulkcreate_usage', [$bulkcreate_usage1]);

    // Execute the method to test.
    $result = $this->administrationHelper->getBulkcreateUsagesForForm(
      $form,
      $form_state->reveal()
    );

    $this->assertTrue(is_array($result), 'The result returned is not an array.');
    $this->assertArrayEquals([$bulkcreate_usage1->reveal()], $result, 'The array returned is not correct.');
  }

  /**
   * Tests the administrationHelper's getBulkcreateConfigurationOptions method.
   */
  public function testGetBulkcreateConfigurationOptions() {
    $bulkcreate_configuration1 = $this->prophesize(BulkcreateConfigurationInterface::class);
    $bulkcreate_configuration1->label()->willReturn('the label text');

    $bulkcreate_configuration2 = $this->prophesize(BulkcreateConfigurationInterface::class);
    $bulkcreate_configuration2->label()->willReturn('another text');

    // The expected result value.
    $expected_result = [
      'the_id' => $bulkcreate_configuration1->reveal(),
      'another_id' => $bulkcreate_configuration2->reveal(),
    ];

    // Tell the entity storage mock object to return the expected result.
    $this->entityStorageLoadMultiple(
      $this->entityTypeManager,
      'bulkcreate_configuration',
      $expected_result
    );

    // Execute the method to test.
    $result = $this->administrationHelper->getBulkcreateConfigurationOptions();

    $this->assertTrue(is_array($result), 'The result returned is not an array.');

    $this->assertArrayEquals([
      'the_id' => 'the label text',
      'another_id' => 'another text',
    ],
      $result,
      'The array returned is not correct.'
    );

  }

}
