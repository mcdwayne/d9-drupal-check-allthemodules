<?php

namespace Drupal\Tests\smart_title\Functional;

/**
 * Tests the module's title hide functionality.
 *
 * @group smart_title
 */
class SmartTitleConfigTest extends SmartTitleBrowserTestBase {

  /**
   * Test saved configuration.
   */
  public function testSavedConfiguration() {
    $this->drupalLogin($this->adminUser);

    // Enable Smart Title for the test_page content type's teaser.
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/structure/types/manage/test_page/display/teaser', [
      'smart_title__enabled' => TRUE,
    ], 'Save');
    $this->drupalPostForm(NULL, [
      'fields[smart_title][weight]' => '-5',
      'fields[smart_title][region]' => 'content',
    ], 'Save');
    $smart_title_enabled = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.' . $this->testPageNode->getType() . '.teaser')
      ->getThirdPartySetting('smart_title', 'enabled', FALSE);
    $this->assertTrue($smart_title_enabled);

    // Loop over test cases.
    foreach ($this->settingsTestCases as $settings_test_case) {
      $invalid_values = [];
      $this->drupalGet('admin/structure/types/manage/test_page/display/teaser');
      $input = $settings_test_case['input'];

      foreach ($input as $setting_key => $setting_value) {
        switch ($setting_key) {
          case 'smart_title__tag':
            if (!isset(_smart_title_tag_options()[$input['smart_title__tag']])) {
              $invalid_values[] = $setting_key;
            }
            break;
        }
      }

      // Open Smart Title settings edit.
      $this->click('[name="smart_title_settings_edit"]');

      if (!empty($invalid_values)) {
        // Test that exception is thrown.
        try {
          $this->drupalPostForm(NULL, [
            "fields[smart_title][settings_edit_form][settings][smart_title__tag]" => $input['smart_title__tag'],
            "fields[smart_title][settings_edit_form][settings][smart_title__classes]" => $input['smart_title__classes'],
            "fields[smart_title][settings_edit_form][settings][smart_title__link]" => $input['smart_title__link'],
          ], 'Save');
          $this->fail('Expected exception has not been thrown.');
        }
        catch (\Exception $e) {
          $this->pass('Expected exception has been thrown.');
        }

        // Let's save the other values.
        $edit = [];

        foreach ($input as $key => $value) {
          if (in_array($key, $invalid_values)) {
            continue;
          }
          $edit["fields[smart_title][settings_edit_form][settings][$key]"] = $value;
        }

        $this->drupalPostForm(NULL, $edit, 'Save');
      }
      else {
        $this->drupalPostForm(NULL, [
          "fields[smart_title][settings_edit_form][settings][smart_title__tag]" => $input['smart_title__tag'],
          "fields[smart_title][settings_edit_form][settings][smart_title__classes]" => $input['smart_title__classes'],
          "fields[smart_title][settings_edit_form][settings][smart_title__link]" => $input['smart_title__link'],
        ], 'Save');
      }

      // Verify saved settings.
      $this->assertSmartTitleExpectedConfigs($settings_test_case['expected']);

      // Re-save form again.
      $this->drupalGet('admin/structure/types/manage/test_page/display/teaser');
      $this->drupalPostForm(NULL, [], 'Save');

      // Verify saved settings again.
      $this->assertSmartTitleExpectedConfigs($settings_test_case['expected']);
    }
  }

  /**
   * Assert Smart Title expected configs.
   *
   * @param array $expected_settings
   *   Settings to verify (teaser view mode).
   */
  public function assertSmartTitleExpectedConfigs(array $expected_settings) {
    // Verify saved settings.
    $saved_settings = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.' . $this->testPageNode->getType() . '.teaser')
      ->getThirdPartySetting('smart_title', 'settings', []);
    $this->assertTrue($saved_settings === [
      'smart_title__tag' => $expected_settings['smart_title__tag'],
      'smart_title__classes' => $expected_settings['smart_title__classes'],
      'smart_title__link' => $expected_settings['smart_title__link'],
    ]);

    // Verify expected field settings summary.
    $web_assert = $this->assertSession();
    $web_assert->elementTextContains('css', '[data-drupal-selector="edit-fields-smart-title"] .field-plugin-summary', _smart_title_defaults('', NULL, 'smart_title__tag')['label'] . ': ' . $expected_settings['smart_title__tag']);
    // Css classes.
    if ((bool) $expected_settings['smart_title__classes']) {
      $web_assert->elementTextContains('css', '[data-drupal-selector="edit-fields-smart-title"] .field-plugin-summary', _smart_title_defaults('', NULL, 'smart_title__classes')['label'] . ': ' . implode(', ', $expected_settings['smart_title__classes']));
    }
    else {
      $web_assert->elementTextNotContains('css', '[data-drupal-selector="edit-fields-smart-title"] .field-plugin-summary', _smart_title_defaults('', NULL, 'smart_title__classes')['label']);
    }
    // Link.
    if ((bool) $expected_settings['smart_title__link']) {
      $web_assert->elementTextContains('css', '[data-drupal-selector="edit-fields-smart-title"] .field-plugin-summary', _smart_title_defaults('', NULL, 'smart_title__link')['label']);
    }
    else {
      $web_assert->elementTextNotContains('css', '[data-drupal-selector="edit-fields-smart-title"] .field-plugin-summary', _smart_title_defaults('', NULL, 'smart_title__link')['label']);
    }

    // Test that Smart Title is displayed on the /node page (teaser view mode)
    // for admin user.
    $this->drupalGet('node');
    $this->assertSession()->pageTextContains($this->testPageNode->label());
    $css_selector_compontents = $expected_settings['smart_title__classes'];
    array_unshift($css_selector_compontents, $expected_settings['smart_title__tag']);
    $article_title = $this->xpath($this->cssSelectToXpath('article ' . implode('.', $css_selector_compontents)));
    $this->assertEquals($this->testPageNode->label(), $article_title[0]->getText());
  }

  /**
   * Settings test cases.
   *
   * @var string[][]
   */
  protected $settingsTestCases = [
    'No class, no link' => [
      'input' => [
        'smart_title__tag' => 'span',
        'smart_title__classes' => '',
        'smart_title__link' => 0,
      ],
      'expected' => [
        'smart_title__tag' => 'span',
        'smart_title__classes' => [],
        'smart_title__link' => FALSE,
      ],
    ],
    'Single class without link' => [
      'input' => [
        'smart_title__tag' => 'h3',
        'smart_title__classes' => 'smart-title__test',
        'smart_title__link' => 0,
      ],
      'expected' => [
        'smart_title__tag' => 'h3',
        'smart_title__classes' => ['smart-title__test'],
        'smart_title__link' => FALSE,
      ],
    ],
    'Multiple classes, link' => [
      'input' => [
        'smart_title__tag' => 'div',
        'smart_title__classes' => 'smart-title__test with   multiple classes  and space',
        'smart_title__link' => 1,
      ],
      'expected' => [
        'smart_title__tag' => 'div',
        'smart_title__classes' => [
          'smart-title__test',
          'with',
          'multiple',
          'classes',
          'and',
          'space',
        ],
        'smart_title__link' => TRUE,
      ],
    ],
    'Invalid tag and link values' => [
      'input' => [
        'smart_title__tag' => 'invalid',
        'smart_title__classes' => 'valid',
        'smart_title__link' => 'invalid',
      ],
      'expected' => [
        // Title tag will be the same as the previous submission.
        'smart_title__tag' => 'div',
        'smart_title__classes' => ['valid'],
        'smart_title__link' => TRUE,
      ],
    ],
  ];

}
