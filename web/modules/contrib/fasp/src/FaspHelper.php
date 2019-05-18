<?php

namespace Drupal\fasp;

use Drupal\Component\Utility\Unicode;

/**
 * {@inheritdoc}
 */
class FaspHelper {

  /**
   * Check is provided form id is match any of patterns.
   */
  public function formIdMatch($form_id, $patterns) {
    // Replace new lines by "|" and wildcard by ".*".
    $to_replace = [
      '/(\r\n?|\n)/',
      '/\\\\\*/',
    ];

    $replacements = [
      '|',
      '.*',
    ];

    $patterns_quoted = preg_quote($patterns, '/');
    $pattern = '/^(' . preg_replace($to_replace, $replacements, $patterns_quoted) . ')$/';
    preg_match($pattern, $form_id, $matches);
    return $matches;
  }

  /**
   * Random select fake element input name and cache it.
   *
   * @todo find more elegant solution without caching, sessions, cookies and
   *   hidden inputs. Form storage can't handle dynamic data, so this is the
   *   only working solution without traces to detect it.
   */
  public function getInputNameRandom() {
    $config_advanced = \Drupal::config('fasp.settings.advanced');
    $input_names = $config_advanced->get('input_names');
    $input_names_array = explode(PHP_EOL, $input_names);
    $fasp_random_input_name = trim(preg_replace('/\s\s+/', '', $input_names_array[array_rand($input_names_array)]));
    return $fasp_random_input_name;
  }

  /**
   * Return generated fake input name for current time.
   */
  public function getInputName() {
    $input_name = \Drupal::state()->get('fasp_input_name');
    if ($input_name) {
      return $input_name;
    }
    else {
      $input_name = $this->getInputNameRandom();
      \Drupal::state()->set('fasp_input_name', $input_name);
      return $input_name;
    }
  }

  /**
   * Return random selected class for input element.
   */
  public function getInputClassRandom() {
    $input_classes_array = $this->getInputClasses();
    return $input_classes_array[array_rand($input_classes_array)];
  }

  /**
   * Return all classes as array cleaned from empty new lines and whitespaces.
   */
  public function getInputClasses() {
    $config_advanced = \Drupal::config('fasp.settings.advanced');
    $input_classes = $config_advanced->get('input_classes');
    $input_classes_array = explode(PHP_EOL, $input_classes);
    foreach ($input_classes_array as $key => &$class) {
      if (strlen($class)) {
        $class = trim(preg_replace('/\s\s+/', '', $class));
      }
      else {
        unset($input_classes_array[$key]);
      }
    }
    return $input_classes_array;
  }

  /**
   * Return simple title generated from input name.
   */
  public function getInputTitle() {
    $input_name = $this->getInputName();
    return str_replace('_', ' ', Unicode::ucfirst($input_name));
  }

  /**
   * Return patterns for matching.
   */
  public function getFormPattern() {
    $config_forms = \Drupal::config('fasp.settings.forms');
    $forms = $config_forms->get('forms');
    $form_ids = explode(PHP_EOL, $forms);
    if ($config_forms->get('exclude_views_exposed_forms')) {
      $form_ids[] = 'views_exposed_form';
    }
    $pattern = '';
    foreach ($form_ids as $v) {
      $pattern .= $v . PHP_EOL;
    }
    return $pattern;
  }

  /**
   * Return selected by user match type for forms.
   */
  public function getMatchType() {
    $config_forms = \Drupal::config('fasp.settings.forms');
    return $config_forms->get('match_type');
  }

}
