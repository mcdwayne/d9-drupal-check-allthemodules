<?php

namespace Drupal\Tests\wordfilter\Functional;

use \Drupal\Tests\BrowserTestBase;
use \Drupal\Component\Render\FormattableMarkup;

/**
 * Contains the appliance tests regarding Wordfilter configurations.
 *
 * @package Drupal\Tests\wordfilter\Functional
 * @group filter
 */
class WordfilterApplianceTest extends BrowserTestBase  {
  public static $modules = ['wordfilter'];

  protected $profile = 'standard';

  protected $user = NULL;

  /**
   * @see ::nodeInstance().
   */
  protected $node = NULL;
  
  protected $wordfilter_config_id = 'my_superduper_wordfilter_config';

  protected function setUp() {
    parent::setUp();

    $this->user = $this->drupalCreateUser([
      'access wordfilter configurations page',
      'administer wordfilter configurations',
      'administer filters',
      'administer nodes',
      'administer content types',
      'administer comments',
      'administer comment types']);
    $this->node = $this->nodeInstance();
  }

  /**
   * Tests the creation and editing of Wordfilter configurations.
   */
   public function testWordfilterConfigCreationEdit() {
     $this->drupalLogin($this->user);
     $this->createWordfilterConfig();

     // Add a few more items.
     $this->drupalGet('admin/config/wordfilter_configuration/' .  $this->wordfilter_config_id . '/edit');
     $this->submitForm([], 'Add another item');
     $this->submitForm([], 'Add another item');
     $this->submitForm([
       'items[1][substitute]' => '---',
       'items[1][filter_words]' => 'Sit, Amet, Consectetur',
       'items[2][substitute]' => '...',
       'items[2][filter_words]' => 'Adipiscing, Elit, Vestibulum',
     ], 'Save');

     $this->drupalGet('admin/config/wordfilter_configuration/' .  $this->wordfilter_config_id . '/edit');
     $this->assertSession()->fieldValueEquals('items[0][substitute]', '***');
     $this->assertSession()->fieldValueEquals('items[0][filter_words]', 'Lorem, Ipsum, Dolor');
     $this->assertSession()->fieldValueEquals('items[1][substitute]', '---');
     $this->assertSession()->fieldValueEquals('items[1][filter_words]', 'Sit, Amet, Consectetur');
     $this->assertSession()->fieldValueEquals('items[2][substitute]', '...');
     $this->assertSession()->fieldValueEquals('items[2][filter_words]', 'Adipiscing, Elit, Vestibulum');

     // Remove an item.
     $this->submitForm([], 'edit-items-1-remove');
     $this->submitForm([], 'Save');

     $this->drupalGet('admin/config/wordfilter_configuration/' .  $this->wordfilter_config_id . '/edit');
     $this->assertSession()->fieldValueEquals('items[0][substitute]', '***');
     $this->assertSession()->fieldValueEquals('items[0][filter_words]', 'Lorem, Ipsum, Dolor');
     $this->assertSession()->fieldNotExists('items[1][substitute]');
     $this->assertSession()->fieldNotExists('items[1][filter_words]');
     $this->assertSession()->fieldValueEquals('items[2][substitute]', '...');
     $this->assertSession()->fieldValueEquals('items[2][filter_words]', 'Adipiscing, Elit, Vestibulum');
   }

  /**
   * Tests the appliance of Wordfilter configurations on text formats.
   */
  public function testApplianceOnTextFormat() {
    $this->drupalLogin($this->user);
    $this->createWordfilterConfig();
    $this->addWordfilterConfigToBasicHTMLFormat();
    
    $this->drupalGet('node/' . $this->nodeInstance()->id());
    $this->assertSession()->pageTextContains("TITLE The Lorem Ipsum Node");
    $this->assertSession()->pageTextContains("BODY *** *** *** Sit Amet");
  }
  
  /**
   * Tests the appliance of Wordfilter configurations on nodes.
   */
  public function testApplianceOnNode() {
    $this->drupalLogin($this->user);
    $this->createWordfilterConfig();
    $this->addWordfilterConfigToArticle();
    
    $this->drupalGet('node/' . $this->nodeInstance()->id());
    $this->assertSession()->pageTextContains("TITLE The *** *** Node");
    $this->assertSession()->pageTextContains("BODY *** *** *** Sit Amet");
  }
  
  /**
   * Tests the appliance of Wordfilter configurations on comments.
   */
  public function testApplianceOnComment() {
    $this->drupalLogin($this->user);
    $this->createWordfilterConfig();
    $this->addWordfilterConfigToComments();
    $this->addAndViewCommentOnNode();
    
    $this->drupalGet('node/' . $this->nodeInstance()->id());
    $this->assertSession()->pageTextContains("COMMENT-SUBJECT The *** *** Comment");
    $this->assertSession()->pageTextContains("COMMENT-BODY *** *** *** Sit Amet");
  }
  
  /**
   * Helper function to create a Wordfilter configuration.
   */
  protected function createWordfilterConfig() {
    $this->drupalGet('admin/config/wordfilter_configuration/add');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([
      'id' => $this->wordfilter_config_id,
      'label' => 'My superduper Wordfilter config',
      'process_id' => 'default',
      'items[0][substitute]' => '***',
      'items[0][filter_words]' => 'Lorem, Ipsum, Dolor'
    ], 'Save');

    $this->assertSession()->responseContains(
      new FormattableMarkup('Created the %label Wordfilter configuration.',
        ['%label' => 'My superduper Wordfilter config']));
  }

  /**
   * Helper function to add the Wordfilter configuration
   * to the Basic HTML format, which is included in the Standard profile.
   */
  protected function addWordfilterConfigToBasicHTMLFormat() {
    $this->drupalGet('admin/config/content/formats/manage/basic_html');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([
      'filters[wordfilter][status]' => TRUE,
      'filters[wordfilter][settings][active_wordfilter_configs][]' => [$this->wordfilter_config_id],
    ], 'Save configuration');

    $this->assertSession()->responseContains(
      new FormattableMarkup('The text format %format has been updated.',
        ['%format' => 'Basic HTML']));
  }
  
  /**
   * Helper function to add the Wordfilter configuration
   * to Nodes of type 'article'.
   */
   protected function addWordfilterConfigToArticle() {
    $this->drupalGet('/admin/structure/types/manage/article');
    $this->assertSession()->statusCodeEquals(200);
     
    $this->submitForm([
      'active_wordfilter_configs[]' => [$this->wordfilter_config_id],
    ], 'Save content type');
   
    $this->assertSession()->responseContains(
      new FormattableMarkup('The content type %type has been updated.',
        ['%type' => 'Article']));
  }
  
  /**
   * Helper function to add the Wordfilter configuration
   * to Comments of type 'comment' (default comments).
   */
  protected function addWordfilterConfigToComments() {
    $this->drupalGet('/admin/structure/comment/manage/comment');
    $this->assertSession()->statusCodeEquals(200);
     
    $this->submitForm([
      'active_wordfilter_configs[]' => [$this->wordfilter_config_id],
    ], 'Save');
   
    $this->assertSession()->responseContains(
      new FormattableMarkup('Comment type %type has been updated.',
        ['%type' => 'Default comments']));
  }
  
  /**
   * Helper function to add and view a comment on the given node instance.
   */
  protected function addAndViewCommentOnNode() {
    $this->drupalGet('node/' . $this->nodeInstance()->id());
    
    $form = [
      'subject[0][value]' => 'COMMENT-SUBJECT The Lorem Ipsum Comment',
      'comment_body[0][value]' => 'COMMENT-BODY Lorem Ipsum Dolor Sit Amet',
    ];
    
    $this->submitForm($form, 'Preview', 'comment-form');
    $this->assertSession()->pageTextContains("COMMENT-SUBJECT The *** *** Comment");
    $this->assertSession()->pageTextContains("COMMENT-BODY *** *** *** Sit Amet");
    
    $this->submitForm($form, 'Save', 'comment-form');
    $this->assertSession()->pageTextContains("COMMENT-SUBJECT The *** *** Comment");
    $this->assertSession()->pageTextContains("COMMENT-BODY *** *** *** Sit Amet");
  }

  /**
   * Create and get a node instance for all appliance tests.
   *
   * @return \Drupal\node\NodeInterface
   */
  protected function nodeInstance() {
    if (!isset($this->node)) {
      $this->node = $this->createNode([
        'type' => 'article',
        'title' => 'TITLE The Lorem Ipsum Node',
        'status' => 1,
        'body' => [
          [
            'value' => 'BODY Lorem Ipsum Dolor Sit Amet',
            'format' => 'basic_html'
          ]
        ],
      ]);
    }

    return $this->node;
  }
}
