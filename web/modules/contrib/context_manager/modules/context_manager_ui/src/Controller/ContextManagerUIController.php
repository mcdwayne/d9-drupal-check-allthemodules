<?php

namespace Drupal\context_manager_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Utility\Html;

/**
 * Returns responses for Context Manager UI routes.
 */
class ContextManagerUIController extends ControllerBase {

  /**
   * Menu callback for Context Ruleset tag autocompletion.
   *
   * Like other autocomplete functions, this function inspects the 'q' query
   * parameter for the string to use to search for suggestions.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for Views tags.
   */
  public function autocompleteTag(Request $request) {
    $matches = array();
    $string = $request->query->get('q');

    // Get matches from all rulesets.
    $rulesets =  $this->entityTypeManager()->getStorage('context_ruleset')->loadMultiple();

    $tags = [];
    foreach ($rulesets as $ruleset) {
      $tag = $ruleset->get('tag');
      // Keep track of previously processed tags so they can be skipped.
      if ($tag && !in_array($tag, $tags)) {
        $tags[] = $tag;
        if (strpos($tag, $string) === 0) {
          $matches[] = ['value' => $tag, 'label' => Html::escape($tag)];
          if (count($matches) >= 10) {
            break;
          }
        }
      }
    }

    return new JsonResponse($matches);
  }

}
