<?php

namespace Drupal\gridheightnormalizer\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Class GridHeightSelectorForm.
 *
 * @package Drupal\gridheightnormalizer\Form
 *
 * @ingroup gridheightnormalizer
 */
class GridHeightSelectorForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'gridheightnormalizer_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['gridheightnormalizer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gridheightnormalizer.settings');
    $form['grid_height_equalizer_selectors'] = array(
      '#type' => 'textarea',
      '#title' => t('Add selectors'),
      '#default_value' => $config->get('grid_height_equalizer_selectors'),
      '#description' => t('Add the selectors which are to be normalized, one selector in a line. For eg: if you need to normalize the HTML elements with class "article-list", enter ".article-list" in the above form.'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check class selectors.
    $class_or_not = $form_state->getValue(array('grid_height_equalizer_selectors'));
    $invalid_class = $duplicate_class = $invalid_character = FALSE;
    if (!empty($class_or_not)) {
      $classes = preg_split('/\R/', $class_or_not);
      foreach ($classes as $key => $class_name) {
        // Check class selector starts with a dot.
        if (strpos($class_name, '.') !== 0 || (substr($class_name, -1) == '.')) {
          $invalid_class = TRUE;
        }
        // Check selectors has space.
        if (preg_match('/[ ]/', $class_name)) {
          $space_seperator = TRUE;
        }
        if (preg_match('/[\'\/~`\!@#\$%\^&\*\(\)_\+=\{\}\[\]\|;:"\<\>,\?\\\]/', $class_name)) {
          $invalid_character = TRUE;
        }
      }

      // Check and listout the duplicate class names added in the form.
      $error_classes = '';
      $classes_count = array_count_values($classes);
      foreach ($classes_count as $key => $value) {
        if ($value > 1) {
          $error_classes .= $key . ', ';
        }
      }

      $error_classes = rtrim($error_classes, ', ');
      if (!empty($error_classes)) {
        $duplicate_class = TRUE;
      }

      if ($duplicate_class == TRUE || $invalid_class == TRUE ||
        $space_seperator == TRUE || $invalid_character == TRUE) {
        $message = '';
        if ($duplicate_class == TRUE) {
          $message .= 'Duplicate selectors found :- ' . $error_classes . '.';
        }
        if ($invalid_class == TRUE) {
          $message .= ' Selectors should be a class name with a dot. For example ".article-list" .';
        }
        if ($space_seperator == TRUE) {
          $message .= ' Supports only one selector in a line without space.';
        }
        if ($invalid_character == TRUE) {
          $message .= ' Special characters are not allowed in the selectors except "." and "-".';
        }
        $form_state->setErrorByName('grid_height_equalizer_selectors', t('@message', ['@message' => $message]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('gridheightnormalizer.settings')
      ->set('grid_height_equalizer_selectors', $form_state->getValue(array('grid_height_equalizer_selectors')))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
