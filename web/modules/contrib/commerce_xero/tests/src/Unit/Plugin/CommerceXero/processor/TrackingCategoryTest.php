<?php

namespace Drupal\Tests\commerce_xero\Unit\Plugin\CommerceXero\processor;

use Drupal\commerce_xero\Plugin\CommerceXero\processor\TrackingCategory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\xero\Unit\XeroDataTestTrait;
use Drupal\xero\TypedData\Definition\TrackingCategoryDefinition;
use Drupal\xero\TypedData\Definition\TrackingCategoryOptionDefinition;

/**
 * Tests the commerce_xero_tracking_category processor plugin.
 *
 * @group commerce_xero
 */
class TrackingCategoryTest extends UnitTestCase {

  use XeroDataTestTrait;

  /**
   * The tracking category plugin.
   *
   * @var \Drupal\commerce_xero\Plugin\CommerceXero\processor\TrackingCategory
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $configuration = [
      'id' => 'commerce_xero_tracking_category',
      'settings' => [
        'tracking_category' => '',
        'tracking_option' => '',
      ],
    ];
    $definition = [
      'id' => 'commere_xero_tracking_category',
      'label' => 'Tracking Category',
      'types' => ['xero_bank_transaction'],
      'settings' => [],
      'execution' => 'immediate',
      'required' => FALSE,
      'class' => '\Drupal\commerce_xero\Plugin\CommerceXero\processor\TrackingCategory',
    ];

    $this->createTypedDataProphet();

    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $this->typedDataManagerProphet->reveal());
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $itemDefinition = new TrackingCategoryDefinition();
    $itemDefinition->setClass('\Drupal\xero\Plugin\DataType\TrackingCategory');
    $itemDefinition->setDataType('xero_tracking');

    $optionDefinition = new TrackingCategoryOptionDefinition();
    $optionDefinition->setClass('\Drupal\xero\Plugin\DataType\TrackingCategoryOption');
    $optionDefinition->setDataType('xero_tracking_category_option');

    $values = [
      [
        'Name' => 'Region',
        'Status' => 'ACTIVE',
        'TrackingCategoryID' => '351953c4-8127-4009-88c3-f9cd8c9cbe9f',
        'Options' => [
          [
            'TrackingOptionID' => '161ad543-97ab-4436-8213-e0d794b1ea90',
            'Name' => 'West Coast',
            'Status' => 'ACTIVE',
          ],
        ],
      ],
    ];

    $this->mockTypedData('list', $values[0]['Options'], 'Options', $optionDefinition);
    $this->mockTypedData('list', $values, NULL, $itemDefinition);

    // Sets the typed data manager into the container once more. DrupalWTF.
    $typedDataManager = $this->typedDataManagerProphet->reveal();
    $container->set('typed_data_manager', $typedDataManager);
    \Drupal::setContainer($container);

    $listDefinition = $typedDataManager->createListDataDefinition('xero_tracking');
    $categories = $typedDataManager->create($listDefinition, $values);
    $categories->appendItem($values[0]);

    $queryProphet = $this->prophesize('\Drupal\xero\XeroQuery');
    $queryProphet
      ->getCache('xero_tracking')
      ->willReturn($categories);

    // Finally adds xero query to the container and sets it a final time.
    // DrupalWTF.
    $container->set('xero.query', $queryProphet->reveal());
    \Drupal::setContainer($container);

    // Use the static method so that its covered.
    $this->plugin = TrackingCategory::create(
      $container,
      $configuration,
      'commerce_xero_tracking',
      $definition);

    $this->assertInstanceOf('\Drupal\commerce_xero\Plugin\CommerceXero\processor\TrackingCategory', $this->plugin);
  }

  /**
   * Asserts that the plugin has a configuration form.
   */
  public function testSettingsForm() {
    $formStateProphet = $this->prophesize('\Drupal\Core\Form\FormStateInterface');
    $formState = $formStateProphet->reveal();
    $form = $this->plugin->settingsForm([], $formState);

    $this->assertCount(1, $form['tracking_category']['#options']);
  }

}
