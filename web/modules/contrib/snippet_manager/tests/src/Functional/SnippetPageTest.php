<?php

namespace Drupal\Tests\snippet_manager\Functional;

use Drupal\snippet_manager\Entity\Snippet;
use Drupal\user\Entity\Role;

/**
 * Snippet page test.
 *
 * @group snippet_manager
 */
class SnippetPageTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createContentType(['type' => 'page']);
    $this->drupalCreateNode(['title' => 'Foo', 'body' => 'foo_content']);
    \Drupal::service('theme_handler')->install(['bartik']);

    $values = [
      'id' => 'qux',
      'label' => 'Qux',
      'template' => [
        'value' => '<div class="qux-wrapper">{{ main }}</div>',
        'format' => 'snippet_manager_test_basic_format',
      ],
      'display_variant' => [
        'status' => TRUE,
        'admin_label' => NULL,
      ],
      'variables' => [
        'main' => [
          'plugin_id' => 'display_variant:main_content',
          'configuration' => [],
        ],
      ],
    ];
    Snippet::create($values)->save();
  }

  /**
   * Test callback.
   */
  public function testSnippetPages() {

    // -- Test default form appearance.
    $this->drupalGet($this->snippetEditUrl);

    $xpaths = [
      '//input[@name="page[status]" and not(@checked)]/next::label[text()="Enable snippet page"]',
      '//label[text()="Title"]/next::input[@name="page[title]"]',
      '//label[text()="Path"]/next::input[@name="page[path]"]',
    ];
    $this->assertXpaths($xpaths, '//fieldset[legend/span[text()="Page"]]');

    $theme_prefix = '//fieldset[legend/span[.="Theme"]]//input[@name="page[theme]"]/next::';
    $xpaths = [
      'label[text()="- Default -"]',
      'label[text()="Bartik"]',
    ];
    $this->assertXpaths($xpaths, $theme_prefix);

    $variant_prefix = '//fieldset[legend/span[.="Display variant"]]//input[@name="page[display_variant]"]/next::';
    $xpaths = [
      'label[text()="- Default -"]',
      'label[text()="Qux"]',
      'label[text()="Simple page"]',
    ];
    $this->assertXpaths($xpaths, $variant_prefix);

    $access_prefix = '//fieldset[legend/span[.="Access"]]//input[@name="page[access][type]"]/next::';
    $xpaths = [
      'label[text()="- Do not limit -"]',
      'label[text()="Permission"]',
      'label[text()="Role"]',
    ];
    $this->assertXpaths($xpaths, $access_prefix);

    $this->assertXpath('//label[text()="Permission"]/next::select/option[text()="- Select permission -"]');

    $role_prefix = '//fieldset[legend/span[.="Role"]]';
    $role = $this->loggedInUser->getRoles()[1];
    $xpaths = [
      '//input[@name="page[access][role][anonymous]"]/next::label[text()="Anonymous user"]',
      '//input[@name="page[access][role][authenticated]"]/next::label[text()="Authenticated user"]',
      sprintf('//input[@name="page[access][role][authenticated]"]/next::label[text()="Authenticated user"]', $role),
    ];
    $this->assertXpaths($xpaths, $role_prefix);

    $edit = [
      'page[status]' => TRUE,
      'page[path]' => 'zoo',
    ];
    $this->drupalPostForm($this->snippetEditUrl, $edit, 'Save');

    // -- Test page link on overview page.
    $this->drupalGet($this->snippetEditUrl);
    $this->drupalGet('admin/structure/snippet');
    $this->click(sprintf('//td[a[text()="%s"]]/following-sibling::td/a[text()="zoo"]', $this->snippetLabel));
    $this->assertSession()->addressEquals('zoo');
    $this->assertPageTitle($this->snippetLabel);
    $this->assertXpath('//div[@class="snippet-test" and text()="9"]');

    // -- Test page title.
    $edit = [
      'page[title]' => 'Bar',
    ];
    $this->drupalPostForm($this->snippetEditUrl, $edit, 'Save');
    $this->drupalGet('zoo');
    $this->assertPageTitle('Bar');

    // -- Test display variant.
    $edit = [
      'page[display_variant]' => 'snippet_display_variant:qux',
    ];
    $this->drupalPostForm($this->snippetEditUrl, $edit, 'Save');
    $this->drupalGet('zoo');
    $this->assertXpath('//div[@class="qux-wrapper"]/div[@class="snippet-test" and text()="9"]');

    // -- Test page theme.
    $edit = [
      'page[theme]' => 'bartik',
    ];
    $this->drupalPostForm($this->snippetEditUrl, $edit, 'Save');
    $this->drupalGet('zoo');
    $this->assertSession()->responseContains('core/themes/bartik/css/print.css');

    // -- Test path overriding.
    $edit = [
      'page[path]' => 'node/%node',
      'page[title]' => '',
      'page[display_variant]' => NULL,
    ];
    $this->drupalPostForm($this->snippetEditUrl, $edit, 'Save');
    $this->drupalGet('node/1');
    // The page title comes from the node.
    $this->assertPageTitle('Foo');
    $this->assertXpath('//div[@class="snippet-test" and text()="9"]');

    // Check if a node can be loaded from request.
    $edit = [
      'template[value]' => '<div class="node-body">{{ node.body[0] }}</div>',
    ];
    $this->drupalPostForm($this->snippetEditUrl . '/template', $edit, 'Save');
    $edit = [
      'plugin_id' => 'entity:node',
      'name' => 'node',
    ];
    $this->drupalPostForm($this->snippetEditUrl . '/variable/add', $edit, 'Save and continue');
    $edit = [
      'configuration[render_mode]' => 'fields',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->drupalGet('node/1');
    $this->assertXpath('//div[@class="node-body"]/p[text()="foo_content"]');

    // -- Test page access.
    $edit = [
      'page[title]' => 'Hi!',
      'page[path]' => 'zoo',
      'page[access][type]' => 'permission',
      'page[access][permission]' => 'access site reports',
    ];
    $this->drupalPostForm($this->snippetEditUrl, $edit, 'Save');

    $this->drupalGet('zoo');
    $this->assertSession()->statusCodeEquals(403);

    $this->grantPermissions(Role::load(Role::AUTHENTICATED_ID), ['access site reports']);
    $this->drupalGet('zoo');
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'page[access][type]' => 'role',
      'page[access][role][anonymous]' => TRUE,
    ];
    $this->drupalPostForm($this->snippetEditUrl, $edit, 'Save');
    $this->drupalGet('zoo');
    $this->assertSession()->statusCodeEquals(403);

    $edit = [
      'page[access][type]' => 'role',
      'page[access][role][authenticated]' => TRUE,
    ];
    $this->drupalPostForm($this->snippetEditUrl, $edit, 'Save');
    $this->drupalGet('zoo');
    $this->assertSession()->statusCodeEquals(200);
  }

}
