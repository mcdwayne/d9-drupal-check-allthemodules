<?php

namespace Drupal\interface_string_stats\Form;

use Drupal\locale\Form\TranslateEditForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\locale\SourceString;

/**
 * Defines a translation edit form.
 */
class StringStatsTranslateEditForm extends TranslateEditForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $filter_values = $this->translateFilterValues();
    $langcode = $filter_values['langcode'];

    $this->languageManager->reset();
    $languages = $this->languageManager->getLanguages();

    $langname = isset($langcode) ? $languages[$langcode]->getName() : "- None -";

    $form['#attached']['library'][] = 'locale/drupal.locale.admin';

    $form['langcode'] = [
      '#type' => 'value',
      '#value' => $filter_values['langcode'],
    ];

    $form['strings'] = [
      '#type' => 'table',
      '#tree' => TRUE,
      '#language' => $langname,
      '#header' => [
        $this->t('Source string'),
        $this->t('Translation for @language', ['@language' => $langname]),
        $this->t('Usage count'),
      ],
      '#empty' => $this->t('No strings available.'),
      '#attributes' => ['class' => ['locale-translate-edit-table']],
    ];

    if (isset($langcode)) {
      $strings = $this->translateFilterLoadStrings();

      $plurals = $this->getNumberOfPlurals($langcode);

      foreach ($strings as $string) {
        // Cast into source string, will do for our purposes.
        $source = new SourceString($string);
        // Split source to work with plural values.
        $source_array = $source->getPlurals();
        $translation_array = $string->getPlurals();
        if (count($source_array) == 1) {
          // Add original string value and mark as non-plural.
          $plural = FALSE;
          $form['strings'][$string->lid]['original'] = [
            '#type' => 'item',
            '#title' => $this->t('Source string (@language)', ['@language' => $this->t('Built-in English')]),
            '#title_display' => 'invisible',
            '#plain_text' => $source_array[0],
            '#preffix' => '<span lang="en">',
            '#suffix' => '</span>',
          ];
        }
        else {
          // Add original string value and mark as plural.
          $plural = TRUE;
          $original_singular = [
            '#type' => 'item',
            '#title' => $this->t('Singular form'),
            '#plain_text' => $source_array[0],
            '#prefix' => '<span class="visually-hidden">' . $this->t('Source string (@language)', ['@language' => $this->t('Built-in English')]) . '</span><span lang="en">',
            '#suffix' => '</span>',
          ];
          $original_plural = [
            '#type' => 'item',
            '#title' => $this->t('Plural form'),
            '#plain_text' => $source_array[1],
            '#preffix' => '<span lang="en">',
            '#suffix' => '</span>',
          ];
          $form['strings'][$string->lid]['original'] = [
            $original_singular,
            ['#markup' => '<br>'],
            $original_plural,
          ];
        }
        if (!empty($string->context)) {
          $form['strings'][$string->lid]['original'][] = [
            '#type' => 'inline_template',
            '#template' => '<br><small>{{ context_title }}: <span lang="en">{{ context }}</span></small>',
            '#context' => [
              'context_title' => $this->t('In Context'),
              'context' => $string->context,
            ],
          ];
        }
        // Approximate the number of rows to use in the default textarea.
        $rows = min(ceil(str_word_count($source_array[0]) / 12), 10);
        if (!$plural) {
          $form['strings'][$string->lid]['translations'][0] = [
            '#type' => 'textarea',
            '#title' => $this->t('Translated string (@language)', ['@language' => $langname]),
            '#title_display' => 'invisible',
            '#rows' => $rows,
            '#default_value' => $translation_array[0],
            '#attributes' => ['lang' => $langcode],
          ];
        }
        else {
          // Add a textarea for each plural variant.
          for ($i = 0; $i < $plurals; $i++) {
            $form['strings'][$string->lid]['translations'][$i] = [
              '#type' => 'textarea',
              // @todo Should use better labels https://www.drupal.org/node/2499639
              '#title' => ($i == 0 ? $this->t('Singular form') : $this->formatPlural($i, 'First plural form', '@count. plural form')),
              '#rows' => $rows,
              '#default_value' => isset($translation_array[$i]) ? $translation_array[$i] : '',
              '#attributes' => ['lang' => $langcode],
              '#prefix' => $i == 0 ? ('<span class="visually-hidden">' . $this->t('Translated string (@language)', ['@language' => $langname]) . '</span>') : '',
            ];
          }
          if ($plurals == 2) {
            // Simplify interface text for the most common case.
            $form['strings'][$string->lid]['translations'][1]['#title'] = $this->t('Plural form');
          }
        }
        // Add the usage count.
        $form['strings'][$string->lid]['usage'] = [
          '#type' => 'item',
          '#plain_text' => $string->count,
        ];
      }
      if (count(Element::children($form['strings']))) {
        $form['actions'] = ['#type' => 'actions'];
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Save translations'),
        ];
      }
    }
    $form['pager']['#type'] = 'pager';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function translateFilterLoadStrings() {
    $filter_values = $this->translateFilterValues();

    // Language is sanitized to be one of the possible options in
    // translateFilterValues().
    $conditions = ['language' => $filter_values['langcode']];
    $options = [
      'pager limit' => 30,
      'translated' => TRUE,
      'untranslated' => TRUE,
    ];

    // Add translation status conditions and options.
    switch ($filter_values['translation']) {
      case 'translated':
        $conditions['translated'] = TRUE;
        if ($filter_values['customized'] != 'all') {
          $conditions['customized'] = $filter_values['customized'];
        }
        break;

      case 'untranslated':
        $conditions['translated'] = FALSE;
        break;

    }

    if (!empty($filter_values['string'])) {
      $options['filters']['source'] = $filter_values['string'];
      if ($options['translated']) {
        $options['filters']['translation'] = $filter_values['string'];
      }
    }

    $options['isf_query'] = TRUE;

    return $this->localeStorage->getTranslations($conditions, $options);
  }

}
