<?php

/**
 * @file
 * Contains Drupal\inline\Tests\InlineMacroTest.
 */

namespace Drupal\inline\Tests;

/**
 * Tests general parsing, validation, and rendering of inline macros.
 */
class InlineMacroTest extends InlineTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Macro handling',
      'description' => 'Tests general parsing, validation, and rendering of inline macros.',
      'group' => 'Inline',
    );
  }

  function setUp() {
    parent::setUp();

    // @todo Add tests for anonymous users (verifying page/block caching).
    // @todo Add proper tests for entity/field/etc permissions.
    $this->web_user = $this->drupalCreateUser(array(
      'bypass node access',
      filter_permission_name($this->formats['filtered_html']),
    ));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Tests basic macro handling.
   *
   * @todo This is not how to write tests. However, as long as the code and API
   *   is still in flux, we only want to verify basic assumptions as we proceed.
   *
   * This test leverages the built-in text field, which happens to be the only
   * field type in Drupal core that has:
   * - multiple field types
   * - multiple field widgets
   * - multiple field formatters
   * - formatter settings (even depending on the formatter)
   * - security aspects
   *
   * Thus, the built-in text field actually serves as ideal testing ground.
   */
  function testBasicMacroHandling() {
    // Create a first node to embed, using a precise intro and outro, and lots
    // of random text in between.
    $expected_summary = 'You have inlined the summary.';
    $expected_summary_raw = '<a href="http://example.com">' . $expected_summary . '</a>';
    $expected_intro = 'The full value starts here.';
    $expected_outro = 'Thanks for showing me inline!';
    $full_body = $expected_intro;
    $full_body .= ' ' . file_get_contents(DRUPAL_ROOT . '/README.txt') . "\n";
    $full_body .= $expected_outro;
    $this->inlinedNode = $this->inlineCreateNode(array('summary' => $expected_summary_raw, 'value' => $full_body));

    // Verify that the first node appears as expected in teaser view mode.
    $this->drupalGet('node');
    $this->assertRaw($this->inlinedNode->title);
    $this->assertRaw($expected_summary_raw);
    $this->assertText($expected_summary);
    $this->assertNoText($expected_intro);
    $this->assertNoText($expected_outro);

    // Verify that the first node appears as expected in full view mode.
    $this->drupalGet('node/' . $this->inlinedNode->nid);
    $this->assertRaw($this->inlinedNode->title);
    $this->assertNoRaw($expected_summary_raw);
    $this->assertNoText($expected_summary);
    $this->assertText($expected_intro);
    $this->assertText($expected_outro);

    // Create our actual testing node.
    // Also populate the second text field.
    $expected_second_value = 'Value from another field on this entity.';
    $this->node = $this->inlineCreateNode('', array(
      $this->secondFieldName => array(LANGUAGE_NOT_SPECIFIED => array(array('value' =>$expected_second_value)))
    ));

    $this->drupalGet('node/' . $this->node->nid);
    $this->assertText($expected_second_value);

    // Inline an entity, using only required macro parameters (no view mode).
    $this->inlineUpdateNode($this->node, $this->inlineBuildMacro('entity', array(
      'type' => 'node',
      'id' => $this->inlinedNode->nid,
    )));
    $this->drupalGet('node/' . $this->node->nid . '/edit');

    // Verify that the first node appears embedded in teaser view mode.
    $this->drupalGet('node/' . $this->node->nid);
    $this->assertRaw($this->inlinedNode->title);
    $this->assertRaw($expected_summary_raw);
    $this->assertText($expected_summary);
    $this->assertNoText($expected_intro);
    $this->assertNoText($expected_outro);

    // Inline an entity, including optional arguments (view mode).
    $this->inlineUpdateNode($this->node, $this->inlineBuildMacro('entity', array(
      'type' => 'node',
      'id' => $this->inlinedNode->nid,
      'view_mode' => 'full',
    )));

    // Verify that the first node appears embedded in full view mode.
    $this->drupalGet('node/' . $this->node->nid);
    $this->assertRaw($this->inlinedNode->title);
    $this->assertNoRaw($expected_summary_raw);
    $this->assertNoText($expected_summary);
    $this->assertText($expected_intro);
    $this->assertText($expected_outro);

    // Verify that required arguments cannot be omitted.
    $this->inlineUpdateNodeUI($this->node, $this->inlineBuildMacro('entity', array(
      'type' => 'node',
    )));
    $this->assertRaw(t('Missing argument %arg.', array('%arg' => 'id')));

    $this->inlineUpdateNodeUI($this->node, $this->inlineBuildMacro('entity', array()));
    $this->assertRaw(t('Missing argument %arg.', array('%arg' => 'type')));

    // Verify that arguments are validated.
    $this->inlineUpdateNodeUI($this->node, $this->inlineBuildMacro('entity', array(
      'type' => 'node',
      'id' => 0,
    )));
    $this->assertRaw(t('An entity ID is required.'));

    $this->inlineUpdateNodeUI($this->node, $this->inlineBuildMacro('entity', array(
      'type' => 'node2',
      'id' => $this->inlinedNode->nid,
    )));
    $this->assertRaw(t('The specified entity type %type does not exist.', array('%type' => 'node2')));



    // Inline a field, using only required macro parameters.
    $this->inlineUpdateNode($this->node, $this->inlineBuildMacro('field', array(
      'type' => 'node',
      'id' => $this->inlinedNode->nid,
      'name' => 'body',
    )));

    // Verify that the body field appears embedded using the field formatter
    // configured for the default view mode (full).
    $this->drupalGet('node/' . $this->node->nid);
    $this->assertNoRaw($this->inlinedNode->title);
    $this->assertNoRaw($expected_summary_raw);
    $this->assertNoText($expected_summary);
    $this->assertText($expected_intro);
    $this->assertText($expected_outro);

    // Inline a field, using only required macro parameters.
    $this->inlineUpdateNode($this->node, $this->inlineBuildMacro('field', array(
      'type' => 'node',
      'id' => $this->inlinedNode->nid,
      'name' => 'body',
      'view_mode' => 'teaser',
    )));

    // Verify that the body field appears embedded using the field formatter
    // configured for the teaser view mode.
    $this->drupalGet('node/' . $this->node->nid);
    $this->assertNoRaw($this->inlinedNode->title);
    $this->assertRaw($expected_summary_raw);
    $this->assertText($expected_summary);
    $this->assertNoText($expected_intro);
    $this->assertNoText($expected_outro);

    // Inline the second field on the same entity.
    $this->inlineUpdateNode($this->node, $this->inlineBuildMacro('field', array(
      'type' => 'node',
      'id' => 0,
      'name' => $this->secondFieldName,
    )));

    // Verify that the second field was embedded into the body and the original
    // field was not rendered.
    $this->drupalGet('node/' . $this->node->nid);
    $this->assertUniqueText($expected_second_value);

    // Inline the second field on the same entity, retaining the original.
    $this->inlineUpdateNode($this->node, $this->inlineBuildMacro('field', array(
      'type' => 'node',
      'id' => 0,
      'name' => $this->secondFieldName,
      'render_original' => 1,
    )));

    // Verify that the second field was embedded into the body and the original
    // field was rendered.
    $this->drupalGet('node/' . $this->node->nid);
    $this->assertNoUniqueText($expected_second_value);
  }
}
