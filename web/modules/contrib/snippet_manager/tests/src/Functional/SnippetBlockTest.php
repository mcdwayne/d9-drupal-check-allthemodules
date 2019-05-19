<?php

namespace Drupal\Tests\snippet_manager\Functional;

/**
 * Test for snippet block.
 *
 * @group snippet_manager
 */
class SnippetBlockTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected static $permissions = [
    'administer blocks',
    'administer themes',
  ];

  /**
   * Test callback.
   */
  public function testBlock() {

    $this->drupalGet($this->snippetEditUrl);

    // Check block form appearance.
    $prefix = '//fieldset[legend/span[.="Block"]]';
    $this->assertXpath($prefix . '//input[@name = "block[status]" and not(@checked)]/next::label[text() = "Enable snippet block"]');
    $this->assertXpath($prefix . '//label[text() = "Admin description"]/next::input[@name = "block[name]"]');

    // Enable block.
    $edit = [
      'block[status]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertXpath($prefix . '//input[@name = "block[status]" and @checked]');

    // Check if the block appears on block administration page.
    $this->drupalGet('admin/structure/block/library/classy');
    $this->assertXpath(sprintf('//td[div[text() = "%s"]]/next::td[text() = "Snippet"]', $this->snippetLabel));

    // Set block name.
    $edit = [
      'block[name]' => 'Example',
    ];
    $this->drupalPostForm($this->snippetEditUrl, $edit, 'Save');
    $this->assertXpath($prefix . '//input[@name = "block[name]" and @value = "Example"]');

    // Check if block admin name has been changed.
    $this->drupalGet('admin/structure/block/library/classy');
    $this->assertXpath('//td[div[text() = "Example"]]/next::td[. = "Snippet"]');

    // Create block instance and make sure it appears in a specified region.
    $edit = [
      'settings[label]' => 'Beer',
      'region' => 'sidebar_first',
    ];
    $this->drupalPostForm(sprintf('admin/structure/block/add/snippet:%s/classy', $this->snippetId), $edit, 'Save block');

    $active_block_xpath = implode([
      '//div[contains(@class, "region-sidebar-first")]',
      '/div[@id = "block-example"]',
      '/h2[text()="Beer"]/following::div[@class = "snippet-test" and text() = "9"]',
    ]);
    $this->assertXpath($active_block_xpath);

    // Disable snippet block and check if the respective block should has
    // disappeared.
    $edit = [
      'block[status]' => FALSE,
    ];
    $this->drupalPostForm($this->snippetEditUrl, $edit, 'Save');

    $this->drupalGet('admin/structure/block/library/classy');
    $this->assertSession()->elementNotExists('xpath', '//td[. = "Example"]');

    // Make sure the block is degrading gracefully.
    $missing_block_xpath = implode([
      '//div[contains(@class, "region-sidebar-first")]',
      '/div[@id = "block-example" and h2[text() = "Beer"] and contains(., "This block is broken or missing. You may be missing content or you might need to enable the original module.")]',
    ]);
    $this->assertXpath($missing_block_xpath);

    // Enable block again and check of the block instance returned back.
    $edit = [
      'block[status]' => TRUE,
    ];
    $this->drupalPostForm($this->snippetEditUrl, $edit, 'Save');

    // Broken block can be cached.
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['block_view']);

    $this->drupalGet('admin/structure/block/library/classy');
    $this->assertXpath('//td[div[text() = "Example"]]/next::td[text() = "Snippet"]');
    $this->assertXpath($active_block_xpath);

    // Disable snippet and check if the respective block is not available.
    $this->drupalGet('admin/structure/snippet');
    $this->click(sprintf('//td//a[contains(@href, "admin/structure/snippet/%s/disable")]', $this->snippetId));
    $this->assertXpath($missing_block_xpath);
    $this->drupalGet('admin/structure/block/library/classy');
    $this->assertSession()->elementNotExists('xpath', '//td[. = "Example"]');

    // Enable and delete the snippet.
    $this->drupalGet('admin/structure/snippet');
    $this->click(sprintf('//td//a[contains(@href, "admin/structure/snippet/%s/enable")]', $this->snippetId));
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['block_view']);
    $this->drupalGet(NULL);
    $this->assertXpath($active_block_xpath);
    $this->drupalPostForm(sprintf('admin/structure/snippet/%s/delete', $this->snippetId), [], 'Delete');
    $this->drupalGet('admin/structure/block/library/classy');
    $this->assertSession()->elementNotExists('xpath', '//td[. = "Example"]');
    $this->assertXpath($missing_block_xpath);
  }

}
