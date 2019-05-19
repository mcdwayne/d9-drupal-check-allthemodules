<?php

namespace Drupal\Tests\social_simple\Functional;

/**
 * Provides tests for Social simple module.
 *
 * @group social_simple
 */
class TestSocialSimpleTest extends TestSocialSimpleTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a super admin.
    $this->adminUser = $this->drupalCreateUser(array_keys(\Drupal::service('user.permissions')->getPermissions()));
  }

  /**
   * Test the social simple module for node and block.
   */
  public function testSocialSimple() {
    $this->drupalLogin($this->adminUser);
    $this->assertSession()->statusCodeEquals(200);

    $bundle_path = '/admin/structure/types/manage/article';
    // Check that the extra field do not appears in the overview display page.
    $this->drupalGet($bundle_path . '/display');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Social simple buttons');

    $href_twitter = 'https://twitter.com/intent/tweet/?url=' . str_replace(':', '%3A', $this->baseUrl) . '/node/' . $this->article->id() . '&text=' . $this->article->label();
    $href_googleplus = 'https://plus.google.com/share?url=' . str_replace(':', '%3A', $this->baseUrl) . '/node/' . $this->article->id();
    $href_facebook = 'https://www.facebook.com/sharer/sharer.php?u=' . str_replace(':', '%3A', $this->baseUrl) . '/node/' . $this->article->id();
    $href_linkedin = 'https://www.linkedin.com/shareArticle?mini=true&url=' . str_replace(':', '%3A', $this->baseUrl) . '/node/' . $this->article->id() . '&title=' . $this->article->label();

    $this->drupalGet('/node/' . $this->article->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefNotExists($href_twitter);

    $this->drupalGet($bundle_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->getSession()->getPage()->fillField('social_simple_share', 1);
    $this->getSession()->getPage()->fillField('social_simple_title', 'Share on');
    $this->getSession()->getPage()->fillField('social_simple_networks[twitter]', 'twitter');
    $this->getSession()->getPage()->fillField('social_simple_networks[googleplus]', 'googleplus');
    $this->getSession()->getPage()->fillField('social_simple_networks[facebook]', 'facebook');
    $this->getSession()->getPage()->pressButton('Save content type');
    drupal_flush_all_caches();

    $this->drupalGet($bundle_path);
    $this->assertSession()->optionExists('social_simple_hashtags', 'field_tags');

    $this->setComponentViewDisplay('node.article.default', 'node', 'article', 'default', 'social_simple_buttons');
    $this->drupalGet($bundle_path . '/display');
    $this->assertSession()->pageTextContains('Social simple buttons');
    $this->assertText('Social simple buttons');

    $this->drupalGet('/node/' . $this->article->id());
    $this->assertSession()->pageTextContains('Share on');
    $this->assertSession()->linkByHrefExists($href_twitter);
    $this->assertSession()->linkByHrefExists($href_googleplus);
    $this->assertSession()->linkByHrefExists($href_facebook);
    $this->assertSession()->linkByHrefNotExists($href_linkedin);

    $this->drupalGet($bundle_path);
    $this->getSession()->getPage()->fillField('social_simple_networks[linkedin]', 'linkedin');
    $this->getSession()->getPage()->fillField('social_simple_hashtags', 'field_tags');
    $this->getSession()->getPage()->pressButton('Save content type');
    drupal_flush_all_caches();

    $this->drupalGet($bundle_path);
    $option_field = $this->assertSession()->optionExists('social_simple_hashtags', 'field_tags');
    $message = "Option field_tags for field social_simple_hashtags is selected.";
    $this->assertTrue($option_field->hasAttribute('selected'), $message);

    $this->drupalGet('/node/' . $this->article->id());
    $this->assertSession()->linkByHrefExists($href_linkedin);

    // Add a tag to the article.
    $node_edit_path = '/node/' . $this->article->id() . '/edit';
    $this->drupalGet($node_edit_path);
    $this->getSession()->getPage()->fillField('field_tags[0][target_id]', $this->term1->label() . ' (' . $this->term1->id() . ')');
    $this->getSession()->getPage()->pressButton('Save');

    // Check share url twitter with hashtags.
    $this->drupalGet('/node/' . $this->article->id());
    $href_twitter_hashtags = 'https://twitter.com/intent/tweet/?url=' . str_replace(':', '%3A', $this->baseUrl) . '/node/' . $this->article->id() . '&text=' . $this->article->label() . '&hashtags=' . $this->term1->label();
    $this->assertSession()->linkByHrefExists($href_twitter_hashtags);

    // Remove extra field form view display.
    $this->removeComponentViewDisplay('node.article.default', 'node', 'article', 'default', 'social_simple_buttons');
    $this->drupalGet('/node/' . $this->article->id());
    $this->assertSession()->linkByHrefNotExists($href_twitter_hashtags);
    $this->assertSession()->linkByHrefNotExists($href_googleplus);
    $this->assertSession()->linkByHrefNotExists($href_facebook);
    $this->assertSession()->linkByHrefNotExists($href_linkedin);

    // Create a block social_simple_block.
    $settings = [
      'social_share_title' => 'Block Share on',
    ];
    $block = $this->drupalPlaceBlock('social_simple_block', $settings);
    $this->drupalGet('/admin/structure/block/manage/' . $block->id());

    $this->drupalGet('/admin/structure/block/manage/' . $block->id());
    $this->getSession()->getPage()->fillField('settings[social_networks][twitter]', 'twitter');
    $this->getSession()->getPage()->pressButton('Save block');

    $this->drupalGet('/admin/structure/block/manage/' . $block->id());
    $this->assertSession()->checkboxChecked('edit-settings-social-networks-twitter');

    // Twitter share url from block for an article.
    $this->drupalGet('/node/' . $this->article->id());
    $this->assertSession()->linkByHrefExists($href_twitter_hashtags);

    // Test on a taxonomy term page.
    $this->drupalGet('/taxonomy/term/' . $this->term1->id());
    $href_twitter_term = 'https://twitter.com/intent/tweet/?url=' . str_replace(':', '%3A', $this->baseUrl) . '/taxonomy/term/' . $this->term1->id() . '&text=' . $this->term1->label();
    $this->assertSession()->linkByHrefExists($href_twitter_term);

    // Test per node behavior.
    \Drupal::service('module_installer')->install(['social_simple_per_node']);
    // Enable again the extra field and delete the block.
    $this->setComponentViewDisplay('node.article.default', 'node', 'article', 'default', 'social_simple_buttons');
    $block->delete();

    $this->normalUser = $this->drupalCreateUser([
      'create article content',
      'edit any article content',
      'delete any article content',
      'access content',
    ]);

    $this->advancedUser = $this->drupalCreateUser([
      'create article content',
      'edit any article content',
      'delete any article content',
      'access content',
      'disable social links per node',
    ]);

    $this->drupalGet($node_edit_path);
    $this->assertSession()->pageTextContains('Social share');
    $this->assertSession()->fieldExists('edit-social-share');
    $this->assertSession()->checkboxChecked('edit-social-share');

    $this->drupalGet($node_edit_path);
    $this->getSession()->getPage()->uncheckField('social_share');
    $this->getSession()->getPage()->pressButton('Save');

    $this->drupalGet($node_edit_path);
    $this->assertSession()->checkboxNotChecked('edit-social-share');

    $this->drupalGet('/node/' . $this->article->id());
    $this->assertSession()->pageTextNotContains('Share on');
    $this->assertSession()->linkByHrefNotExists($href_twitter_hashtags);
    $this->assertSession()->linkByHrefNotExists($href_googleplus);
    $this->assertSession()->linkByHrefNotExists($href_facebook);

    // Normal user can not update settings per node.
    $this->drupalLogout();
    $this->drupalLogin($this->normalUser);

    $this->drupalGet($node_edit_path);
    $this->assertSession()->pageTextNotContains('Social share');
    $this->assertSession()->fieldNotExists('edit-social-share');

    $this->getSession()->getPage()->pressButton('Save');
    $this->drupalGet('/node/' . $this->article->id());
    $this->assertSession()->pageTextNotContains('Share on');
    $this->assertSession()->linkByHrefNotExists($href_twitter_hashtags);
    $this->assertSession()->linkByHrefNotExists($href_googleplus);
    $this->assertSession()->linkByHrefNotExists($href_facebook);

    // Change settings again for social share for article.
    $this->drupalLogout();
    $this->drupalLogin($this->advancedUser);
    $this->drupalGet($node_edit_path);
    $this->assertSession()->fieldExists('edit-social-share');
    $this->assertSession()->checkboxNotChecked('edit-social-share');

    $this->drupalGet($node_edit_path);
    $this->getSession()->getPage()->checkField('social_share');
    $this->getSession()->getPage()->pressButton('Save');

    $this->drupalGet($node_edit_path);
    $this->assertSession()->checkboxChecked('edit-social-share');
    $this->drupalGet('/node/' . $this->article->id());
    $this->assertSession()->pageTextContains('Share on');
    $this->assertSession()->linkByHrefExists($href_twitter_hashtags);
    $this->assertSession()->linkByHrefExists($href_googleplus);
    $this->assertSession()->linkByHrefExists($href_facebook);

    // Normal can not update settings per node.
    $this->drupalLogout();
    $this->drupalLogin($this->normalUser);

    $this->drupalGet($node_edit_path);
    $this->assertSession()->fieldNotExists('edit-social-share');
    $this->getSession()->getPage()->pressButton('Save');

    $this->drupalGet('/node/' . $this->article->id());
    $this->assertSession()->linkByHrefExists($href_twitter_hashtags);
    $this->assertSession()->linkByHrefExists($href_googleplus);
    $this->assertSession()->linkByHrefExists($href_facebook);

  }

}
