<?php

namespace Drupal\Tests\snippet_manager\Functional;

use Drupal\Component\Utility\Crypt;

/**
 * Snippet CSS test.
 *
 * @group snippet_manager
 */
class SnippetCssTest extends TestBase {

  /**
   * Tests snippet CSS managing.
   */
  public function testCss() {

    // Check CSS form appearance.
    $this->drupalGet($this->snippetEditUrl . '/css');
    $this->assertPageTitle(t('Edit %label', ['%label' => $this->snippetLabel]));
    $this->assertXpath('//form//input[@type="checkbox" and @name="css[status]" and not(@checked)]/following::label[.="Enable"]');
    $this->assertXpath('//form//input[@type="checkbox" and @name="css[preprocess]" and @checked]/following::label[.="Preprocess"]');
    $this->assertXpath('//form//label[.="Group"]/following::select[@name="css[group]"]/option[@value="component" and @selected="selected"]');
    $this->assertXpath('//form//label[.="CSS"]/following::div/textarea[@name="css[value]"]');
    $this->assertXpath('//form//input[@type="submit" and @value="Save"]');

    $edit = [
      'css[status]' => TRUE,
      'css[value]' => '.test {color: pink}',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertStatusMessage(t('Snippet %label has been updated.', ['%label' => $this->snippetLabel]));

    // Make sure the form values have been updated.
    $this->assertXpath('//form//input[@name="css[status]" and @checked]');
    $this->assertXpath('//form//textarea[@name="css[value]" and .=".test {color: pink}"]');

    // Check if the CSS file has been created.
    $this->click('//form//a[.="Open file" and @class="button" and @target="_blank"]');
    $file_url = $this->getUrl();
    $snippet_id_hash = Crypt::hashBase64($this->snippetId);
    $this->assertRegExp(sprintf('#^http://.*/files/snippet/%s.css\?.{6}$#', $snippet_id_hash), $file_url);
    $this->assertSession()->responseContains('.test {color: pink}');

    // Check that the CSS file is attached to the snippet.
    $this->drupalGet($this->snippetUrl);
    $file_import_xpath = sprintf('//head/link[contains(@href, "/files/snippet/%s.css?")]', $snippet_id_hash);
    $this->assertXpath($file_import_xpath);

    // Disable CSS and make sure the file is removed.
    $edit = [
      'css[status]' => FALSE,
    ];
    $this->drupalPostForm($this->snippetEditUrl . '/css', $edit, 'Save');
    $this->assertSession()->linkNotExists('Open file');
    $this->drupalGet($file_url);
    $this->assertSession()->statusCodeEquals(404);
  }

}
