<?php

namespace Drupal\url_filter\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to convert email addresses into mailto links.
 *
 * @Filter(
 *   id = "url_filter",
 *   title = @Translation("Convert URLs into links (configurable)"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "filter_url_length" = 72,
 *     "with_protocol" = true,
 *     "with_www" = true,
 *     "mails" = true,
 *     "ignore_tld_only" = false,
 *   }
 * )
 */
class UrlFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['filter_url_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum link text length'),
      '#default_value' => $this->settings['filter_url_length'],
      '#min' => 1,
      '#field_suffix' => $this->t('characters'),
      '#description' => $this->t('URLs longer than this number of characters will be truncated to prevent long strings that break formatting. The link itself will be retained; just the text portion of the link will be truncated.'),
    ];
    $form['with_protocol'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('URLs with a protocol'),
      '#default_value' => $this->settings['with_protocol'],
      '#description' => $this->t('Eg. <em>http://example.com/</em>'),
    ];
    $form['with_www'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('URLs starting with www'),
      '#default_value' => $this->settings['with_www'],
      '#description' => $this->t('Eg. <em>www.example.com/</em>'),
    ];
    $form['mails'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Email addresses'),
      '#default_value' => $this->settings['mails'],
      '#description' => $this->t('Eg. <em>test@example.com</em>'),
    ];
    $form['ignore_tld_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore TLD-only URLs and email addresses'),
      '#default_value' => $this->settings['ignore_tld_only'],
      '#description' => $this->t('TLD stands for top level domain. For example, <em>test@test</em> is a technically valid email address, and <em>http://test</em> is a technically valid URL. However, such addresses are not widely used. When this option is enabled, URLs and email addresses should have at least the second level domain.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult(url_filter_filter_url($text, $this));
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $typesTranslated = [];
    if ($this->settings['with_protocol']) {
      $typesTranslated[] = $this->t('links with a protocol');
    }
    if ($this->settings['with_www']) {
      $typesTranslated[] = $this->t('links starting with www');
    }
    if ($this->settings['mails']) {
      $typesTranslated[] = $this->t('email addresses');
    }
    if ($this->settings['ignore_tld_only']) {
      $typesTranslated[] = $this->t('excluding TLD-only domains');
    }

    $prefix = ucfirst(implode(', ', $typesTranslated));

    return $prefix . ' ' . $this->t('turn into links automatically.');
  }

}
