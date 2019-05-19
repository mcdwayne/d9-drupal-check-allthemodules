<?php

namespace Drupal\Tests\smart_title\Functional;

/**
 * Tests the module's title hide functionality.
 *
 * @group smart_title
 */
class SmartTitleXssTest extends SmartTitleBrowserTestBase {

  /**
   * Tests XSS escaping.
   */
  public function testConfigXss() {
    // Enable Smart Title for the test_page content type.
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/structure/types/manage/test_page/display', [
      'smart_title__enabled' => TRUE,
    ], 'Save');
    $this->drupalPostForm(NULL, [
      'fields[smart_title][weight]' => '-5',
      'fields[smart_title][region]' => 'content',
    ], 'Save');
    $this->click('[name="smart_title_settings_edit"]');
    $this->drupalPostForm(NULL, [
      'fields[smart_title][settings_edit_form][settings][smart_title__classes]' => '<script>alert("XSS classes")</script>',
    ], 'Save');

    try {
      $this->drupalPostForm(NULL, [
        'fields[smart_title][settings_edit_form][settings][smart_title__tag]' => '<script>alert("XSS tag")</script>',
      ], 'Save');
      $this->fail('Expected exception has not been thrown.');
    }
    catch (\Exception $e) {
      $this->pass('Expected exception has been thrown.');
    }

    try {
      $this->drupalPostForm(NULL, [
        'fields[smart_title][settings_edit_form][settings][smart_title__link]' => '<script>alert("XSS link")</script>',
      ], 'Save');
      $this->fail('Expected exception has not been thrown.');
    }
    catch (\Exception $e) {
      $this->pass('Expected exception has been thrown.');
    }

    // Summary is protected.
    $web_assert = $this->assertSession();
    $web_assert->responseNotContains('<script>alert("XSS classes")</script>');

    // Node page is safe.
    $this->drupalGet($this->testPageNode->toUrl());
    $web_assert = $this->assertSession();
    $web_assert->responseNotContains('<script>alert("XSS classes")</script>');

    // Set dangerous settings directly to the entity.
    \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.' . $this->testPageNode->getType() . '.default')
      ->setThirdPartySetting('smart_title', 'settings', [
        'smart_title__tag' => '<script>alert("XSS tag")</script>',
        'smart_title__classes' => ['<script>alert("XSS classes")</script>'],
        'smart_title__link' => '<script>alert("XSS link")</script>',
      ])->save(TRUE);

    $this->drupalGet($this->testPageNode->toUrl());
    $web_assert = $this->assertSession();
    $web_assert->responseNotContains('<script>alert("XSS tag")</script>');
    $web_assert->responseNotContains('<script>alert("XSS classes")</script>');
    $web_assert->responseNotContains('<script>alert("XSS link")</script>');
  }

}
