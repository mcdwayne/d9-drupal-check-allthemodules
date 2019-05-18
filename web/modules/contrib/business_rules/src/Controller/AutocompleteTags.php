<?php

namespace Drupal\business_rules\Controller;

use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Entity\BusinessRule;
use Drupal\business_rules\Entity\Condition;
use Drupal\business_rules\Entity\Variable;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AutocompleteTags.
 *
 * @package Drupal\business_rules\Controller
 */
class AutocompleteTags extends ControllerBase {

  /**
   * Handler for autocomplete request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The matched values.
   */
  public function handleAutocomplete(Request $request) {
    $matches = [];
    $string = $request->query->get('q');
    // Get the current saved tags.
    $br_tags = BusinessRule::loadAllTags();
    $ac_tags = Action::loadAllTags();
    $co_tags = Condition::loadAllTags();
    $va_tags = Variable::loadAllTags();
    $tags    = array_merge($br_tags, $ac_tags, $co_tags, $va_tags);

    // Keep track of previously processed tags so they can be skipped.
    foreach ($tags as $tag) {
      if (strpos($tag, $string) === 0) {
        $matches[] = ['value' => $tag, 'label' => Html::escape($tag)];
        if (count($matches) >= 10) {
          break;
        }
      }
    }

    return new JsonResponse($matches);
  }

}
