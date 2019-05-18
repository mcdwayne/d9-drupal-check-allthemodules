<?php

/**
 * @file
 * Contains \Drupal\content_callback_examples\Plugin\ContentCallback\Options.
 */

namespace Drupal\content_callback_examples\Plugin\ContentCallback;

use Drupal\Core\Annotation\Translation;
use Drupal\content_callback\Annotation\ContentCallback;
use Drupal\content_callback\Plugin\ContentCallback\PluginBase;

/**
 * A test callback
 *
 * @ContentCallback(
 *   id = "example_options",
 *   title = @Translation("Example with options"),
 *   has_options = TRUE
 * )
 */
class Options extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $options = $this->options;

    // Test outputting the saved textfield value
    $text = !empty($options['text']) ? $options['text'] : 'nothing';
    $build['textfield'] = array(
      '#markup' => 'Text chosen: ' . $text,
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    );

    // Test outputting the value of the checkbox
    if (!empty($options['checkbox'])) {
      $checkbox_string = 'on';
    }
    else {
      $checkbox_string = 'off';
    }
    $build['checkbox'] = array(
      '#markup' => 'Checkbox status: ' . $checkbox_string,
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsForm(array &$form, array $saved_options) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => 'Example textfield',
      '#default_value' => isset($saved_options['text']) ? $saved_options['text'] : '',
    );
    $form['checkbox'] = array(
      '#type' => 'checkbox',
      '#title' => 'Example checkbox',
      '#default_value' => isset($saved_options['checkbox']) ? $saved_options['checkbox'] : 0,
    );
  }
}
