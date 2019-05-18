<?php

namespace Drupal\fasp;

/**
 * Class GenerateStyles.
 *
 * Generate CSS stylesheet from settings and write it to file.
 */
class FaspStyleGenerator {

  /**
   * {@inheritdoc}
   */
  public function generate() {
    $settings = $this->getSettings();
    $stylesheet_render_array = [
      '#theme' => 'fasp_stylesheets',
      '#selector' => $settings['selector'],
      '#style' => $settings['style'],
    ];
    $stylesheet_rendered = \Drupal::service('renderer')
      ->renderPlain($stylesheet_render_array);
    // Workaround for Issue #2672656. Which removed Twig debug comments on dev site.
    $stylesheet_rendered = trim(strip_tags($stylesheet_rendered));
    $file = $this->createFile($stylesheet_rendered);
    return $file;
  }

  /**
   * {@inheritdoc}
   */
  private function getSettings() {
    $config = \Drupal::config('fasp.settings.advanced');
    $fasp_helper = \Drupal::service('fasp.helper');
    $input_classes_array = $fasp_helper->getInputClasses();
    return [
      'selector' => $input_classes_array,
      'style' => $config->get('classes_style'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  private function createFile($content = '') {
    $file = FALSE;
    $dir = 'public://fasp';
    if (file_prepare_directory($dir, FILE_CREATE_DIRECTORY)) {
      $file = file_save_data($content, $dir . '/style.css', FILE_EXISTS_REPLACE);
      $file_usage = \Drupal::service('file.usage');
      $file_usage->add($file, 'fasp', 'style', 'fasp');
      $file->save();
    }
    return $file;
  }

}
