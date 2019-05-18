<?php

namespace Drupal\Tests\amp\Functional;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\filter\Entity\FilterFormat;

/**
 * Base AMP testing setup.
 */
abstract class AmpTestBase extends BrowserTestBase {

  use ContentTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'amp',
    'node',
    'contextual',
    'field_ui',
    'quickedit',
    'filter',
  ];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'access administration pages',
    'access in-place editing',
    'access content overview',
    'view all revisions',
    'administer content types',
    'administer display modes',
    'administer node fields',
    'administer node form display',
    'administer node display',
    'administer site configuration',
    'administer filters',
    'bypass node access',
    'use text format full_html',
  ];

  /**
   * An user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    parent::setUp();

    // Set up full html filter.
    $full_html_format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => [],
    ]);
    $full_html_format->save();
    user_role_grant_permissions('authenticated', [$full_html_format->getPermissionName()]);

    // Install the theme.
    // @see https://www.drupal.org/node/2232651
    $this->container->get('theme_installer')->install(['bartik', 'seven', 'ampsubtheme_example']);
    $this->container->get('config.factory')
      ->getEditable('system.theme')
      ->set('default', 'bartik')
      ->set('admin', 'seven')
      ->save();

    // Create Article node type.
    $this->createContentType([
      'type' => 'article',
      'name' => 'Article'
    ]);

    // Login as an admin user.
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);
    $this->assertTrue($full_html_format->access('use', $this->adminUser), 'Admin user may use permission: ' . $full_html_format->getPermissionName());

    // Configure AMP.
    $settings_url = Url::fromRoute("amp.settings")->toString();
    $this->drupalGet($settings_url);
    $edit = ['amptheme' => 'ampsubtheme_example'];
    $this->submitForm($edit, t('Save configuration'));

    // Enable AMP display on article content.
    $article_url = Url::fromRoute("entity.entity_view_display.node.default", ['node_type' => 'article'])->toString();
    $this->drupalGet($article_url);
    $this->assertSession()->statusCodeEquals(200);
    $edit = ['display_modes_custom[amp]' => 'amp'];
    $this->submitForm($edit, t('Save'));

    // Configure AMP field formatters.
    $amp_edit = Url::fromRoute('entity.node_type.edit_form', ['node_type' => 'article'])->toString();
    $this->drupalGet($amp_edit . '/display/amp');
    $this->assertSession()->statusCodeEquals(200);
    $edit = ["fields[field_image][type]" => 'amp_image'];
    $edit = ["fields[body][type]" => 'amp_text'];
    $this->submitForm($edit, t('Save'));

  }

}
