<?php

namespace Drupal\Tests\cck_select_other\Functional;

/**
 * Tests that HTML entities are not encoded properly.
 *
 * @group cck_select_other
 */
class CckSelectOtherHtmlEntitiesTest extends CckSelectOtherTestBase {

  /**
   * Asserts HTML entities are not double-encoded.
   */
  public function testHtmlEntities() {
    $options = $this->createOptions();

    $field_info = [
      'settings' => [
        'allowed_values' => $options,
      ],
      'cardinality' => 1,
    ];
    $config_info = ['required' => 0];

    $field = $this->createSelectOtherListField('list_string', $field_info, $config_info);
    $field_name = $field->getName();

    // Login as content creator.
    $this->drupalLogin($this->webUser);

    $edit = [
      'title[0][value]' => $this->randomString(25),
      $field_name . '[0][select_other_list]' => 'other',
      $field_name . '[0][select_other_text_input]' => '&',
    ];
    $this->drupalPostForm('/node/add/' . $this->contentType->id(), $edit, 'Save');
    // Decode entities in the page so that we can assert that there are no other
    // raw entities in there. MinkWTF. Still not sure if this is going to work
    // the same way even getting the raw session.
    $raw = html_entity_decode($this->getSession()->getPage()->getHtml());
    $this->assertNotContains('<div class="field__item">&amp;</div>', $raw);
  }

}
