<?php

namespace Drupal\Tests\snippet_manager\Functional;

use Drupal\Component\Utility\Crypt;

/**
 * Snippet JS test.
 *
 * @group snippet_manager
 */
class SnippetJsTest extends TestBase {

  /**
   * Tests snippet JS managing.
   */
  public function testJs() {

    $snippet_id_hash = Crypt::hashBase64($this->snippetId);

    // Check JS form appearance.
    $this->drupalGet($this->snippetEditUrl . '/js');
    $this->assertPageTitle(t('Edit %label', ['%label' => $this->snippetLabel]));
    $this->assertXpath('//form//input[@type="checkbox" and @name="js[status]" and not(@checked)]/following::label[.="Enable"]');
    $this->assertXpath('//form//input[@type="checkbox" and @name="js[preprocess]" and @checked]/following::label[.="Preprocess"]');
    $this->assertXpath('//form//label[.="JavaScript"]/following::div/textarea[@name="js[value]"]');
    $this->assertXpath('//form//input[@type="submit" and @value="Save"]');

    $edit = [
      'js[status]' => TRUE,
      'js[preprocess]' => FALSE,
      'js[value]' => 'alert(123);',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertStatusMessage(t('Snippet %label has been updated.', ['%label' => $this->snippetLabel]));

    // Make sure the form values have been updated.
    $this->assertXpath('//form//input[@name="js[status]" and @checked]');
    $this->assertXpath('//form//input[@name="js[preprocess]" and not(@checked)]');
    $this->assertXpath('//form//textarea[@name="js[value]" and .="alert(123);"]');

    // Check if the JS file has been created.
    $this->click('//form//a[.="Open file" and @class="button" and @target="_blank"]');
    $file_url = $this->getUrl();
    $this->assertRegExp(sprintf('#^http://.*/files/snippet/%s.js\?.{6}$#', $snippet_id_hash), $file_url);
    $this->assertSession()->responseContains('alert(123);');

    // Check that the JS file is attached to the snippet.
    $this->drupalGet($this->snippetUrl);
    $this->assertXpath(sprintf('//script[contains(@src, "/files/snippet/%s.js?")]', $snippet_id_hash));

    // Disable JS and make sure the file is removed.
    $edit = [
      'js[status]' => FALSE,
    ];
    $this->drupalPostForm($this->snippetEditUrl . '/js', $edit, 'Save');
    $this->assertSession()->linkNotExists('Open file');
    $this->drupalGet($file_url);
    $this->assertSession()->statusCodeEquals(404);
  }

}
