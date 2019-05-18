<?php

namespace Drupal\janrain_connect_ui\Service;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * JanrainConnect Form Class.
 */
class JanrainConnectUiTokenService {

  /**
   * Replaces all tokens in a given string with appropriate values.
   *
   * @param string $text
   *   The text containing replaceable tokens.
   *
   * @return string
   *   The text with tokens replaced.
   */
  public function replace($text) {
    $text = $this->replaceLink($text);

    return $text;
  }

  /**
   * Replaces all tokens link in a given string with appropriate values.
   *
   * @param string $text
   *   The text containing replaceable tokens.
   *
   * @return string
   *   The text with tokens link replaced.
   */
  public function replaceLink($text) {
    // Regex read [link]Label|URL[/link] and broke in two groups:
    // [link]Label|URL[/link] and Label|URL.
    preg_match_all("/\[link](.*?)\[\/link]/", $text, $matches);

    if (empty($matches[0])) {
      return $text;
    }

    $links = $matches[0];
    $links_settings = $matches[1];

    foreach ($links as $key => $link_token) {
      $link_settings = explode('|', $links_settings[$key]);
      // Label and URL are required.
      if (empty($link_settings[0]) || empty($link_settings[1])) {
        continue;
      }
      $link = Link::fromTextAndUrl($link_settings[0], Url::fromUserInput(
        $link_settings[1],
        [
          'attributes' => ['target' => '_blank'],
        ]
      ))->toString();
      $text = str_replace($link_token, $link, $text);
    }

    return $text;
  }

  /**
   * Get all name/value token values from message string using the form_state.
   *
   * @param string $message
   *   The message containing replaceable tokens. i.e.
   *   $message = "Your name is @fieldNameId and another @fieldAnotherFieldId".
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   *
   * @return array
   *   Array with token names and token values. i.e.:
   *   $tokens = [
   *     '@fieldNameId' => $form_state->getValue("fieldNameId"),
   *     '@fieldAnotherFieldId' => $form_state->getValue("fieldAnotherFieldId")
   *   ]
   */
  public function getMessageFormTokens(string $message, FormStateInterface $form_state) {
    // Regex read @fielId and create token array:
    // ['@fieldId' => $form_state->getValue("fieldId")].
    $tokens = [];
    $regex = '~(@\w+)~';
    if (preg_match_all($regex, $message, $matches, PREG_PATTERN_ORDER)) {
      foreach ($matches[1] as $token) {
        $token_name = (string) str_replace('@', '', $token);
        $token_value = (string) $form_state->getValue("$token_name");
        $tokens[$token] = $token_value;
      }
    }

    return $tokens;
  }

}
