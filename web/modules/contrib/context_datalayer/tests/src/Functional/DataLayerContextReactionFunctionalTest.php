<?php

namespace Drupal\Tests\context_datalayer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * DataLayer Context Reaction Functional Tests.
 *
 * @group context_datalayer
 */
class DataLayerContextReactionFunctionalTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'context',
    'context_ui',
    'datalayer',
    'context_datalayer',
  ];

  /**
   * Tests DataLayer ContextReaction Configuration Form Handling.
   */
  public function testDataLayerContextReactionConfigurationFormHandling() {
    // Setup assert session.
    $assert = $this->assertSession();
    // Login.
    $this->drupalLogin($this->rootUser);
    // Create context.
    $this->drupalPostForm('admin/structure/context/add', ['label' => 'datalayer_test_context', 'name' => 'datalayer_test_context'], t('Save'));
    // Add condition for authenticated users.
    $this->drupalGet('admin/structure/context/datalayer_test_context/condition/add/user_role');
    $this->drupalPostForm('admin/structure/context/datalayer_test_context', ['conditions[user_role][roles][authenticated]' => 'authenticated'], t('Save and continue'));
    // Add datalayer reaction.
    $this->drupalGet('admin/structure/context/datalayer_test_context/reaction/add/datalayer');
    $this->drupalGet('admin/structure/context/datalayer_test_context');
    // Verify add new pair checkbox is required key/value pair.
    $this->drupalPostForm(
      'admin/structure/context/datalayer_test_context',
      [
        'reactions[datalayer][new][key]' => 'foo',
        'reactions[datalayer][new][value]' => 'bar',
        'reactions[datalayer][new][type]' => 'string',
        'reactions[datalayer][add_new_pair]' => '',
      ],
      t('Add new pair')
    );
    $this->drupalGet('admin/structure/context/datalayer_test_context');
    $assert->pageTextContains('No key/value pairs found');
    $assert->pageTextNotContains('"foo":"bar"');
    // Verify we can add a new pair.
    $this->drupalPostForm(
      'admin/structure/context/datalayer_test_context',
      [
        'reactions[datalayer][new][key]' => 'foo',
        'reactions[datalayer][new][value]' => 'bar',
        'reactions[datalayer][new][type]' => 'string',
        'reactions[datalayer][add_new_pair]' => '1',
      ],
      t('Add new pair')
    );
    $this->drupalGet('admin/structure/context/datalayer_test_context');
    $assert->pageTextNotContains('No key/value pairs found');
    $assert->pageTextContains('"foo":"bar"');
    // Verify we can add a second pair.
    $this->drupalPostForm(
      'admin/structure/context/datalayer_test_context',
      [
        'reactions[datalayer][new][key]' => 'bar',
        'reactions[datalayer][new][value]' => 'baz',
        'reactions[datalayer][new][type]' => 'string',
        'reactions[datalayer][add_new_pair]' => '1',
      ],
      t('Add new pair')
    );
    $this->drupalGet('admin/structure/context/datalayer_test_context');
    $assert->pageTextNotContains('No key/value pairs found');
    $assert->pageTextContains('"foo":"bar"');
    $assert->pageTextContains('"bar":"baz"');
    // Verify we can remove a pair.
    $this->drupalPostForm(
      'admin/structure/context/datalayer_test_context',
      [
        'reactions[datalayer][remove][remove_table][2]' => '2',
      ],
      t('Remove selected pairs')
    );
    $this->drupalGet('admin/structure/context/datalayer_test_context');
    $assert->pageTextContains('"foo":"bar"');
    $assert->pageTextNotContains('"bar":"baz"');
    // Verify we can remove all pairs.
    $this->drupalPostForm(
      'admin/structure/context/datalayer_test_context',
      [
        'reactions[datalayer][remove][remove_table][1]' => '1',
      ],
      t('Remove selected pairs')
    );
    $this->drupalGet('admin/structure/context/datalayer_test_context');
    $assert->pageTextContains('No key/value pairs found');
    $assert->pageTextNotContains('"foo":"bar"');
    $assert->pageTextNotContains('"bar":"baz"');
    // Verify we can remove multiple pairs.
    $this->drupalPostForm(
      'admin/structure/context/datalayer_test_context',
      [
        'reactions[datalayer][new][key]' => 'baz',
        'reactions[datalayer][new][value]' => 'qux',
        'reactions[datalayer][new][type]' => 'string',
        'reactions[datalayer][add_new_pair]' => '1',
      ],
      t('Add new pair')
    );
    $this->drupalGet('admin/structure/context/datalayer_test_context');
    $this->drupalPostForm(
      'admin/structure/context/datalayer_test_context',
      [
        'reactions[datalayer][new][key]' => 'quux',
        'reactions[datalayer][new][value]' => 'qub',
        'reactions[datalayer][new][type]' => 'string',
        'reactions[datalayer][add_new_pair]' => '1',
      ],
      t('Add new pair')
    );
    $this->drupalGet('admin/structure/context/datalayer_test_context');
    $assert->pageTextNotContains('No key/value pairs found');
    $assert->pageTextContains('"baz":"qux"');
    $assert->pageTextContains('"quux":"qub"');
    $this->drupalPostForm(
      'admin/structure/context/datalayer_test_context',
      [
        'reactions[datalayer][remove][remove_table][1]' => '1',
        'reactions[datalayer][remove][remove_table][2]' => '2',
      ],
      t('Remove selected pairs')
    );
    $this->drupalGet('admin/structure/context/datalayer_test_context');
    $assert->pageTextContains('No key/value pairs found');
    $assert->pageTextNotContains('"baz":"qux"');
    $assert->pageTextNotContains('"quux":"qub"');
    // Verify type output.
    $this->drupalPostForm(
      'admin/structure/context/datalayer_test_context',
      [
        'reactions[datalayer][new][key]' => 'foo',
        'reactions[datalayer][new][value]' => 'bar',
        'reactions[datalayer][new][type]' => 'string',
        'reactions[datalayer][add_new_pair]' => '1',
      ],
      t('Add new pair')
    );
    $this->drupalPostForm(
      'admin/structure/context/datalayer_test_context',
      [
        'reactions[datalayer][new][key]' => 'bar',
        'reactions[datalayer][new][value]' => '2018',
        'reactions[datalayer][new][type]' => 'integer',
        'reactions[datalayer][add_new_pair]' => '1',
      ],
      t('Add new pair')
    );
    $this->drupalPostForm(
      'admin/structure/context/datalayer_test_context',
      [
        'reactions[datalayer][new][key]' => 'baz',
        'reactions[datalayer][new][value]' => '1',
        'reactions[datalayer][new][type]' => 'boolean',
        'reactions[datalayer][add_new_pair]' => '1',
      ],
      t('Add new pair')
    );
    $this->drupalGet('admin/structure/context/datalayer_test_context');
    $assert->pageTextContains('"foo":"bar"');
    $assert->pageTextContains('"bar":2018');
    $assert->pageTextContains('"baz":true');
    // Verify datalayer overwrite.
    $assert->pageTextContains('"drupalLanguage":"en"');
    $this->drupalPostForm(
      'admin/structure/context/datalayer_test_context',
      [
        'reactions[datalayer][new][key]' => 'drupalLanguage',
        'reactions[datalayer][new][value]' => 'foobarbaz',
        'reactions[datalayer][new][type]' => 'string',
        'reactions[datalayer][add_new_pair]' => '1',
        'reactions[datalayer][datalayer_overwrite]' => '1',
      ],
      t('Add new pair')
    );
    $this->drupalGet('admin/structure/context/datalayer_test_context');
    $assert->pageTextNotContains('"drupalLanguage":"en"');
    $assert->pageTextContains('"drupalLanguage":"foobarbaz"');
  }

}
