<?php

namespace Drupal\Tests\first_paragraph\Unit;


use Drupal\filter\Entity\FilterFormat;
use Drupal\system\Tests\Entity\EntityUnitTestBase;


/**
 * Tests the First Paragraph formatter functionality.
 *
 * @group text
 */
class FirstParagraphTest extends EntityUnitTestBase {


  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('first_paragraph');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    entity_create('filter_format', array(
      'format' => 'my_text_format',
      'name' => 'My text format',
      'filters' => array(
        'filter_autop' => array(
          'module' => 'filter',
          'status' => TRUE,
        ),
      ),
    ))->save();

    entity_create('field_storage_config', array(
      'field_name' => 'formatted_text',
      'entity_type' => $this->entityType,
      'type' => 'text',
      'settings' => array(),
    ))->save();
    entity_create('field_config', array(
      'entity_type' => $this->entityType,
      'bundle' => $this->bundle,
      'field_name' => 'formatted_text',
      'label' => 'Filtered text',
    ))->save();
  }

  /**
   * Tests all text field formatters.
   */
  public function testFormatters() {
    $strings = [
      "This is a first paragraph.\n\nThis is the second paragraph.\n\nThis is the third paragraph." => "<p>This is a first paragraph.</p>\n",
      "<p>First</p><p>Second</p>" => "<p>First</p>\n",
      "First\nSecond" => "<p>First<br><br />\nSecond</p>\n",
      'test' => "<p>test</p>\n",
    ];

    // Create the entity to be referenced.
    $entity = entity_create($this->entityType, array('name' => $this->randomMachineName()));

    foreach ($strings as $input => $output) {
      $entity->formatted_text = array(
        'value' => $input,
        'format' => 'my_text_format',
      );
      $entity->save();

      // Verify the text field formatter's render array.
      $build = $entity->get('formatted_text')
        ->view(['type' => 'text_first_para']);
      \Drupal::service('renderer')->renderRoot($build[0]);
      $this->assertEqual($build[0]['#markup'], $output);

      // Check the cache tags
      $this->assertEqual(
        $build[0]['#cache']['tags'],
        FilterFormat::load('my_text_format')->getCacheTags(),
        format_string('The @formatter formatter has the expected cache tags when formatting a formatted text field.', ['@formatter' => 'text_first_para'])
      );
    }
  }












  /**
   * Implements getInfo().
   */
  /*
  public static function getInfo() {
    return [
      'name' => t('First Paragraph tests'),
      'description' => t('Check that teasers have the right output..'),
      'group' => t('First Paragraph'),
    ];
  }
  */

   /**
   * Implements setUp().
   */
  /*
  public function setUp() {
    // Call the parent with an array of modules to enable for the test.
    parent::setUp([
      'first_paragraph'
      ]);

    $instance = field_info_instance('node', 'body', 'page');
    $instance['display']['teaser']['type'] = 'text_first_para';
    $instance['display']['teaser']['settings'] = [];
    $instance->save();
  }
  8/

  public /**
   * Test the module's functionality.
   */
  /*
  function testFirstParaEntity() {
    // Define test content/paragraphs.
    $paras = [
      'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam nec vulputate nibh.',
      'Morbi faucibus nunc feugiat nisi elementum, eu imperdiet nisl semper. Vivamus tincidunt ex magna.',
    ];

    // Create a test node with the above 2 paragraphs. Using AutoP and \n\n to
    // make the filter make 2 paragraphs.
    $settings = [
      'promote' => 1,
      'language' => \Drupal\Core\Language\Language::LANGCODE_NOT_SPECIFIED,
    ];
    $settings['body'][$settings['language']][0] = [
      'value' => implode("\n\n", $paras),
      'format' => 'filtered_html',
    ];
    $node = $this->drupalCreateNode($settings);

    // First test; the promotes nodes page. Should only have first para.
    $this->drupalGet('node');
    $this->assertText($paras[0]);
    $this->assertNoText($paras[1]);

    // Second test; the node page. Should have both para's.
    $this->drupalGet('node/' . $node->nid);
    $this->assertText($paras[0]);
    $this->assertText($paras[1]);

  }
  */

}
