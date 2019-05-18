<?php

namespace Drupal\shortcode\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Filter that corrects html added by wysiwyg editors around shortcodes.
 *
 * @Filter(
 *   id = "shortcode_corrector",
 *   module = "shortcode",
 *   title = @Translation("Shortcodes - html corrector"),
 *   description = @Translation("Trying to correct the html around shortcodes. Enable only if you using wysiwyg editor."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class ShortcodeCorrector extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if (!empty($text)) {
      /** @var \Drupal\shortcode\Shortcode\ShortcodeService $shortcodeEngine */
      $shortcodeEngine = \Drupal::service('shortcode');
      $text = $shortcodeEngine->postprocessText($text, $langcode, $this);
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {

    // Get enabled shortcodes for a specific text format.
    // Drupal 7 way:
    // $shortcodes = shortcode_list_all_enabled($format);
    // Drupal 8 way:
    /** @var \Drupal\shortcode\Shortcode\ShortcodePluginManager $type */
    $type = \Drupal::service('plugin.manager.shortcode');
    $shortcodes = $type->getDefinitions();

    // Gather tips defined in all enabled plugins.
    $tips = [];

    foreach ($shortcodes as $plugin_id => $shortcode_info) {
      /** @var \Drupal\shortcode\Plugin\ShortcodeInterface $shortcode */
      $shortcode = $type->createInstance($plugin_id);
      $tips[] = $shortcode->tips($long);

    }

    $output = '';
    foreach ($tips as $tip) {
      $output .= '<li>' . $tip . '</li>';
    }
    return '<p>You can use wp-like shortcodes such as: </p><ul>' . $output . '</ul>';
  }

}
