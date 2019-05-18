<?php

namespace Drupal\chatbot_api_apiai;

use DialogFlow\Model\Context;
use Drupal\Component\Utility\Unicode;

/**
 * Trait ApiAiContextTrait.
 *
 * API.ai supports context parameters. Intents can get/set parameters by
 * separating the context name and the parameter name with a period i.e.
 * "context_name.parameter_name". This trait will provide the method to extract
 * context and parameter names.
 *
 * @package Drupal\chatbot_api_apiai
 */
trait ApiAiContextTrait {

  /**
   * Get a context name.
   *
   * This method will exclude the parameter segment from a context (invocation)
   * name, i.e. getContextName("context_name.parameter_name") returns
   * "context_name".
   *
   * @param string $context_name
   *   The full context name syntax. It may include the parameter part.
   *
   * @return string
   *   The context name.
   */
  public function getContextName($context_name) {
    return strpos($context_name, '.') !== FALSE ? explode('.', $context_name)[0] : $context_name;
  }

  /**
   * Get a context parameter name.
   *
   * This method will extract the parameter name
   * if present in the context_name, otherwise will use the default parameter
   * name.
   *
   * @param string $context_name
   *   The full context name syntax. It may include the parameter part.
   *
   * @return string
   *   The parameter name, or the default 'value' name if no parameter part is
   *   found.
   */
  public function getParameterName($context_name) {
    return strpos($context_name, '.') !== FALSE ? explode('.', $context_name)[1] : 'value';
  }

  /**
   * Massage context name value to be DialogFlow-compatible.
   *
   * DialogFlow context names are case-insensitive and when sent back by the
   * request they are all lowercase, so because chatbot_api allows
   * case-sensitive contexts names (i.e. MyIntentIterator) we need to massage
   * their values before doing any search.
   *
   * @param string $context_name
   *   The context name, can be case-sensitive.
   *
   * @return string
   *   The context name, massaged and ready to work with DialogFlow contexts.
   *
   * @see https://dialogflow.com/docs/contexts#adding_contexts
   */
  public function massageContextName($context_name) {
    return Unicode::strtolower($context_name);
  }

  /**
   * Check if context name matches the provided string.
   *
   * @param \DialogFlow\Model\Context $context
   *   The context element we need to process.
   * @param string $context_name
   *   The string to match.
   *
   * @return bool
   *   TRUE if context name matches with provided $context_name string, FALSE
   *   otherwise. When doing the match values are massaged with
   *   massageContextName() method.
   */
  public function contextNameIs(Context $context, $context_name) {
    return $this->massageContextName($context->getName()) === $this->massageContextName($context_name);
  }

}
