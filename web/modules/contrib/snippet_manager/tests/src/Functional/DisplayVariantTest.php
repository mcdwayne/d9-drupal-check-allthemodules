<?php

namespace Drupal\Tests\snippet_manager\Functional;

/**
 * Display variant test.
 *
 * @group snippet_manager
 */
class DisplayVariantTest extends TestBase {

  /**
   * Test callback.
   */
  public function testDisplayVariant() {

    // -- Check display variant form appearance.
    $this->drupalGet($this->snippetEditUrl);
    $prefix = '//fieldset[legend/span[text()="Display variant"]]';
    $this->assertXpath($prefix . '//input[@name="display_variant[status]" and not(@checked)]/next::label[text()="Enable display variant"]');
    $this->assertXpath($prefix . '//label[text()="Admin label"]/next::input[@name="display_variant[admin_label]"]');

    // -- Enable display_variant.
    $edit = [
      'display_variant[status]' => TRUE,
      'display_variant[admin_label]' => 'Foo',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertXpath($prefix . '//input[@name="display_variant[status]" and @checked]');

    $template = implode([
      '<div class="dv-wrapper">',
      '  <div class="dv-title">{{ title }}</div>',
      '  <div class="dv-main">{{ main }}</div>',
      '</div>',
    ]);
    $edit = [
      'template[value]' => $template,
    ];
    $this->drupalPostForm($this->snippetEditUrl . '/template', $edit, 'Save');

    // -- Display variant title variable.
    $edit = [
      'plugin_id' => 'display_variant:title',
      'name' => 'title',
    ];
    $this->drupalPostForm($this->snippetEditUrl . '/variable/add', $edit, 'Save and continue');
    $this->assertXpath('//form[contains(text(),"This plugin has no configurable options.")]');
    $this->drupalPostForm(NULL, [], 'Save');
    $this->assertStatusMessage('The variable has been updated.');

    // -- Display variant main variable.
    $edit = [
      'plugin_id' => 'display_variant:main_content',
      'name' => 'main',
    ];
    $this->drupalPostForm($this->snippetEditUrl . '/variable/add', $edit, 'Save and continue');
    $this->assertXpath('//form[contains(text(),"This plugin has no configurable options.")]');
    $this->drupalPostForm(NULL, [], 'Save');
    $this->assertStatusMessage('The variable has been updated.');

    // -- Check display variant definition.
    $variant_manager = \Drupal::service('plugin.manager.display_variant');
    $variant_definition = $variant_manager->getDefinition('snippet_display_variant:' . $this->snippetId);
    $this->assertEquals('Foo', $variant_definition['admin_label']);

    // -- Check display variant rendering.
    $options = [
      'query' => [
        'display-variant' => 'snippet_display_variant:' . $this->snippetId,
      ],
    ];
    $this->drupalGet('snippet-manager-test/foo', $options);
    $this->assertXpath('//div[@class="dv-wrapper"]/div[@class="dv-title" and text()="Foo"]');
    $this->assertXpath('//div[@class="dv-wrapper"]/div[@class="dv-main" and text()="Bar."]');
  }

}
