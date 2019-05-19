<?php

namespace Drupal\tmgmt_deepl;

use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * DeepL translator UI.
 */
class DeeplProTranslatorUi extends TranslatorPluginUiBase {

  /**
   * Overrides TMGMTDefaultTranslatorUIController::pluginSettingsForm().
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();

    $form['auth_key'] = [
      '#type' => 'textfield',
      '#title' => t('DeepL Pro authentication key'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('auth_key'),
      '#description' => t('Please enter your DeepL Pro authentication key or visit <a href="@url">DeepL Pro registration</a> to create new one.',
        ['@url' => 'https://www.deepl.com/pro.html']),
    ];

    $form['url'] = [
      '#type' => 'value',
      '#value' => $translator->getSetting('url'),
    ];

    $form['url_usage'] = [
      '#type' => 'value',
      '#value' => $translator->getSetting('url_usage'),
    ];
    // Additional query options.
    $form['tag_handling'] = [
      '#type' => 'textfield',
      '#title' => t('Tag handling'),
      '#description' => t('Sets which kind of tags should be handled. Comma-separated list of one or more of the following values: "xml"'),
      '#default_value' => !empty($translator->getSetting('tag_handling')) ? $translator->getSetting('tag_handling') : '',
    ];

    $form['non_splitting_tags'] = [
      '#type' => 'textfield',
      '#title' => t('Non-splitting tags'),
      '#description' => t('Comma-separated list of XML tags which never split sentences.'),
      '#default_value' => !empty($translator->getSetting('non_splitting_tags')) ? $translator->getSetting('non_splitting_tags') : '',
    ];

    $form['ignore_tags'] = [
      '#type' => 'textfield',
      '#title' => t('Ignore tags'),
      '#description' => t('Comma-separated list of XML tags whose content is never translated.'),
      '#default_value' => !empty($translator->getSetting('ignore_tags')) ? $translator->getSetting('ignore_tags') : '',
    ];

    $split_split_sentences = $translator->getSetting('split_sentences');
    $form['split_sentences'] = [
      '#type' => 'select',
      '#title' => t('Split sentences'),
      '#options' => [
        '0' => t('Disabled'),
        '1' => t('Enabled (default)'),
      ],
      '#description' => t('Sets whether the translation engine should first split the input into sentences.'),
      '#default_value' => isset($split_split_sentences) ? $split_split_sentences : 1,
      '#required' => TRUE,
    ];

    $preserve_formatting = $translator->getSetting('preserve_formatting');
    $form['preserve_formatting'] = [
      '#type' => 'select',
      '#title' => t('Preserve formatting'),
      '#options' => [
        '0' => t('Disabled (default)'),
        '1' => t('Enabled'),
      ],
      '#description' => t('Sets whether the translation engine should preserve some aspects of the formatting, even if it would usually correct some aspects.'),
      '#default_value' => isset($preserve_formatting) ? $preserve_formatting : 0,
      '#required' => TRUE,
    ];

    $form += parent::addConnectButton();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();
    // Get actual usage data from API - if numeric sth. went wrong.
    $usage_data = $translator->getPlugin()->getUsageData($translator);

    if (is_numeric($usage_data)) {
      $form_state->setErrorByName('settings][auth_key', t('The "DeepL Pro authentication key" is not correct.'));
    }
  }

}
