<?php

namespace Drupal\improved_multi_select\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * Defines improved_multi_select settings form.
 *
 * @package Drupal\improved_multi_select\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ims_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['improved_multi_select.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('improved_multi_select.settings');

    $form['isall'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace all multi-select lists'),
      '#default_value' => $config->get('isall'),
    ];

    $form['url'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Replace multi-select lists on specific pages'),
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", [
        '%blog' => '/blog',
        '%blog-wildcard' => '/blog/*',
        '%front' => '<front>',
      ]),
      '#default_value' => $config->get('url'),
    ];

    $form['selectors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Replace multi-select with specified selectors'),
      '#description' => $this->t('Enter jQuery selectors (one selector per line). Example: select[multiple]'),
      '#default_value' => $config->get('selectors'),
    ];

    $form['filtertype'] = [
      '#type' => 'radios',
      '#title' => $this->t('Filter functionality'),
      '#description' => $this->t('Choose how you would like the filter textfield to function.'),
      '#options' => [
        'partial' => $this->t('Partial match: Shows options that contain the filter text.'),
        'exact' => $this->t('Exact match: Shows options that exactly match the filter text.'),
        'anywords' => $this->t('Any words: Shows options that contain any of the individual words in the filter text. Only exact word matches count.'),
        'anywords_partial' => $this->t('Any words (partial): Shows options that contain any of the individual words in the filter text. Partial word matches count.'),
        'allwords' => $this->t('All words: Shows options that contain all of the individual words in the filter text (in any order). Only exact word matches count.'),
        'allwords_partial' => $this->t('All words (partial): Shows options that contain all of the individual words in the filter text (in any order). Partial word matches count.'),
      ],
      '#default_value' => $config->get('filtertype'),
    ];

    $form['orderable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow re-ordering of selected items'),
      '#description' => $this->t('If checked, the user will be able to re-order the selected items using "Move up" and "Move down" buttons. Also, when adding items they will remain in the order they were added instead of keeping the order of the original field. Note: some Drupal fields (like list) will keep this order on an entity view, but will not keep this order on an entity edit form. You have to alter your edit form to account on items order.'),
      '#default_value' => $config->get('orderable'),
    ];

    $form['groupresetfilter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reset filter when selecting a group'),
      '#description' => $this->t('If checked and a select has optgroups, when a group is selected the filter text field is cleared. If unchecked, any existing filter will be applied only to items of the selected group.'),
      '#default_value' => $config->get('groupresetfilter'),
    ];

    $form['button_text'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Button text'),
      '#description' => $this->t('Set the text used for the improved multi-select buttons.'),
    ];
    $form['button_text']['buttontext_add'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add button'),
      '#default_value' => $config->get('buttontext_add'),
    ];
    $form['button_text']['buttontext_addall'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add all button'),
      '#default_value' => $config->get('buttontext_addall'),
    ];
    $form['button_text']['buttontext_del'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Remove button'),
      '#default_value' => $config->get('buttontext_del'),
    ];
    $form['button_text']['buttontext_delall'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Remove all button'),
      '#default_value' => $config->get('buttontext_delall'),
    ];
    $form['button_text']['buttontext_moveup'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Move up button'),
      '#default_value' => $config->get('buttontext_moveup'),
      // Hide the settings when the move buttons are disabled.
      '#states' => [
        'invisible' => [
          ':input[name="orderable"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['button_text']['buttontext_movedown'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Move down button'),
      '#default_value' => $config->get('buttontext_movedown'),
      '#states' => [
        // Hide the settings when the move buttons are disabled.
        'invisible' => [
          ':input[name="orderable"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values_to_save = [
      'isall',
      'url',
      'selectors',
      'filtertype',
      'orderable',
      'groupresetfilter',
      'buttontext_add',
      'buttontext_addall',
      'buttontext_del',
      'buttontext_delall',
      'buttontext_moveup',
      'buttontext_movedown',
    ];

    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      if (array_search($key, $values_to_save) === FALSE) {
        unset($values[$key]);
      }
    }

    $this->config('improved_multi_select.settings')->setData($values)->save();
  }

}
