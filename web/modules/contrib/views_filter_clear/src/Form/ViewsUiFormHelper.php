<?php

namespace Drupal\views_filter_clear\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Alters the Views UI to add clear button configurations.
 */
class ViewsUiFormHelper {

  use StringTranslationTrait;

  /**
   * Alters the filter configuration form.
   *
   * @param array $form
   *   The form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function viewsUiFormAlter(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\views\Plugin\views\HandlerBase $handler */
    $handler = $form_state->get('handler');
    if ($handler->canExpose()) {
      /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $handler */

      // @todo This seems extremely hacky. There is not apparently a way to
      // add options to a handler's `defineOptions` method, but adding it here
      // and in the submit handler below does persist the option through a view
      // save.
      $handler->options['expose']['add_clear_link'] = isset($handler->options['expose']['add_clear_link']) ? $handler->options['expose']['add_clear_link'] : FALSE;
      $form['options']['expose']['add_clear_link'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Add a <em>clear</em> link'),
        '#description' => $this->t('This will add a link to clear just the value of this filter from the form.'),
        '#default_value' => $handler->options['expose']['add_clear_link'],
      ];

      // Attach a submit handler to run before the default handler.
      array_unshift($form['actions']['submit']['#submit'], [ViewsUiFormHelper::class, 'submitFilterForm']);
    }
  }

  /**
   * Submit handler for the exposed filter settings form.
   */
  public static function submitFilterForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $handler */
    $handler = $form_state->get('handler');
    $handler->options['expose']['add_clear_link'] = $form_state->getValue(
      ['options', 'expose', 'add_clear_link']
    );
  }

}
