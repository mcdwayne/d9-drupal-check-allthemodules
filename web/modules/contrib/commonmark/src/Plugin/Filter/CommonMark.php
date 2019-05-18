<?php
/**
 * @file
 * Contains \Drupal\commonmark\Plugin\Filter\CommonMark.
 */

namespace Drupal\commonmark\Plugin\Filter;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to process CommonMark Markdown to HTML.
 *
 * @Filter(
 *   id = "commonmark",
 *   title = @Translation("Convert Markdown to HTML with CommonMark"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "show_examples" = TRUE,
 *     "use_global_settings" = TRUE
 *   }
 * )
 */
class CommonMark extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['show_examples'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show examples in the "long" filter tips.'),
      '#default_value' => $this->settings['show_examples'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Convert to HTML.
    $html = commonmark_convert_to_html($text);

    return new FilterProcessResult($html);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    // Yet to be ported.
    return '';
  }
}
