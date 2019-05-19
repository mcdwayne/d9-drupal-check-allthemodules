<?php

namespace Drupal\Tests\snippet_manager\Functional;

/**
 * Block variable test.
 *
 * @group snippet_manager
 */
class BlockVariableTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'snippet_manager',
    'snippet_manager_test',
    'block',
  ];

  /**
   * Test callback.
   */
  public function testEntityVariable() {

    $edit = [
      'plugin_id' => 'block:system_branding_block',
      'name' => 'branding',
    ];
    $this->drupalPostForm($this->snippetEditUrl . '/variable/add', $edit, 'Save and continue');
    $this->assertStatusMessage('The variable has been created.');

    // Check default form appearance.
    $this->assertXpath('//label[text() = "Title"]/following-sibling::input[@name = "configuration[label]" and @value = "Site branding"]');
    $this->assertXpath('//input[@name = "configuration[label_display]" and not(@checked)]/following-sibling::label[text() = "Display title"]');
    $this->assertXpath('//fieldset//input[@name = "configuration[block_branding][use_site_logo]" and @checked]');
    $this->assertXpath('//fieldset//input[@name = "configuration[block_branding][use_site_name]" and @checked]');
    $this->assertXpath('//fieldset//input[@name = "configuration[block_branding][use_site_slogan]" and @checked]');

    // Submit some values and make sure they are accepted.
    $edit = [
      'configuration[label]' => 'Foo',
      'configuration[label_display]' => TRUE,
      'configuration[block_branding][use_site_name]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertStatusMessage('The variable has been updated.');

    $this->assertXpath('//main//table/tbody/tr/td[position() = 1]/a[@href = "#snippet-edit-form" and text() = "branding"]');

    $edit = [
      'template[value]' => '<div class="snippet-block">{{ branding }}</div>',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $this->click('//table//td[position() = 4]//a[text() = "Edit"]');

    $this->assertXpath('//label[text() = "Title"]/following-sibling::input[@name = "configuration[label]" and @value = "Foo"]');
    $this->assertXpath('//input[@name = "configuration[label_display]" and @checked]/following-sibling::label[text() = "Display title"]');
    $this->assertXpath('//fieldset//input[@name = "configuration[block_branding][use_site_logo]" and @checked]');
    $this->assertXpath('//fieldset//input[@name = "configuration[block_branding][use_site_name]" and not(@checked)]');
    $this->assertXpath('//fieldset//input[@name = "configuration[block_branding][use_site_slogan]" and @checked]');
    $this->assertNoXpath('//a[@text() = "Drupal"]');

    // Check block rendering with title and without site name.
    $this->drupalGet($this->snippetUrl);

    $xpath_prefix = '//div[@class = "snippet-block"]/div[contains(@class, "block")]';
    $this->assertXpath($xpath_prefix . '/h2[text() = "Foo"]/following-sibling::a[@title = "Home"]/img[contains(@src, "logo.svg")]');

    // Check form rendering without title and with site name.
    $edit = [
      'configuration[label_display]' => FALSE,
      'configuration[block_branding][use_site_name]' => TRUE,
    ];
    $this->drupalPostForm($this->snippetEditUrl . '/variable/branding/edit', $edit, 'Save');

    $this->drupalGet($this->snippetUrl);
    $this->assertNoXpath('//h2[text() = "Foo"]');
    $this->assertXpath($xpath_prefix . '//a[text() = "Drupal"]');
  }

}
