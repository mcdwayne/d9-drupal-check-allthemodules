<?php

namespace Drupal\toolshed_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form class for creating a keyword search from an exposed view filter.
 */
class KeywordSearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'toolshed_search_keyword_form';
  }

  /**
   * Disable the form build tokens as they do not apply for this keyword form.
   *
   * @param array $form
   *   Structure of the form after it has been processed.
   *
   * @return array
   *   Altered form to render. Should be the form without any of the build
   *   and token information typically used, since it is only to populate the
   *   keyword search.
   */
  public static function afterBuild(array $form) {
    $form['form_id']['#access'] = FALSE;
    $form['form_build_id']['#access'] = FALSE;
    $form['form_token']['#access'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $context = []) {
    // A \Drupal\views\ViewExecutable object for the view.
    $view = $context['view'];
    $context += [
      'placeholder_text' => $this->t('Search by Keyword'),
      'submit_class_names' => [],
    ];
    $context['submit_class_names'][] = 'button--search';

    $form['#action'] = $view->getUrl()->toString();
    $form['#token'] = FALSE;
    $form['#after_build'][] = static::class . '::afterBuild';

    $form[$context['filter_value']] = [
      '#theme_wrappers' => [],
      '#type' => 'textfield',
      '#title' => $this->t('Keyword search'),
      '#attributes' => [
        'placeholder' => $context['placeholder_text'],
        'class' => ['form-text--keyword-search'],
        'aria-label' => $this->t('Keywords for :view@context search', [
          ':view' => $view->getTitle(),
          '@context' => $context['aria_context'],
        ]),
      ],
    ];

    $form['search'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => '<span class="visually-hidden">' . $this->t('Search') . '</span>',
      '#attributes' => [
        'class' => $context['submit_class_names'],
        'aria-label' => $this->t('Submit :view@context search', [
          ':view' => $view->getTitle(),
          '@context' => $context['aria_context'],
        ]),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Form does not uses GET and won't post like standard forms.
  }

}
