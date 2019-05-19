<?php

namespace Drupal\Tests\snippet_manager\Functional;

/**
 * Mini snippet variable test.
 *
 * @group snippet_manager
 */
class MiniSnippetVariableTest extends TestBase {

  /**
   * Test callback.
   */
  public function testMiniSnippetVariable() {
    $edit = [
      'plugin_id' => 'mini_snippet',
      'name' => 'ms',
    ];
    $this->drupalPostForm($this->snippetEditUrl . '/variable/add', $edit, 'Save and continue');
    $this->assertStatusMessage('The variable has been created.');

    $edit = [
      'configuration[template]' => '{{ 7 * 7 }}',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertStatusMessage('The variable has been updated.');

    $edit = [
      'template[value]' => '<div class="mini_snippet">-= {{ ms }} =-</div>',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->drupalGet($this->snippetUrl);

    $this->assertXpath('//div[@class="mini_snippet" and text() = "-= 49 =-"]');
  }

}
