<?php

namespace Drupal\sumoselect;

use Drupal\Core\Render\BubbleableMetadata;

class RequestPathChecker {

  /** @var \Drupal\Core\Condition\ConditionManager $conditionManager */
  private $conditionManager;

  /**
   * RequestPathChecker constructor.
   * @param \Drupal\Core\Condition\ConditionManager $conditionManager
   */
  public function __construct(\Drupal\Core\Condition\ConditionManager $conditionManager) {
    $this->conditionManager = $conditionManager;
  }

  /**
   * @param string $patterns
   *   Page patterns.
   * @param \Drupal\Core\Render\BubbleableMetadata|null &$bubbleableMetaData
   *   Return or merge metadata.
   * @param bool $negate
   *   Negate the condition.
   * @return bool
   *   Condition result.
   */
  function checkPatterns($patterns, &$bubbleableMetaData = NULL, $negate = FALSE) {
    if (!$bubbleableMetaData) {
      $bubbleableMetaData = new BubbleableMetadata();
    }
    /** @var \Drupal\Core\Condition\ConditionInterface $pathCondition */
    $pathCondition = $this->conditionManager->createInstance('request_path', [
      'negate' => $negate,
      'pages' => $patterns
    ]);
    $bubbleableMetaData->merge(BubbleableMetadata::createFromRenderArray(['#cache' => ['contexts' => $pathCondition->getCacheContexts()]]));
    // Work around a bug that returns true on empty patterns.
    $result = $patterns ? $pathCondition->evaluate() : $negate;
    return $result;
  }

  /**
   * @param string $patterns
   *   Page patterns.
   * @param \Drupal\Core\Render\BubbleableMetadata|null &$bubbleableMetaData
   *   Return or merge metadata.
   * @param bool $negate
   *   Negate the condition.
   * @return bool
   *   Condition result.
   */
  function checkExcludedPatterns($patterns, &$bubbleableMetaData = NULL, $negated = FALSE) {
    return $this->checkPatterns($patterns, $bubbleableMetaData, !$negated);
  }

}
