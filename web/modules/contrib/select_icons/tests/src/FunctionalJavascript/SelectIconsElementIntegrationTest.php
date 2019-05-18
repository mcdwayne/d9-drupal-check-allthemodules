<?php

namespace Drupal\Tests\select_icons\FunctionalJavascript;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;


/**
 * Tests the JavaScript select_icons - jquery selectmenu widget.
 *
 * @group views_ui
 */
class SelectIconsElementIntegrationTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    //'system',
    //'block',
    'select_icons',
    'select_icons_test'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->enableTheme('seven');
    //$this->placeBlock('system_powered_by_block', ['id' => 'powered']);
  }

  public function testSelecIconsRenderedFormElement() {
    $this->drupalGet('select_icons_test/form_element');

    $page = $this->getSession()->getPage();

    // Check if selecticons select is hidden.
    $selecticons_select = $page->find('css', 'select#select-icons-test-element');
    $this->assertFalse($selecticons_select->isVisible(), 'Selectmenu select is hidden.');

    // Check if selecticons select has options with data attribute.
    $option_red = $page->find('css', 'select#select-icons-test-element option[value="r"]');
    $this->assertEquals('colour red', $option_red->getAttribute('data-class'));

    // Check if selectmenu is opened.
    $selecticons_widget = $page->find('css', '.js-form-type-select-icons .ui-selectmenu-button');
    $selecticons_widget->click();
    //$this->createScreenshot('public://test_screenshot.jpg');
    $selecticons_widget->find('css', 'ui-selectmenu-open');
    $this->assertTrue($selecticons_widget->isVisible(), 'Selectmenu is visible.');
  }

  /**
   * Enables a theme.
   *
   * @param string $theme
   *   The theme.
   */
  public function enableTheme($theme) {
    // Enable the theme.
    \Drupal::service('theme_installer')->install([$theme]);
    $theme_config = \Drupal::configFactory()->getEditable('system.theme');
    $theme_config->set('default', $theme);
    $theme_config->save();
  }

}
