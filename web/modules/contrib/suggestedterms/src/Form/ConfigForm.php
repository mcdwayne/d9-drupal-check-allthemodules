<?php
/**
 * @file
 * Definition of Drupal\suggestedterms\Form.
 * Provides admin form for suggestedterms.
 */

namespace Drupal\suggestedterms\Form;

use \Drupal\Core\Form\ConfigFormBase;
use \Drupal\Core\Form\FormStateInterface;

class ConfigForm extends ConfigFormBase {
  public function getFormId() {
    return 'suggestedterms_config';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('suggestedterms.settings');

    $form['maximum_displayed'] = [
      '#title' => $this->t('Results count'),
      '#description' => t('The maximum number of links to display in the block. Enter 0 to show all.'),
      '#type' => 'number',
      '#default_value' => $config->get('maximum_displayed', 5),
    ];

    $form['sort_order'] = [
      '#title' => $this->t('Sort order'),
      '#description' => t('The sort order for the links displayed.'),
      '#type' => 'select',
      '#default_value' => $config->get('sort_order', SUGGESTEDTERMS_SORT_NAME),
      '#options' => [
        SUGGESTEDTERMS_SORT_POPULAR => t('Most used'),
        SUGGESTEDTERMS_SORT_NAME => t('Alphabetically'),
        SUGGESTEDTERMS_SORT_WEIGHT => t('Weight'),
        SUGGESTEDTERMS_SORT_RECENT => t('Most recently added'),
        SUGGESTEDTERMS_SORT_USED => t('Most recently used'),
      ],
    ];

    $form['display_mode'] = [
      '#title' => $this->t('Which terms to display'),
      '#description' => t("Whether to display all defined terms or only the ones previously used."),
      '#type' => 'radios',
      '#prefix' => '<h4>Display Options</h4>',
      '#default_value' => $config->get('display_mode', SUGGESTEDTERMS_DISPLAY_ALL),
      '#options' => [
        SUGGESTEDTERMS_DISPLAY_USED => t('Previously-used terms'),
        SUGGESTEDTERMS_DISPLAY_ALL => t('All terms'),
      ],
    ];

    $form['display_fieldset'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display terms in a collapsed fieldset'),
      '#description' => t('The terms will be wrapped in a fieldset that is collapsed by default'),
      '#default_value' => $config->get('display_fieldset', SUGGESTEDTERMS_DISPLAY_FIELDSET),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('suggestedterms.settings');
    $config->set('maximum_displayed', $form_state['values']['maximum_displayed']);
    $config->set('sort_order', $form_state['values']['sort_order']);
    $config->set('display_mode', $form_state['values']['display_mode']);
    $config->set('display_fieldset', $form_state['values']['display_fieldset']);
    $config->save();
  }
}