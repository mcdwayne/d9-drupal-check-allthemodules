<?php

namespace Drupal\function_filter\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @Filter(
 *   id = "function_filter",
 *   title = @Translation("Function filter"),
 *   description = @Translation("Replace tokens [function:*] to function result."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = 20
 * )
 */
class FunctionFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $filter_result = new FilterProcessResult();

    $text = preg_replace_callback('/\[function:(.+?)\]/', function ($matches) use ($filter_result) {
      $function_info = function_filter_get_function_info_by_token_param($matches[1]);

      if (isset($function_info['file'])) {
        require_once $function_info['file'];
      }

      if (function_exists($function_info['function'])) {
        $result = call_user_func_array($function_info['function'], $function_info['arguments']);

        if (is_array($result)) {
          $filter_result->applyTo($result);
          $result = \Drupal::service('renderer')->render($result);
        }
        if (isset($function_info['cache']) && $function_info['cache'] === FALSE) {
          $filter_result->setCacheMaxAge(0);
        }

        return $result;
      }

      return '[function:' . $matches[1] . ']';
    }, $text);

    $filter_result->setProcessedText($text);

    return $filter_result;
  }

}
