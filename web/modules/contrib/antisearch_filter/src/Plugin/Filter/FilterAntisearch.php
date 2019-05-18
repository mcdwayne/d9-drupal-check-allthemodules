<?php

namespace Drupal\antisearch_filter\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;

/**
 * Provides a filter to limit allowed HTML tags.
 *
 * @Filter(
 *   id = "filter_antisearch",
 *   module = "antisearch_filter",
 *   title = @Translation("Antisearch filter"),
 *   description = @Translation("Hide text from search engines like Google. The filter adds random characters between the single characters of the text."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = {
 *     "antisearch_filter_email" = 1,
 *     "antisearch_filter_strike" = 1,
 *     "antisearch_filter_bracket" = 1,
 *     "antisearch_filter_show_title" = 1,
 *   },
 *   weight = -99
 * )
 */
class FilterAntisearch extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['antisearch_filter_email'] = [
      '#type' => 'checkbox',
      '#title' => t('Apply to e-mail adresses.'),
      '#default_value' => $this->settings['antisearch_filter_email'],
      '#description' => t('Apply antisearch filter to e-mail addresses (e. g. foo@bar.com).'),
    ];

    $form['antisearch_filter_strike'] = [
      '#type' => 'checkbox',
      '#title' => t('Apply to HTML strike tags.'),
      '#default_value' => $this->settings['antisearch_filter_strike'],
      '#description' => t('Apply antisearch filter to text surrounded by html strike tags (e. g. &lt;strike&gt;foo bar&lt;/strike&gt;).'),
    ];

    $form['antisearch_filter_bracket'] = [
      '#type' => 'checkbox',
      '#title' => t('Apply to square brackets.'),
      '#default_value' => $this->settings['antisearch_filter_bracket'],
      '#description' => t('Apply antisearch filter to text surrounded by square brackets (e. g. [foo bar]).'),
    ];

    $form['antisearch_filter_show_title'] = [
      '#type' => 'checkbox',
      '#title' => t('Show description.'),
      '#default_value' => $this->settings['antisearch_filter_show_title'],
      '#description' => t('Show description.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // E-mail address.
    if ($this->settings['antisearch_filter_email']) {
      $text = preg_replace("'<a.*mailto.*>(.*)</a>'Uis", '\\1', $text);
      $text = preg_replace_callback("`([A-Za-z0-9._-]+@[A-Za-z0-9._+-]+\.[A-Za-z]{2,4})`i", 'antisearch_filter', $text);
    }

    // HTML strike tags: <strike> </strike> or <s> </s>.
    if ($this->settings['antisearch_filter_strike']) {
      $text = preg_replace_callback("#<strike>(.*)</strike>#U", 'antisearch_filter', $text);
      $text = preg_replace_callback("#<s>(.*)</s>#U", 'antisearch_filter', $text);
    }

    // Square brackets [ ].
    if ($this->settings['antisearch_filter_bracket']) {
      $text = preg_replace_callback("/\[(.*)\]/U", 'antisearch_filter', $text);
    }

    if (!$this->settings['antisearch_filter_show_title']) {
      $text = preg_replace("#<span class=\"antisearch-filter\".*>#Uis", '<span class="antisearch-filter">', $text);
    }

    $result = new FilterProcessResult($text);
    $result->setAttachments([
      'library' => ['antisearch_filter/antisearch-filter'],
    ]);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $to = [];

    // E-mail address.
    if ($this->settings['antisearch_filter_email'] == TRUE) {
      $to[] = t('to e-mail addresses');
    }

    // HTML strike tags: <strike> </strike>.
    if ($this->settings['antisearch_filter_strike'] == TRUE) {
      $to[] = t('to any text surrounded by HTML strike tags');
    }

    // Square brackets [ ].
    if ($this->settings['antisearch_filter_bracket'] == TRUE) {
      $to[] = t('to any text surrounded by square brackets');
    }

    return t('The antisearch filter will be applied') . ' ' . implode(', ', $to) . '.';
  }

}
