<?php

namespace Drupal\Tests\webform_score\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\webform\Plugin\WebformElementInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_score\HookService;
use Drupal\webform_score\QuizInterface;

/**
 * Tests webform_score pre save hook for webform_submission entity type.
 *
 * @group webform_score
 */
class WebformSubmissionPreSaveTest extends UnitTestCase {

  /**
   * Tests the pre save hook logic.
   *
   * @param string $message
   *   Assertion message to use for this test case.
   *
   * @dataProvider providerPreSaveHook()
   */
  public function testPreSaveHook(array $elements, array $expected_score, $message) {
    $element_manager = $this->getMockBuilder(WebformElementManagerInterface::class)
      ->getMock();
    $element_manager->method('getElementPluginId')->willReturnCallback(function($element) {
      return $element['#type'];
    });

    $correct_quiz_plugin = $this->getMockBuilder(QuizInterface::class)
      ->getMock();
    $correct_quiz_plugin->method('getMaxScore')->willReturn(1);
    $correct_quiz_plugin->method('score')->willReturn(1);

    $wrong_quiz_plugin = $this->getMockBuilder(QuizInterface::class)
      ->getMock();
    $wrong_quiz_plugin->method('getMaxScore')->willReturn(1);
    $wrong_quiz_plugin->method('score')->willReturn(0);

    $non_quiz_plugin = $this->getMockBuilder(WebformElementInterface::class)
      ->getMock();

    $element_manager->method('createInstance')->willReturnMap([
      ['webform_score_correct_quiz', [], $correct_quiz_plugin],
      ['webform_score_wrong_quiz', [], $wrong_quiz_plugin],
      ['webform_score_non_quiz', [], $non_quiz_plugin],
    ]);

    $webform = $this->getMockBuilder(WebformInterface::class)
      ->getMock();

    $webform->method('getElementsInitializedAndFlattened')->willReturn($elements);

    $webform_submission = $this->getMockBuilder(WebformSubmissionInterface::class)
      ->getMock();
    $webform_submission->method('getWebform')->willReturn($webform);
    $webform_submission->webform_score = (object) [
      'numerator' => rand(0, 99),
      'denominator' => rand(1, 99),
    ];

    $hook_service = new HookService($element_manager);
    $hook_service->webformSubmissionPreSave($webform_submission);

    $this->assertEquals($expected_score[0], $webform_submission->webform_score->numerator, 'Score is correct for ' . $message);
    $this->assertEquals($expected_score[1], $webform_submission->webform_score->denominator, 'Maximum score is correct for ' . $message);
  }

  /**
   * Data provider for testPreSaveHook().
   *
   * @see testPreSaveHook()
   */
  public function providerPreSaveHook() {
    $tests = [];

    $known_elements = [
      'webform_score_non_quiz' => [0, 0],
      'webform_score_correct_quiz' => [1, 1],
      'webform_score_wrong_quiz' => [0, 1],
    ];

    // Generate all possible combinations.
    $combinations = [[]];
    for ($i = 1; $i < count($known_elements); $i++) {
      $combinations = array_merge($combinations, $this->generateCombinations(array_keys($known_elements), $i));
    }

    foreach ($combinations as $combination) {
      $elements = [];
      $score = [0, 0];

      foreach ($combination as $item) {
        $elements[$item] = ['#type' => $item];
        $score[0] += $known_elements[$item][0];
        $score[1] += $known_elements[$item][1];
      }

      $tests[] = [$elements, $score, implode($combination, ', ')];
    }

    return $tests;
  }

  /**
   * Generate all possible combinations of given length from a given set.
   *
   * @param array $set
   *   Set of items to generate combinations from.
   * @param int $length
   *   Length of combinations to generate
   * @param array $combinations
   *   Array of combinations generated so far
   *
   * @return array
   *   Array of all possible combinations of a specified length generated from a
   *   given set
   */
  protected function generateCombinations($set, $length, $combinations = []) {
    if (empty($combinations)) {
      foreach ($set as $item) {
        $combinations[] = [$item];
      }
    }

    if ($length == 1) {
      return $combinations;
    }

    $new_combinations = array();

    foreach ($combinations as $combination) {
      foreach ($set as $item) {
        if (!in_array($item, $combination)) {
          $combination[] = $item;
          $new_combinations[] = $combination;
        }
      }
    }

    return $this->generateCombinations($set, $length - 1, $new_combinations);
  }

}
