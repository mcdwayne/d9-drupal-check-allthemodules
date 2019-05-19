<?php

namespace Drupal\Tests\twig_extensions\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\views\Entity\View;

/**
 * Tests Twig Extensions admin functionality.
 *
 * @group twig_extensions
 */
class TwigExtensionsAdminTest extends BrowserTestBase {

  /**
   * An administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['twig_extensions'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user with administrator role.
    $this->adminUser = $this->drupalCreateUser([], NULL, TRUE);
  }

  /**
   * Tests content admin page.
   */
  public function testContentAdminPage() {
    /** @var \Drupal\views\Entity\View $view */
    $view = View::load('content');
    $display =& $view->getDisplay('default');
    $display['display_options']['pager'] = [
      'type' => 'mini',
      'options' => [
        'items_per_page' => 1,
        'tags' => [
          'previous' => '‹‹',
          'next'  => '››',
        ],
      ],
    ];
    $view->save();

    // Create two nodes to get pager.
    $this->drupalCreateNode(['type' => 'article']);
    $this->drupalCreateNode(['type' => 'article']);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/content');
    $this->assertSession()->statusCodeEquals(200);
  }

}
