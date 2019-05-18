<?php

/**
 * @file
 * Contains \Drupal\royalslider\Tests\Formatter\RoyalSliderFormatterTest.
 */

// namespace Drupal\royalslider\Tests\Formatter;

// //use Drupal\filter\Entity\FilterFormat;
// use Drupal\system\Tests\Entity\EntityUnitTestBase;

// /**
//  * Tests the royalslider formatter functionality.
//  *
//  * @group royalslider
//  */
// class RoyalSliderFormatterTest extends EntityUnitTestBase {

//   /**
//    * The entity type used in this test.
//    *
//    * @var string
//    */
//   protected $entityType = 'entity_test';

//   /**
//    * The bundle used in this test.
//    *
//    * @var string
//    */
//   protected $bundle = 'entity_test';

//   /**
//    * Modules to enable.
//    *
//    * @var array
//    */
//   public static $modules = array('royalslider', 'image');

//   /**
//    * {@inheritdoc}
//    */
//   protected function setUp() {
//     parent::setUp();

//     entity_create('field_storage_config', array(
//       'field_name' => 'slider_image',
//       'entity_type' => $this->entityType,
//       'type' => 'image',
//       'settings' => array(),
//     ))->save();
//   }

//   /**
//    * Tests all royalslider field formatters.
//    */
//   public function testFormatters() {
//     $formatters = array(
//       'royalslider',
//     );

//     // Create the entity to be referenced.
//     $entity = entity_create($this->entityType, array('name' => $this->randomMachineName()));
//     $entity->slider_image = array(
//       'value' => 'Hello, world!',
//     );
//     $entity->save();

//     foreach ($formatters as $formatter) {
//       // Verify the text field formatter's render array.
//       $build = $entity->get('formatted_text')->view(array('type' => $formatter));
//       \Drupal::service('renderer')->render($build[0]);
//       $this->assertEqual($build[0]['#markup'], "<p>Hello, world!</p>\n");
//       $this->assertEqual($build[0]['#cache']['tags'], FilterFormat::load('my_text_format')->getCacheTags(), format_string('The @formatter formatter has the expected cache tags when formatting a formatted text field.', array('@formatter' => $formatter)));
//     }
//   }

// }
// @TODO CHECK TelephoneFieldTest