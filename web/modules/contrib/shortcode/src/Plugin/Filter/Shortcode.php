<?php

namespace Drupal\shortcode\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter for insert view.
 *
 * @Filter(
 *   id = "shortcode",
 *   module = "shortcode",
 *   title = @Translation("Shortcodes"),
 *   description = @Translation("Provides WP like shortcodes to text formats."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class Shortcode extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    /** @var \Drupal\shortcode\ShortcodeService $shortcodeService */
    $shortcodeService = \Drupal::service('shortcode');
    $shortcodes = $shortcodeService->loadShortcodePlugins();

    $shortcodes_by_provider = [];

    // Group shortcodes by provider.
    foreach ($shortcodes as $shortcode_id => $shortcode_info) {
      $provider_id = $shortcode_info['provider'];
      if (!isset($shortcodes_by_provider[$provider_id])) {
        $shortcodes_by_provider[$provider_id] = [];
      }
      $shortcodes_by_provider[$provider_id][$shortcode_id] = $shortcode_info;
    }

    // Generate form elements.
    $settings = [];
    foreach ($shortcodes_by_provider as $provider_id => $shortcodes) {

      // Add section header.
      // todo: add Translate markup.
      $settings['header-' . $provider_id] = [
        '#markup' => '<b class="shortcodeSectionHeader">Shortcodes provided by ' . $provider_id . '</b>',
      ];

      // Sort definitions by weight property.
      $sorted_shortcodes = $shortcodes;
      uasort($sorted_shortcodes, function ($a, $b) {
        return $b['weight'] - $a['weight'];
      });

      /** @var \Drupal\shortcode\Plugin\ShortcodeInterface $shortcode */
      foreach ($sorted_shortcodes as $shortcode_id => $shortcode_info) {
        $settings[$shortcode_id] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable %name shortcode', ['%name' => $shortcode_info['title']]),
          '#default_value' => isset($this->settings[$shortcode_id]) ? $this->settings[$shortcode_id] : TRUE,
          '#description' => isset($shortcode_info['description']) ? $shortcode_info['description'] : $this->t('Enable or disable this shortcode in this input format'),
        ];
      }
    }
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if (!empty($text)) {
      /** @var \Drupal\shortcode\ShortcodeService $shortcodeEngine */
      $shortcodeEngine = \Drupal::service('shortcode');
      $text = $shortcodeEngine->process($text, $langcode, $this);
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {

    /** @var \Drupal\shortcode\ShortcodeService $type */
    $type = \Drupal::service('shortcode');

    // todo: it duplicates the tips section.
    // Get enabled shortcodes for this text format.
    $shortcodes = $type->getShortcodePlugins($this);
    /** @var \Drupal\shortcode\ShortcodePluginManager $type */
    $type = \Drupal::service('plugin.manager.shortcode');

    // Gather tips defined in all enabled plugins.
    $tips = [];
    foreach ($shortcodes as $shortcode_info) {
      /** @var \Drupal\shortcode\Plugin\ShortcodeInterface $shortcode */
      $shortcode = $type->createInstance($shortcode_info['id']);
      $tips[] = $shortcode->tips($long);
    }

    $output = '';
    foreach ($tips as $tip) {
      $output .= '<li>' . $tip . '</li>';
    }
    // todo: add render array instead of li/ul markup.
    // todo: add Translate markup.
    return '<p>You can use wp-like shortcodes such as: </p><ul>' . $output . '</ul>';
  }

}
