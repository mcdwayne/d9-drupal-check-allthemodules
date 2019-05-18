<?php

namespace Drupal\Tests\jqcloud\FunctionalJavascript;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait;

/**
 * Test the jQCloud JavaScript.
 *
 * @group jqcloud
 */
class JqcloudJavascriptTest extends JavascriptTestBase {

  use NodeCreationTrait;
  use ContentTypeCreationTrait;
  use TaxonomyTestTrait;
  use EntityReferenceTestTrait;

  const TERMS_COUNT = 100;

  /**
   * Drupal\taxonomy\Entity\Vocabulary definition.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * Drupal\node\Entity\NodeType definition.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * Entity reference field name in the node.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Term names in array.
   *
   * @var array
   */
  protected $termNames;

  /**
   * List of the created taxonomy terms.
   *
   * @var array
   */
  protected $terms;

  /**
   * List of the created nodes.
   *
   * @var array
   */
  protected $nodes;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'jqcloud',
    'jqcloud_library_test',
    'node',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create taxonomy vocabulary.
    $this->vocabulary = $this->createVocabulary();

    // Create node type.
    $this->nodeType = $this->drupalCreateContentType();

    // Create referenced field to the taxonomy vocabulary.
    $this->fieldName = Unicode::strtolower($this->randomMachineName());
    $handler_settings = [
      'target_bundles' => [
        $this->vocabulary->id() => $this->vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField(
      'node',
      $this->nodeType->id(),
      $this->fieldName,
      NULL,
      'taxonomy_term',
      'default',
      $handler_settings,
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );

    // Generate term names array.
    $this->termNames = [];
    do {
      for ($i = count($this->termNames); $i < self::TERMS_COUNT; $i++) {
        $this->termNames[] = Unicode::strtolower(
          $this->randomMachineName(rand(3, 8))
        );
      }

      $this->termNames = array_unique($this->termNames);
      $this->termNames = array_values($this->termNames);

      if (count($this->termNames) == self::TERMS_COUNT) {
        break;
      }
    } while (TRUE);

    // Generate taxonomy terms.
    foreach ($this->termNames as $term_name) {
      $term_values = [
        'name' => $term_name,
      ];

      $this->terms[] = $this->createTerm($this->vocabulary, $term_values);
    }

    // Create nodes with terms.
    for ($i = 0; $i < 100; $i++) {
      $node = [];
      $node['type'] = $this->nodeType->id();

      // Add terms.
      $terms = array_slice($this->terms, 0, $i + 1);

      /** @var \Drupal\taxonomy\TermInterface $term */
      foreach ($terms as $term) {
        $node[$this->fieldName][]['target_id'] = $term->id();
      }

      $this->nodes[] = $this->drupalCreateNode($node);
    }
  }

  /**
   * Tests that the terms was created.
   */
  public function testCheckTermsCreated() {
    foreach ($this->termNames as $term_name) {
      $terms = taxonomy_term_load_multiple_by_name(
        $term_name, $this->vocabulary->id()
      );

      $term = reset($terms);
      $this->assertTrue(
        isset($term) && $term instanceof TermInterface,
        "Checking {$term_name} term for exists."
      );
    }
  }

  /**
   * Tests that the jQCloud library was installed.
   */
  public function testLibraryExists() {
    // Checking js library.
    $js_library = \Drupal::service('library.discovery')->getLibraryByName(
      'jqcloud',
      'jqcloud'
    );

    $js_library_exists = file_exists(
      DRUPAL_ROOT . '/' . $js_library['js'][0]['data']
    );

    $this->assertTrue($js_library_exists, 'jQCloud JS library exists.');

    // Checking css library.
    $css_library = \Drupal::service('library.discovery')->getLibraryByName(
      'jqcloud',
      'jqcloud-styles'
    );

    $css_library_exists = file_exists(
      DRUPAL_ROOT . '/' . $css_library['css'][0]['data']
    );
    $this->assertTrue($css_library_exists, 'jQCloud CSS library exists.');
  }

  /**
   * Test that the jQCloud block is exist.
   */
  public function testBlockExists() {
    $admin_user = $this->drupalCreateUser([
      'administer blocks',
      'administer themes',
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);

    // Install admin theme and confirm that tab is accessible.
    \Drupal::service('theme_handler')->install(['bartik']);
    $edit['admin_theme'] = 'bartik';
    $this->drupalPostForm('/admin/appearance', $edit, t('Save configuration'));
    $this->drupalGet('/admin/structure/block/library/bartik');
    $this->assertSession()->statusCodeEquals(200);

    // Check jQCloud block exists.
    $this->assertSession()->responseContains(
      'jQCloud with "' . $this->vocabulary->get('name') . '" vocabulary'
    );
  }

  /**
   * Test that the jQCloud block has configuration form with default values.
   */
  public function testBlockEditForm() {
    $block_url = "jqcloud_block:{$this->vocabulary->id()}";

    // Create and login.
    $admin_user = $this->drupalCreateUser([
      'administer blocks',
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);

    // Checking page form.
    $this->drupalGet("/admin/structure/block/add/{$block_url}/bartik");
    $this->assertSession()->statusCodeEquals(200);

    // Checking fields.
    $result = $this->xpath('//input[@name="settings[label]"]');
    $assert_equals = 'jQCloud with "' . $this->vocabulary->get('name');
    $assert_equals .= '" vocabulary';
    $this->assertEquals(
      $result[0]->getValue(),
      $assert_equals,
      'The block title with right value was found.'
    );

    // Checking the "jQCloud settings" details form element.
    $result = $this->xpath('//details[@id="edit-settings-jqcloud" and @open]');
    $this->assertTrue(
      isset($result[0]),
      'The "jQCloud settings" details form element was found and open.'
    );
    $result = $this->xpath('//details[@id="edit-settings-jqcloud"]//summary');
    $this->assertEquals(
      t('jQCloud settings'),
      $result[0]->getText(),
      'The "jQCloud settings" details form element has right summary.'
    );

    // Checking "Number of terms to display" form element.
    $result = $this->xpath('//*[@id="edit-settings-jqcloud"]/div/div[1]');
    $this->assertTrue(
      isset($result[0]),
      'The "Number of terms to display" form element was found.'
    );
    $result = $this->xpath('//*[@id="edit-settings-jqcloud"]/div/div[1]/label');
    $this->assertEquals(
      t('Number of terms to display'),
      $result[0]->getText(),
      'The "Number of terms to display" label has right text.'
    );
    $result = $this->xpath('//input[@id="edit-settings-jqcloud-terms-count"]');
    $this->assertEquals(
      40,
      $result[0]->getValue(),
      'The "Number of terms to display" form element has right default value.'
    );
    $result = $this->xpath(
      '//*[@id="edit-settings-jqcloud-terms-count--description"]'
    );
    $this->assertEquals(
      t('Set "-1" value for display unlimited terms.'),
      $result[0]->getText(),
      'The "Number of terms to display" form element has right description.'
    );

    // Checking "Terms block height" form element.
    $result = $this->xpath('//*[@id="edit-settings-jqcloud"]/div/div[2]');
    $this->assertTrue(
      isset($result[0]),
      'The "Terms block height" form element was found.'
    );
    $result = $this->xpath('//*[@id="edit-settings-jqcloud"]/div/div[2]/label');
    $this->assertEquals(
      t('Terms block height'),
      $result[0]->getText(),
      'The "Terms block height" label has right text.'
    );
    $result = $this->xpath('//input[@id="edit-settings-jqcloud-height"]');
    $this->assertEquals(
      250,
      $result[0]->getValue(),
      'The "Terms block height" form element has right default value.'
    );

    // Checking "Link to the term page" form element.
    $result = $this->xpath('//*[@id="edit-settings-jqcloud"]/div/div[3]');
    $this->assertTrue(
      isset($result[0]),
      'The "Link to the term page" form element was found.'
    );
    $result = $this->xpath('//*[@id="edit-settings-jqcloud"]/div/div[3]/label');
    $this->assertEquals(
      t('Link to the term page'),
      $result[0]->getText(),
      'The "Link to the term page" label has right text.'
    );
    $result = $this->xpath('//input[@id="edit-settings-jqcloud-link-to-term"]');
    $this->assertTrue(
      $result[0]->getAttribute('checked') == FALSE,
      'The "Link to the term page" form element is not checked.'
    );

    // Checking "Style" form element.
    $result = $this->xpath('//*[@id="edit-settings-jqcloud"]/div/div[4]');
    $this->assertTrue(isset($result[0]), 'The "Style" form element was found.');
    $result = $this->xpath('//*[@id="edit-settings-jqcloud"]/div/div[4]/label');
    $this->assertEquals(
      t('Style'),
      $result[0]->getText(),
      'The "Style" label has right text.'
    );
    $result = $this->xpath('//select[@id="edit-settings-jqcloud-style"]');
    $this->assertTrue(
      isset($result[0]),
      'The "Style" select form element was found.'
    );
    $result = $this->xpath(
      '//select[@id="edit-settings-jqcloud-style"]/option'
    );
    $this->assertTrue(
      count($result) == 3,
      'The "style" select form element has 3 options.'
    );
    $this->assertEquals(
      'default',
      $result[0]->getValue(),
      'The "default" option in the "style" select was found.'
    );
    $this->assertEquals(
      t('Default jQCloud styles'),
      $result[0]->getText(),
      'The "default" option in the "style" has right text.'
    );
    $this->assertTrue(
      $result[0]->getAttribute('selected') == 'selected',
      'The "default" option in the "style" is selected.'
    );
    $this->assertEquals(
      'none',
      $result[1]->getValue(),
      'The "none" option in the "style" select was found.'
    );
    $this->assertEquals(
      t('Without styling'),
      $result[1]->getText(),
      'The "none" option in the "style" has right text.'
    );
    $this->assertEquals(
      'custom_colors',
      $result[2]->getValue(),
      'The "custom_colors" option in the "style" select was found.'
    );
    $this->assertEquals(
      t('With custom colors'),
      $result[2]->getText(),
      'The "custom_colors" option in the "style" has right text.'
    );

    // Checking "Other settings" details form element.
    $result = $this->xpath(
      '//details[@id="edit-settings-jqcloud-other-settings"]'
    );
    $this->assertTrue(
      isset($result[0]),
      'The "Other settings" details form element was found.'
    );
    $result = $this->xpath(
      '//details[@id="edit-settings-jqcloud-other-settings"]//summary'
    );
    $this->assertEquals(
      t('Other settings'),
      $result[0]->getText(),
      'The "Other settings" details form element has right summary.'
    );

    // Checking "Auto resize" form element.
    $result = $this->xpath(
      '//*[@id="edit-settings-jqcloud-other-settings"]/div/div[1]'
    );
    $this->assertTrue(
      isset($result[0]),
      'The "Auto resize" form element was found.'
    );
    $result = $this->xpath(
      '//*[@id="edit-settings-jqcloud-other-settings"]/div/div[1]/label'
    );
    $this->assertEquals(
      t('Auto resize'),
      $result[0]->getText(),
      'The "Auto resize" label has right text.'
    );
    $result = $this->xpath(
      '//*[@id="edit-settings-jqcloud-other-settings-auto-resize"]'
    );
    $this->assertTrue(
      $result[0]->getAttribute('checked') == TRUE,
      'The "Auto resize" form element is checked.'
    );

    // Checking "Shape" form element.
    $result = $this->xpath(
      '//*[@id="edit-settings-jqcloud-other-settings"]/div/div[2]'
    );
    $this->assertTrue(
      isset($result[0]),
      'The "Shape" form element was found.'
    );
    $result = $this->xpath(
      '//*[@id="edit-settings-jqcloud-other-settings"]/div/div[2]/label'
    );
    $this->assertEquals(
      t('Shape'),
      $result[0]->getText(),
      'The "Shape" label has right text.'
    );
    $result = $this->xpath(
      '//select[@id="edit-settings-jqcloud-other-settings-shape"]'
    );
    $this->assertTrue(
      isset($result[0]),
      'The "Shape" select form element was found.'
    );
    $result = $this->xpath(
      '//select[@id="edit-settings-jqcloud-other-settings-shape"]/option'
    );
    $this->assertTrue(
      count($result) == 2,
      'The "shape" select form element has 2 options.'
    );
    $this->assertEquals(
      'elliptic',
      $result[0]->getValue(),
      'The "elliptic" option in the "shape" select was found.'
    );
    $this->assertEquals(
      t('Elliptic'),
      $result[0]->getText(),
      'The "elliptic" option in the "shape" has right text.'
    );
    $this->assertTrue(
      $result[0]->getAttribute('selected') == 'selected',
      'The "elliptic" option in the "shape" is selected.'
    );
    $this->assertEquals(
      'rectangular',
      $result[1]->getValue(),
      'The "rectangular" option in the "shape" select was found.'
    );
    $this->assertEquals(
      t('Rectangular'),
      $result[1]->getText(),
      'The "rectangular" option in the "shape" has right text.'
    );

    // Checking "Delay" form element.
    $result = $this->xpath(
      '//*[@id="edit-settings-jqcloud-other-settings"]/div/div[3]'
    );
    $this->assertTrue(
      isset($result[0]),
      'The "Delay" form element was found.'
    );
    $result = $this->xpath(
      '//*[@id="edit-settings-jqcloud-other-settings"]/div/div[3]/label'
    );
    $this->assertEquals(
      t('Delay'),
      $result[0]->getText(),
      'The "Delay" label has right text.'
    );
    $result = $this->xpath(
      '//input[@id="edit-settings-jqcloud-other-settings-delay"]'
    );
    $this->assertEquals(
      0,
      $result[0]->getValue(),
      'The "Delay" form element has right default value.'
    );
    $result = $this->xpath(
      '//*[@id="edit-settings-jqcloud-other-settings-delay--description"]'
    );
    $this->assertEquals(
      t('Display terms in the jQCloud block with delay in milliseconds.'),
      $result[0]->getText(),
      'The "Delay" form element has right description.'
    );

    // @todo: Write code for checking custom_colors appears.
  }

  /**
   * Test that jQCloud block is visible after add to region.
   */
  public function testBlockAdd() {
    $admin_user = $this->drupalCreateUser([
      'administer blocks',
      'administer themes',
    ]);
    $this->drupalLogin($admin_user);

    // Install bartik theme as default.
    \Drupal::service('theme_handler')->install(['bartik']);
    $this->container->get('config.factory')
      ->getEditable('system.theme')
      ->set('default', 'bartik')
      ->save();
    $edit['admin_theme'] = 'bartik';

    // Go to block form.
    $block_url = "jqcloud_block:{$this->vocabulary->id()}";
    $this->drupalGet("/admin/structure/block/add/{$block_url}/bartik");
    $this->assertSession()->statusCodeEquals(200);

    // Changed region to 'Content' and save block.
    $edit = [];
    $edit['region'] = 'content';
    $this->drupalPostForm(
      "/admin/structure/block/add/{$block_url}/bartik",
      $edit,
      t('Save block')
    );
    $this->assertSession()->pageTextContains(
      t('The block configuration has been saved.')
    );

    // Check that block is enabled.
    $this->drupalGet('/admin/structure/block');
    $this->assertSession()->statusCodeEquals(200);

    // Go to front page and check the block.
    $this->drupalGet('<front>');
    $result = $this->xpath("//*[contains(@class, 'block block-jqcloud')]");
    $this->assertTrue(isset($result[0]), 'The jQCloud block found.');
    $result = $this->xpath(
      "//*[contains(@class, 'block block-jqcloud')]/div/div[contains(@class,
      'jqcloud-contents jqcloud')]"
    );
    $this->assertTrue(isset($result[0]), 'The jQCloud block contents found.');

    // Wait for .jqcloud-word elements.
    $condition = "jQuery('.jqcloud-contents span.jqcloud-word').length > 0";
    $this->assertJsCondition($condition, 50000);

    // Assert first 10 terms.
    for ($i = 0; $i < 10; $i++) {
      $term_name = $this->terms[$i]->getName();
      $this->assertSession()->pageTextContains($term_name);
    }
  }

}
