<?php

namespace Drupal\admin_menu\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Base class for all administration menu web test cases.
 */
abstract class AdminMenuTestBase extends WebTestBase {

  public static $modules = ['admin_menu'];

  protected $basePermissions = [
    'system' => 'access administration pages',
    'admin_menu' => 'access administration menu',
  ];

  function setUp() {
    parent::setUp();

    // @todo Core: Missing uid on $this->root_user.
    // @see http://drupal.org/node/1899862
    $this->root_user->uid = 1;

    // Disable client-side caching.
    variable_set('admin_menu_cache_client', FALSE);
  }

  /**
   * Check that an element exists in HTML markup.
   *
   * @param $xpath
   *   An XPath expression.
   * @param array $arguments
   *   (optional) An associative array of XPath replacement tokens to pass to
   *   DrupalWebTestCase::buildXPathQuery().
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   *
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertElementByXPath($xpath, array $arguments = [], $message, $group = 'Other') {
    $elements = $this->xpath($xpath, $arguments);
    return $this->assertTrue(!empty($elements[0]), $message, $group);
  }

  /**
   * Check that an element does not exist in HTML markup.
   *
   * @param $xpath
   *   An XPath expression.
   * @param array $arguments
   *   (optional) An associative array of XPath replacement tokens to pass to
   *   DrupalWebTestCase::buildXPathQuery().
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   *
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNoElementByXPath($xpath, array $arguments = [], $message, $group = 'Other') {
    $elements = $this->xpath($xpath, $arguments);
    return $this->assertTrue(empty($elements), $message, $group);
  }

  /**
   * Asserts that links appear in the menu in a specified trail.
   *
   * @param array $trail
   *   A list of menu link titles to assert in the menu.
   */
  protected function assertLinkTrailByTitle(array $trail) {
    $xpath = [];
    $args = [];
    $message = '';
    foreach ($trail as $i => $title) {
      $xpath[] = '/li/a[text()=:title' . $i . ']';
      $args[':title' . $i] = $title;
      $message .= ($i ? ' » ' : '') . check_plain($title);
    }
    $xpath = '//div[@id="admin-menu"]/div/ul' . implode('/parent::li/ul', $xpath);
    $this->assertElementByXPath($xpath, $args, $message . ' link found.');
  }

  /**
   * Asserts that no link appears in the menu for a specified trail.
   *
   * @param array $trail
   *   A list of menu link titles to assert in the menu.
   */
  protected function assertNoLinkTrailByTitle(array $trail) {
    $xpath = [];
    $args = [];
    $message = '';
    foreach ($trail as $i => $title) {
      $xpath[] = '/li/a[text()=:title' . $i . ']';
      $args[':title' . $i] = $title;
      $message .= ($i ? ' » ' : '') . check_plain($title);
    }
    $xpath = '//div[@id="admin-menu"]/div/ul' . implode('/parent::li/ul', $xpath);
    $this->assertNoElementByXPath($xpath, $args, $message . ' link not found.');
  }
}

