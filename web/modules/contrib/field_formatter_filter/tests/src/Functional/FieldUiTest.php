<?php

namespace Drupal\Tests\field_formatter_filter\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests applying the filter formatter to a node.
 *
 * Exercise the field UI method of configuring the formatter settings.
 *
 * @group field_formatter_filter
 */
class FieldUiTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * Dependencies should be enabled automatically.
   *
   * @var string[]
   */
  public static $modules = [
    'node',
    'field_ui',
    'field_formatter_filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Need to mock more bootstrap stuff.
    $this->createTextFormats();
  }

  /**
   * Tests creating and configuring a bundle.
   *
   * Just an internal self-test here.
   */
  public function testCreateContentType() {
    $this->createContentType(['type' => 'fff_article']);
  }

  /**
   * Test enabling the filter formatter. Check before, during and after.
   */
  public function testConfiguringFilter() {
    $entity_type = 'node';
    $bundle = 'fff_article';
    $view_mode = 'default';

    $this->createContentType(['type' => $bundle]);
    $node = $this->createTestNode($bundle);

    // Verify that rendering the page initially shows unwanted text.
    // We use the browser page fetch to look at the page.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains('the real content of the body text');
    $this->assertSession()->responseContains('<li>troublesome lists</li>');

    // Now enable the module.
    $this->container->get('module_installer')
      ->install(['field_formatter_filter'], TRUE);
    // Log in as a content manager.
    $permissions = [
      'access administration pages',
      'administer site configuration',
      "access content",
      "create $bundle content",

    ];
    $account = $this->createUser($permissions, 'manager', TRUE);
    $this->drupalLogin($account);

    // Open, edit and re-save the field UI, then
    // Re-check that all is well, issue #2868519 implies leaving it unconfigured
    // may damage normal display.
    $this->drupalGet("/admin/structure/types/manage/${bundle}/display");
    // To edit the field formatter settings, can do it by submitting the
    // fields 'cog' button. This is js/no-js safe.
    $edit = [];
    // Take care of these button identifiers. Unsure how volatile they are.
    $this->submitForm($edit, 'body_settings_edit');
    // At this point we should be seeing the field UI edit form with our
    // widget options displayed open.
    $this->assertSession()->pageTextContains(t('Additional Text Filter/Format'));
    // And the default current value of it should be '<none>'
    // The SELECT actually has that as '0' - which is part of our problem.
    # $this->assertSession()->fieldValueEquals(t('Additional Text Filter/Format'), '0');
    // Save without deliberately making any change.
    $this->submitForm($edit, 'body_plugin_settings_update');
    // OMG, I forgot this annoying extra step even when automating!
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains(t('Your settings have been saved.'));

    // After that field UI update, we ensure the the node display
    // has not been corrupted as reported in #2868519.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains('the real content of the body text');
    $this->assertSession()->responseContains('<li>troublesome lists</li>');

    // Proceed with the proper configurations.
    // Return to the field UI, choose our option, and check results again.
    $this->drupalGet("/admin/structure/types/manage/${bundle}/display");
    $this->submitForm($edit, 'body_settings_edit');
    // Retrieving the element is the way to set it. Its ID is ridiculous.
    $select = $this->assertSession()->selectExists(t('Additional Text Filter/Format'));
    // fields[body][settings_edit_form][third_party_settings][field_formatter_filter][format]
    $select->selectOption('teaser_safe_text');
    $this->submitForm($edit, 'body_plugin_settings_update');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains(t('Your settings have been saved.'));

    // Now go and see the results.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains('the real content of the body text');
    // Markup should be stripped now!
    $this->assertSession()->responseNotContains('<li>troublesome lists</li>');
  }

  /**
   * Create our test content type.
   *
   * Extend ContentTypeCreationTrait.
   * I'm not sure, but it seems this is not expected to be a thing we use
   * in Kernel tests - is it  based around assuming a simpletest environment?
   *
   * @inheritdoc
   */
  public function createContentType(array $values = []) {
    // From ContentTypeCreationTrait.
    $content_type = $this->drupalCreateContentType($values);
    // To avoid too many dependencies,
    // -- just toggle off the 'display user' for view modes.
    $content_type->set('display_submitted', FALSE);
    $content_type->save();
  }

  /**
   * Create a node with sample content.
   *
   * @param $bundle
   * @return \Drupal\Core\Entity\EntityInterface
   */
  private function createTestNode($bundle) {
    // Sample markup is in an external file - just to keep HTML out of code.
    $path = __DIR__ . '/../..';
    $body = file_get_contents($path . '/sample-markup.txt');
    $settings = [
      'type' => $bundle,
      'title' => 'Test this is filtered',
      'uid' => 1,
      'body' => ['value' => $body, 'format' => 'full_html'],
    ];
    return $this->createNode($settings);
  }

  /**
   * We need to set up two text formats.
   */
  private function createTextFormats() {
    // Add a text format with no restrictions.
    $format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
    ]);
    $format->save();

    // Add another text format with very limited markup.
    $format = FilterFormat::create([
      'format' => 'teaser_safe_text',
      'name' => 'Teaser safe text',
    ]);
    $format->setFilterConfig('filter_html', [
      'status' => 1,
      'settings' => [
        'allowed_html' => '<p> <br> <a href hreflang> <em> <strong>',
      ],
    ]);
    $format->save();
  }

}
