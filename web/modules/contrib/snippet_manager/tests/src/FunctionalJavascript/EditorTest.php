<?php

namespace Drupal\Tests\snippet_manager\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests the snippet editor.
 *
 * @group snippet_manager
 */
class EditorTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected $minkDefaultDriverClass = DrupalSelenium2Driver::class;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['snippet_manager_test', 'file'];

  /**
   * Test callback.
   */
  public function testEditor() {

    $admin_user = $this->drupalCreateUser([
      'administer snippets',
      'use text format snippet_manager_test_basic_format',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/structure/snippet/alpha/edit/template');

    $this->editorSetValue('');

    // -- Test main buttons.
    $this->editorClickButton('bold');
    $this->editorClickButton('italic');
    $this->editorClickButton('underline');
    $this->editorClickButton('strike-through');
    $this->editorClickButton('list-numbered');
    $this->editorClickButton('list-bullet');
    $this->editorClickButton('horizontal-rule');

    $expected_output = "<hr/><ul>\n  <li></li>\n</ul>\n<ol>\n  <li></li>\n</ol>\n<s></s><u></u><em></em><strong></strong>";
    $this->assertEquals($expected_output, $this->editorGetValue());

    // -- Make sure it works with respect to selection.
    $this->editorSetValue('Test');
    $this->editorSetSelection([0, 0], [0, 4]);
    $this->editorClickButton('bold');

    $this->assertEquals('<strong>Test</strong>', $this->editorGetValue());

    // -- Test 'undo' button.
    $this->editorClickButton('undo');

    $this->assertEquals('Test', $this->editorGetValue());

    // -- Test 'redo' button.
    $this->editorClickButton('redo');
    $this->assertEquals('<strong>Test</strong>', $this->editorGetValue());

    // -- Test 'clear formatting' button.
    $this->editorSetSelection([0, 0], [0, 21]);
    $this->editorClickButton('clear-formatting');
    $this->assertEquals('Test', $this->editorGetValue());

    // -- Test 'enlarge' button.
    $this->editorClickButton('enlarge');
    $this->assertTrue($this->editorGetOption('fullScreen'));
    $this->assertNotVisible('//*[@data-cme-button = "enlarge"]');
    $this->assertVisible('//*[@data-cme-button = "shrink"]');

    // -- Test 'shrink' button.
    $this->editorClickButton('shrink');
    $this->assertFalse($this->editorGetOption('fullScreen'));
    $this->assertVisible('//*[@data-cme-button = "enlarge"]');
    $this->assertNotVisible('//*[@data-cme-button = "shrink"]');

    // -- Test 'mode' dropdown.
    $this->assertEquals('html_twig', $this->editorGetOption('mode'));
    $this->changeMode('text/html');
    $this->assertEquals('text/html', $this->editorGetOption('mode'));
    $this->changeMode('text/x-twig');
    $this->assertEquals('text/x-twig', $this->editorGetOption('mode'));
    $this->changeMode('text/javascript');
    $this->assertEquals('text/javascript', $this->editorGetOption('mode'));
    $this->changeMode('text/css');
    $this->assertEquals('text/css', $this->editorGetOption('mode'));

    // Reload the page and make sure the mode has been preserved.
    $this->drupalGet('admin/structure/snippet/alpha/edit/template');
    $this->assertSession()->elementExists('xpath', '//select[@class = "cme-mode"]/option');
    $selected_mode = $this->getSession()->getDriver()->evaluateScript('return jQuery(".cme-mode").val()');
    $this->assertEquals('text/css', $selected_mode);
    $this->assertEquals('text/css', $this->editorGetOption('mode'));

    // -- Test inserting of variables.
    $this->editorSetValue('');
    $this->click('//a[@data-drupal-selector="snippet-variable" and text() = "foo"]');
    $this->assertEquals('{{ foo }}', $this->editorGetValue());

    // -- Test CSS editor.
    $this->drupalGet('admin/structure/snippet/alpha/edit/css');
    $this->assertEquals('text/css', $this->editorGetOption('mode'));
    $this->assertCommonButtons();

    // -- Test JavaScript editor.
    $this->drupalGet('admin/structure/snippet/alpha/edit/js');
    $this->assertEquals('text/javascript', $this->editorGetOption('mode'));
    $this->assertCommonButtons();

    // -- Test snippet source page.
    $this->drupalGet('admin/structure/snippet/alpha/source');
    $this->assertEquals('text/html', $this->editorGetOption('mode'));
  }

  /**
   * Gets CodeMirror document value.
   */
  protected function editorGetValue() {
    $script = sprintf('return document.getElementsByClassName("CodeMirror")[0].CodeMirror.getValue();');
    return $this->getSession()
      ->getDriver()
      ->evaluateScript($script);
  }

  /**
   * Sets CodeMirror document value.
   */
  protected function editorSetValue($value) {
    $script = sprintf('document.getElementsByClassName("CodeMirror")[0].CodeMirror.setValue("%s");', $value);
    $this->getSession()
      ->getDriver()
      ->executeScript($script);
  }

  /**
   * Sets CodeMirror document selection.
   */
  protected function editorSetSelection($anchor, $head) {
    $script = 'document.getElementsByClassName("CodeMirror")[0].CodeMirror.setSelection({line: %d, ch: %d}, {line: %d, ch: %d});';
    $script = sprintf($script, $anchor[0], $anchor[1], $head[0], $head[1]);
    $this->getSession()
      ->getDriver()
      ->executeScript($script);
  }

  /**
   * Clicks specified editor toolbar button.
   */
  protected function editorClickButton($button) {
    $this->getSession()
      ->getPage()
      ->find('css', '[data-cme-button="' . $button . '"]')
      ->click();
  }

  /**
   * Gets CodeMirror option.
   */
  protected function editorGetOption($option) {
    $script = sprintf('document.getElementsByClassName("CodeMirror")[0].CodeMirror.getOption("%s");', $option);
    return $this->getSession()
      ->getDriver()
      ->evaluateScript($script);
  }

  /**
   * Asserts that the provided element is visible.
   */
  protected function assertVisible($xpath) {
    $is_visible = $this->getSession()
      ->getDriver()
      ->isVisible($xpath);
    $this->assertTrue($is_visible);
  }

  /**
   * Asserts that the provided element is not visible.
   */
  protected function assertNotVisible($xpath) {
    $is_visible = $this->getSession()
      ->getDriver()
      ->isVisible($xpath);
    $this->assertFalse($is_visible);
  }

  /**
   * Changes editor mode.
   */
  protected function changeMode($mode) {
    $this->getSession()
      ->getPage()
      ->find('css', '.cme-mode')
      ->selectOption($mode);
  }

  /**
   * Clicks button or link located by it's XPath query.
   */
  protected function click($xpath) {
    $this->getSession()->getDriver()->click($xpath);
  }

  /**
   * Asserts that common buttons exist.
   */
  protected function assertCommonButtons() {
    $prefix = '//div[@class = "cme-toolbar"]';
    $this->assertSession()->elementExists('xpath', $prefix . '/*[@data-cme-button="undo"]');
    $this->assertSession()->elementExists('xpath', $prefix . '/*[@data-cme-button="redo"]');
    $this->assertSession()->elementExists('xpath', $prefix . '/*[@data-cme-button="enlarge"]');
    $this->assertSession()->elementExists('xpath', $prefix . '/*[@data-cme-button="shrink"]');
  }

}
