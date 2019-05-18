<?php

namespace Drupal\apptiles\Tests;

use Drupal\Tests\BrowserTestBase;

/**
 * Base abstraction for testing the "Application Tiles" module.
 */
abstract class ApplicationTilesTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['apptiles'];
  /**
   * {@inheritdoc}
   *
   * Needed to not validate "system.theme.global" schema.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   */
  protected function setUp() {
    parent::setUp();

    $this->container
      ->get('theme_installer')
      ->install(['apptiles_test_theme']);

    $this
      ->config('system.theme')
      ->set('default', 'apptiles_test_theme')
      ->save();
  }

  /**
   * Check meta tags on page.
   *
   * @param bool $exists
   *   Should the metatags be on the page?
   */
  protected function checkMetatags($exists = TRUE) {
    $tiles = apptiles()->getUrls();
    // Cannot continue without files.
    $this->assertFalse(empty($tiles), 'Tiles were found!');

    // No need to check tiles for Windows because they
    // are configured inside of browserconfig.xml.
    unset($tiles['windows']);

    $html = $this
      ->getSession()
      ->getPage()
      ->getHtml();

    // Walk through existing tiles only.
    foreach ($tiles as $os => $dimensions) {
      foreach ($dimensions as $dimension => $url) {
        $this->assertTrue(
          (strpos($html, file_url_transform_relative($url)) !== FALSE) === $exists,
          sprintf('Metatag for "%s" with "%s" dimension%sexists on the page.', $os, $dimension, $exists ? ' ' : ' not ')
        );
      }
    }
  }

  /**
   * Recursive comparison of values of tails setting.
   *
   * @param array $settings
   *   An associative array, full representation of XML structure (nested).
   * @param \SimpleXMLElement $element
   *   Complete XML file with settings.
   */
  protected function recursiveSettingsAssertion(array $settings, \SimpleXMLElement $element) {
    foreach ($settings as $key => $value) {
      if (is_array($value)) {
        // R-r-recursive.
        call_user_func(__METHOD__, $settings[$key], $element->{$key});
      }
      else {
        // WARNING: do not use "assertEquals()" since it'll be trying to
        // serialize the "SimpleXMLElement" and result in uncaught exception:
        // Serialization of 'SimpleXMLElement' is not allowed.
        if (isset($element->{$key})) {
          // Assert child properties.
          $this->assertTrue($settings[$key] == $element->{$key}, sprintf('Value of "%s" is "%s".', $key, $settings[$key]));
        }
        else {
          // Assert attributes.
          $attribute = $element->attributes()->{$key};
          $this->assertTrue($attribute == $value, sprintf('Attribute "%s" is "%s".', $attribute, $value));
        }
      }
    }
  }

}
