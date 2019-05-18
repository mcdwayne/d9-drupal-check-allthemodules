<?php

namespace Drupal\empty_page\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form for callbacks add/edit form.
 */
class CallbackForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['empty_page.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'empty_page_callback_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cid = NULL) {
    // If $cid exists, we're editing.
    $callback = NULL;
    $settings = $this->configFactory->get('empty_page.settings');

    if (!empty($cid)) {
      $callback = $settings->get('callback_' . $cid);
    }
    if ($callback) {
      $this->cid = $cid;
      $form_title = $this->t('Edit callback');
    }
    else {
      $form_title = $this->t('Create a new callback');
    }
    $form['empty_page_basic'] = [
      '#type' => 'details',
      '#title' => $form_title,
      '#description' => '',
      '#open' => TRUE,
    ];
    $form['empty_page_basic']['empty_page_callback_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Internal path'),
      '#description' => '',
      '#required' => 1,
      '#default_value' => $callback ? $callback['path'] : '',
    ];
    $form['empty_page_basic']['empty_page_callback_page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#description' => '',
      '#default_value' => $callback ? $callback['page_title'] : '',
    ];
    $form['empty_page_basic']['buttons']['submit'] = [
      '#type' => 'submit',
      '#value' => $callback ? $this->t('Save') : $this->t('Add'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $new = FALSE;
    $settings = $this->config('empty_page.settings');

    if (empty($this->cid)) {
      $new = TRUE;
      $id = $this->config('empty_page.settings')->get('new_id');
      $callback['created'] = REQUEST_TIME;
    }
    else {
      $id = $this->cid;
      $callback['created'] = $settings->get('callback_' . $id)['created'];
    }

    $callback['cid'] = $id;
    $callback['updated'] = REQUEST_TIME;
    $callback['path'] = $values['empty_page_callback_path'];
    $callback['page_title'] = $values['empty_page_callback_page_title'];

    $config = $this->config('empty_page.settings')->set('callback_' . $id, $callback);
    $config = $new ? $config->set('new_id', $id + 1) : $config;
    $config->save();
    \Drupal::service('router.builder')->rebuild();
    drupal_set_message($this->t('Changes saved.'));
    $form_state->setRedirect('empty_page.administration');
  }

}
