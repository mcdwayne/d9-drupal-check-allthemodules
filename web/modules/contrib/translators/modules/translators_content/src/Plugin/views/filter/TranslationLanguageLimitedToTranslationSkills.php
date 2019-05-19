<?php

namespace Drupal\translators_content\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\views\Plugin\views\PluginBase;

/**
 * Class TranslationLanguageLimitedToTranslationSkills.
 *
 * @package Drupal\translators_content\Plugin\views\filter
 */
class TranslationLanguageLimitedToTranslationSkills extends TranslatorsViewsFiltersBase {

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['expose']['identifier'] = $this->getPluginId();
    $options['limit'] = ['default' => FALSE];
    $options['column'] = ['default' => ['source' => 'source', 'target' => '']];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    // Remove the values list - we will handle them on a background basis.
    // Only if limited option is checked.
    $form['value']['#states'] = [
      'visible' => [
        'input[name="options[limit]"]' => ['checked' => FALSE],
      ],
    ];
    // Build values list independently in order to see all the options,
    // while switching "limit" option without necessity to reload the form.
    $form['value']['#options'] = $this->listLanguages(
      LanguageInterface::STATE_ALL | LanguageInterface::STATE_SITE_DEFAULT | PluginBase::INCLUDE_NEGOTIATED,
      array_keys($this->value)
    );

    $end = $form['clear_markup_end'];
    unset($form['clear_markup_end']);
    $form['limit'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Limit languages to translation skills'),
      '#required'      => FALSE,
      '#default_value' => $this->options['limit'],
    ];
    $form['column'] = [
      '#type'          => 'checkboxes',
      '#options'       => $this->getFilterColumnsOptions(),
      '#title'         => $this->t('Translation skills'),
      '#required'      => FALSE,
      '#default_value' => $this->options['column'],
      '#states' => [
        'visible' => [
          'input[name="options[limit]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['clear_markup_end'] = $end;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    if ($this->options['limit']) {
      // We need to force this option to allow users to use only the languages,
      // specified as the user's translation skills.
      $form['expose']['reduce']['#default_value'] = TRUE;
      $form['expose']['reduce']['#disabled'] = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!$this->options['limit']) {
      $this->valueTitle = $this->t('Language');
      // Pass the current values so options that are already selected do not get
      // lost when there are changes in the language configuration.
      $this->valueOptions = $this->listLanguages(LanguageInterface::STATE_ALL | LanguageInterface::STATE_SITE_DEFAULT | PluginBase::INCLUDE_NEGOTIATED, array_keys($this->value));
    }
    else {
      $this->valueTitle = $this->t('Translator skills');
      $this->valueOptions = parent::getValueOptions();
    }
    return array_merge(['All' => $this->t('- Any -')], $this->valueOptions);
  }

  /**
   * {@inheritdoc}
   */
  protected function resetOptionsForEmptySkills(array &$field) {
    // Leave only "- Any -" option if there are no registered skills.
    $field['#options'] = ['All' => $this->t('- Any -')];
    $field['#value'] = $field['#default_value'] = 'All';
  }

}
