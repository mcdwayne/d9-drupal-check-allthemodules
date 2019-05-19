<?php

namespace Drupal\webform_score;

use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Various hook implementations encapsulated into a service for better testing.
 */
class HookService {

  /**
   * The webform element manager service.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  public function __construct(WebformElementManagerInterface $element_manager) {
    $this->elementManager = $element_manager;
  }

  /**
   * Implementation of hook_ENTITY_TYPE_presave().
   */
  public function webformSubmissionPreSave(WebformSubmissionInterface $webform_submission) {
    $score = [
      'maximum' => 0,
      'scored' => 0,
    ];
    foreach ($webform_submission->getWebform()->getElementsInitializedAndFlattened() as $element) {
      $element_plugin = $this->elementManager->createInstance($this->elementManager->getElementPluginId($element));
      if ($element_plugin instanceof \Drupal\webform_score\QuizInterface) {
        $score['maximum'] += $element_plugin->getMaxScore($element);
        $score['scored'] += $element_plugin->score($element, $webform_submission);
      }
    }

    $webform_submission->webform_score->numerator = $score['scored'];
    $webform_submission->webform_score->denominator = $score['maximum'];
  }

}
