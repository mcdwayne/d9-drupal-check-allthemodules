<?php

/**
 * @file
 * Contains Drupal\webpurify\Plugin\Filter\FilterWebpurifyProfanity
 */

namespace Drupal\webpurify\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a profanity filter.
 *
 * @Filter(
 *   id = "filter_webpurify_profanity",
 *   title = @Translation("WebPurify profanity filter"),
 *   description = @Translation("Replaces all the profanity words in the text."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FilterWebpurifyProfanity extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
	  $text = webpurify_replace_text($text);
    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Masks curse words with replacement characters based on the sound of a string.');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['webpurify_mode'] = [
      '#title' => t('Mode'),
      '#type' => 'select',
      '#options' => webpurify_modes_list(),
      '#description' => t('Select how you want WebPurify to handle fields.'),
      '#default_value' => $this->settings['webpurify_mode']
    ];

    $form['webpurify_validation_message'] = [
      '#title' => t('Validation message'),
      '#type' => 'textfield',
      '#default_value' => !empty($this->settings['webpurify_validation_message'])
          ? $this->settings['webpurify_validation_message']
          : '',
      '#description' => t('This is the text that will be appearing if node creation will be blocked via WebPurify.'),
      '#states' => [
        'visible' => [
          'select[name="filters[filter_webpurify_profanity][settings][webpurify_mode]"]' => [
            'value' => WEBPURIFY_VALIDATION_MODE
          ],
        ],
      ],
    ];

    return $form;
  }
}
