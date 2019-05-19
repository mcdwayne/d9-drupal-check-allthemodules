<?php

namespace Drupal\term_name_validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Html;

/**
 * Validates the TermNameValidate constraint.
 */
class TermNameConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $term_name = $items->first()->value;
    $vocab_id = $items->getEntity()->bundle();
    $tid = $items->getEntity()->id() ? $items->getEntity()->id() : '';

    // Get configuration value.
    $term_name_validation_config = \Drupal::config('term_name_validation.settings');

    if ($term_name_validation_config) {
      // Get type unique values for current term type.
      $type_unique = $term_name_validation_config->get('unique-' . $vocab_id) ? $term_name_validation_config->get('unique-' . $vocab_id) : '';

      // Get common unique value for all term type.
      $unique = $term_name_validation_config->get('unique') ? $term_name_validation_config->get('unique') : '';

      // Check unique OR type unique checkbox field.
      if (!empty($type_unique) || !empty($unique)) {
        // Check existing same term name.
        if (term_name_validation_unique_term($term_name, $vocab_id, $tid)) {
          $this->context->addViolation("The term %term already exists in this vocabulary!.", ['%term' => Html::escape($term_name)]);
        }
      }

      // Validating excluded characters in the Term Name.
      $type_exclude = $term_name_validation_config->get('exclude-' . $vocab_id) ? $term_name_validation_config->get('exclude-' . $vocab_id) : '';
      if (!empty($type_exclude)) {
        // Replace \r\n with comma.
        $type_exclude = str_replace("\r\n", ',', $type_exclude);
        // Store into array.
        $type_exclude = explode(',', $type_exclude);
        // Find any exclude value found in node title.
        $findings = _term_name_validation_search_excludes_in_title($term_name, $type_exclude);
        if ($findings) {
          $this->context->addViolation("The characters/words are not allowed to enter in the title. - @findings", ['@findings' => implode(',', $findings)]);
        }
      }

      // Validating minimum characters in the Term Name.
      $type_min_chars = $term_name_validation_config->get('min-' . $vocab_id) ? $term_name_validation_config->get('min-' . $vocab_id) : '';
      if (!empty($type_min_chars)) {
        if (Unicode::strlen($term_name) < $type_min_chars) {
          $this->context->addViolation("Title should have minimum @num characters", ['@num' => $type_min_chars]);
        }
      }

      // Validating maximum characters in the Term Name.
      $type_max_chars = $term_name_validation_config->get('max-' . $vocab_id) ? $term_name_validation_config->get('max-' . $vocab_id) : '';
      if (!empty($type_max_chars)) {
        if (Unicode::strlen($term_name) > $type_max_chars) {
          $this->context->addViolation("Title should not exceed @num characters", ['@num' => $type_max_chars]);
        }
      }

      // Validating Minimum Word Count in the Term Name.
      $type_min_wc = $term_name_validation_config->get('min-wc-' . $vocab_id) ? $term_name_validation_config->get('min-wc-' . $vocab_id) : '';
      if (!empty($type_min_wc)) {
        if (str_word_count($term_name) < $type_min_wc) {
          $this->context->addViolation("Term Name should have minimum word count of @num", ['@num' => $type_min_wc]);
        }
      }

      // Validating Maximum Word Count in the Term Name.
      $type_max_wc = $term_name_validation_config->get('max-wc-' . $vocab_id) ? $term_name_validation_config->get('max-wc-' . $vocab_id) : '';
      if (!empty($type_max_wc)) {
        if (str_word_count($term_name) > $type_max_wc) {
          $this->context->addViolation("Term Name should not exceed word count of @num", ['@num' => $type_max_wc]);
        }
      }
    }
  }

}
