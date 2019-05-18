<?php

namespace Drupal\custom_voice_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CustomVoiceSearchForm.
 */
class CustomVoiceSearchForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'custom_voice_search.googlevoicesearch',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_voice_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('custom_voice_search.googlevoicesearch');
    $count = ($config->get('form_ids') !== NULL) ? count($config->get('form_ids')) : 0;
    // Gather the number of form ids in the form.
    $num_ids = $form_state->get('num_ids');
    // We have to ensure that there is at least one form id field.
    if ($num_ids === NULL) {
      $form_state->set('num_ids', $count);
      $num_ids = $count;
    }
    $form['#tree'] = TRUE;
    $form['ids_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add form ids'),
      '#prefix' => '<div id="ids-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    for ($i = 0; $i < $num_ids; $i++) {
      $form['ids_fieldset'][$i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Form Id: :id', [':id' => ($i + 1)]),
      ];
      $form['ids_fieldset'][$i]['id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Form ID :id', [':id' => $i + 1]),
        '#default_value' => isset($config->get('form_ids')[$i]['id']) ? $config->get('form_ids')[$i]['id'] : NULL,
        '#description' => $this->t('Provide Form ID like: user_login_form'),
      ];
      $form['ids_fieldset'][$i]['input_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Input ID :id', [':id' => $i + 1]),
        '#default_value' => isset($config->get('form_ids')[$i]['input_id']) ? $config->get('form_ids')[$i]['input_id'] : NULL,
        '#description' => $this->t('Provide input ID on which voice search needs to be implemented like: edit-keys'),
      ];
      $form['ids_fieldset'][$i]['input_machine_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Input Machine Name :id', [':id' => $i + 1]),
        '#default_value' => isset($config->get('form_ids')[$i]['input_machine_name']) ? $config->get('form_ids')[$i]['input_machine_name'] : NULL,
        '#description' => $this->t('Provide name attributes for input textfield.'),
      ];
    }

    $form['ids_fieldset']['actions'] = [
      '#type' => 'actions',
    ];
    $form['ids_fieldset']['actions']['add_id'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more'),
      '#submit' => ['::addOne'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'ids-fieldset-wrapper',
      ],
    ];
    // If there is more than one name, add the remove button.
    if ($num_ids > 1) {
      $form['ids_fieldset']['actions']['remove_id'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove one'),
        '#submit' => ['::removeCallback'],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'ids-fieldset-wrapper',
        ],
      ];
    }
    $form_state->setCached(FALSE);
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    if (array_key_exists('actions', $form_state->getValue('ids_fieldset'))) {
      array_pop($form_state->getValue('ids_fieldset'));
      $this->config('custom_voice_search.googlevoicesearch')
        ->set('form_ids', $form_state->getValue('ids_fieldset'))
        ->save();
    }
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['ids_fieldset'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $ids_field = $form_state->get('num_ids');
    $add_button = $ids_field + 1;
    $form_state->set('num_ids', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $ids_field = $form_state->get('num_ids');
    if ($ids_field > 1) {
      $remove_button = $ids_field - 1;
      $form_state->set('num_ids', $remove_button);
    }
    $form_state->setRebuild();
  }

}
