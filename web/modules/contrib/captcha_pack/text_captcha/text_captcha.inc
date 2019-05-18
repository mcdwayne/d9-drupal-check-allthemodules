<?php

/**
 * @file
 * Contains general functionality of the module.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Helper function for splitting a string on white spaces.
 *
 * Using explode(' ', $string) is not enough because it returns empty elements
 * if $string contains consecutive spaces.
 */
function _text_captcha_whitespace_explode($string) {
  return preg_split('/\s+/', $string, -1, PREG_SPLIT_NO_EMPTY);
}

/**
 * Helper function for splitting an utf8 string correctly in characters.
 *
 * Assumes the given utf8 string is well formed.
 * See http://en.wikipedia.org/wiki/Utf8 for more info.
 */
function _text_captcha_utf8_split($str) {
  $characters = array();
  $len = strlen($str);
  for ($i = 0; $i < $len;) {
    $chr = ord($str[$i]);
    // One byte character (0zzzzzzz)
    if (($chr & 0x80) == 0x00) {
      $width = 1;
    }
    else {
      // Two byte character (first byte: 110yyyyy)
      if (($chr & 0xE0) == 0xC0) {
        $width = 2;
      }
      // Three byte character (first byte: 1110xxxx)
      elseif (($chr & 0xF0) == 0xE0) {
        $width = 3;
      }
      // Four byte character (first byte: 11110www)
      elseif (($chr & 0xF8) == 0xF0) {
        $width = 4;
      }
      else {
        \Drupal::logger('CAPTCHA')
          ->error('Encountered an illegal byte while splitting an utf8 string in characters.');
        return $characters;
      }
    }
    $characters[] = substr($str, $i, $width);
    $i += $width;
  }
  return $characters;
}

/**
 * Helper function for getting the content of a word pool locale dependent.
 */
function _text_captcha_word_pool_get_content($name_base, $lang_code, $default_value, $explode = FALSE) {
  switch ($name_base) {
    case 'phrase_captcha_userdefined_word_pool':
      $config = \Drupal::config('phrase_captcha.settings');
      break;

    case 'lost_character_captcha_word_pool':
      $config = \Drupal::config('lost_character_captcha.settings');
      break;

    case 'word_list_captcha_word_pool_1':
    case 'word_list_captcha_word_pool_2':
      $config = \Drupal::config('word_list_captcha.settings');
      break;
  }
  if (\Drupal::moduleHandler()->moduleExists('locale')) {
    if (!$lang_code) {
      global $language;
      $lang_code = $language->language;
    }
    $content = !empty($config->get("{$name_base}_{$lang_code}")) ? $config->get("{$name_base}_{$lang_code}") : $default_value;
  }
  else {
    $content = $config->get($name_base);
  }
  if ($explode) {
    $content = _text_captcha_whitespace_explode($content);
  }
  return $content;
}

/**
 * Helper function for setting word pool form items locale dependent.
 */
function _text_captcha_word_pool_form_items(&$form, $name_base, $title, $description, $default_value, $rows = 3) {
  if (Drupal::moduleHandler()->moduleExists('locale')) {
    // Locale available.
    $langs = \Drupal::languageManager()->getLanguages();
    $form[$name_base] = array(
      '#type' => 'fieldset',
      '#title' => t($title),
      '#description' => t($description),
      '#collapsible' => TRUE,
    );
    foreach ($langs as $lang_code => $lang_name) {
      $form[$name_base]["{$name_base}_{$lang_code}"] = array(
        '#type' => 'textarea',
        '#title' => t('For language %lang_name (code %lang_code)', array('%lang_name' => $lang_name, '%lang_code' => $lang_code)),
        '#default_value' => _text_captcha_word_pool_get_content($name_base, $lang_code, $default_value),
        '#rows' => $rows,
      );
    }
  }
  else {
    // No locale available.
    $form[$name_base] = array(
      '#type' => 'textarea',
      '#title' => t($title),
      '#description' => t($description),
      '#default_value' => _text_captcha_word_pool_get_content($name_base, NULL, $default_value),
      '#rows' => $rows,
    );
  }
}

/**
 * Validates word length.
 *
 * @param string $name
 *   Field name.
 * @param array $words
 *   Array of words.
 * @param int $minimum_length
 *   Minimal length.
 * @param string $too_short_message
 *   Message to be shown on validation.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Form state.
 */
function _text_captcha_word_pool_validate_word_length($name, array $words, $minimum_length, $too_short_message, FormStateInterface $form_state) {
  $too_short = [];
  foreach ($words as $word) {
    if (count(_text_captcha_utf8_split($word)) < $minimum_length) {
      $too_short[] = $word;
    }
  }
  if (count($too_short)) {
    $form_state->setErrorByName($name, t($too_short_message, [
      '@minimum_length' => $minimum_length,
      '@words' => implode(', ', $too_short),
    ]));
  }
}

/**
 * Helper function for validating the word pool locale dependent.
 *
 * @param string $name_base
 *   Field name.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Form state.
 * @param int $minimum_count
 *   Minimal count.
 * @param int $minimum_length
 *   Minimal length.
 * @param string $too_short_message
 *   Message to be shown on validating input.
 */
function _text_captcha_word_pool_validate($name_base, FormStateInterface $form_state, $minimum_count, $minimum_length = NULL, $too_short_message = '') {
  $form_values = $form_state->getValues();
  if (Drupal::moduleHandler()->moduleExists('locale')) {
    $langs = \Drupal::languageManager()->getLanguages();
    foreach ($langs as $lang_code => $lang_name) {
      $words = _text_captcha_whitespace_explode($form_values["{$name_base}_{$lang_code}"]);
      if (count($words) < $minimum_count) {
        $form_state->setErrorByName("{$name_base}_{$lang_code}", t('You should provide at least @num words', array('@num' => $minimum_count)));
      }
      // Check the minimum word length (if needed)
      if ($minimum_length) {
        _text_captcha_word_pool_validate_word_length("{$name_base}_{$lang_code}", $words, $minimum_length, $too_short_message, $form_state);
      }
    }
  }
  else {
    $words = _text_captcha_whitespace_explode($form_values[$name_base]);
    if (count($words) < $minimum_count) {
      $form_state->setErrorByName($name_base, t('You should provide at least @num words', array('@num' => $minimum_count)));
    }
    if ($minimum_length) {
      _text_captcha_word_pool_validate_word_length($name_base, $words, $minimum_length, $too_short_message, $form_state);
    }
  }
}
