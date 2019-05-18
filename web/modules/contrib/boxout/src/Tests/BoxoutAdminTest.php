<?php

namespace Drupal\boxout\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests administration of Boxout.
 *
 * @group boxout
 */
class BoxoutAdminTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'filter', 'editor', 'boxout');

  /**
   * A user with the 'administer filters' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A privileged user with additional access to the 'full_html' format.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $privilegedUser;

  /**
   * Sets up environment for running tests.
   */
  protected function setUp() {
    parent::setUp();

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser(array('administer filters'), 'Boxout Admin', TRUE);

    // Create article node type.
    $this->drupalCreateContentType(array(
      'type' => 'article',
      'name' => 'Article',
    ));

    // Create text format.
    $filtered_html_format = FilterFormat::create(array(
      'format' => 'boxout_filter',
      'name' => 'Boxout Filter',
      'weight' => 0,
      'roles' => $this->adminUser->getRoles(),
      'filters' => array(
        'filter_html' => array(
          'status' => 1,
          'settings' => array('allowed_html' => '<h2> <p> <div class="boxout default plain">'),
        ),
      ),
    ));
    $filtered_html_format->save();

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/formats/manage/boxout_filter');

    // Set editor.
    $edit = array(
      'editor[editor]' => 'ckeditor',
    );
    $this->drupalPostAjaxForm(NULL, $edit, 'editor_configure');

    // Set buttons.
    $buttons = array(
      array(
        array(
          'name' => 'Tools',
          'items' => array(
            'Boxout',
          ),
        ),
      ),
    );
    $edit['editor[settings][toolbar][button_groups]'] = json_encode($buttons);

    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
  }

  /**
   * Test article creation.
   */
  function testBoxoutArticle() {
    $this->drupalLogin($this->adminUser);

    // Test boxout form callback.
    $this->drupalGet('boxout/dialog');
    $this->assertResponse(200, "Boxout Dialog works");

    $this->xpath('//select[@id=:id]/option', array(':id' => 'boxout-style'));
    $this->assertOption('boxout-style', 'default');
    $this->assertOption('boxout-style', 'plain');

    $this->xpath('//select[@name=:id]/option', array(':id' => 'boxout-element-type'));
    $this->assertOption('boxout-element-type', 'p');
    $this->assertOption('boxout-element-type', 'h2');
    $this->assertOption('boxout-element-type', 'h3');
    $this->assertOption('boxout-element-type', 'h4');
    $this->assertOption('boxout-element-type', 'h5');

    $field_header = $this->xpath('//input[@name="attributes[header]"]');
    $this->assertTrue(count($field_header) === 1, 'Header field is present');

    $field_body = $this->xpath('//textarea[@name="attributes[body]"]');
    $this->assertTrue(count($field_body) === 1, 'Body field is present');

    // Add an article.
    $this->drupalGet('node/add/article');

    // Test editor config.
    $editor_settings = $this->getDrupalSettings()['editor']['formats']['boxout_filter']['editorSettings'];
    $this->assertTrue($editor_settings['allowedContent']['div']['classes'] = 'boxout,default,plain');
    $this->assertTrue($editor_settings['boxout_dialog_title_insert'] = 'Insert Boxout');
    $this->assertTrue($editor_settings['toolbar'][0]['items'][0] = 'Boxout');

    // Set content.
    $markup = '<div class="boxout default"><h2>Title</h2><p>Content</p></div>';
    $edit = array();
    $edit['title[0][value]'] = 'Boxout test node ' . $this->randomMachineName(10);
    $edit['body[0][value]'] = $markup;
    $this->drupalPostForm('node/add/article', $edit, t('Save'));

    // Check markup.
    $this->assertRaw($markup);
  }

}
