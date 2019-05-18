<?php

namespace Drupal\calltracking\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Class ImportForm.
 *
 * @package Drupal\calltracking\Form
 */
class SettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['calltracking.settings'];
  }

  public function getFormId() {
    return 'setting_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('calltracking.settings');
    $form['#tree'] = TRUE;

    $form['fields'] = [
      '#type' => 'fieldset',
      '#prefix' => '<div id="names-fieldset-wrapper">
        <div id="validate-state-message"></div>',
      '#suffix' => '</div>',
    ];

    $values = $config->get('fields');
    $utmCount = $form_state->get('utm');
    if (empty($utmCount) && !empty(count($values))) {
      $utmCount = count($values);
      $form_state->set('utm', $utmCount);
    }
    elseif (empty($utmCount) && empty(count($values))) {
      $utmCount = 1;
      $form_state->set('utm', 1);
    }

    // Each all forms with UTM labels.
    for ($i = 0; $i < $utmCount; $i++) {
      $form['fields']['items'][$i]['key'] = [
        '#type' => 'textfield',
        '#title' => 'UTM name',
        '#default_value' => $values[$i]['key'],
        '#placeholder' => 'utm_source',
        '#prefix' => '<div class="field--wrapper">
          <div id="fields-items-' . $i . '-key">',
        '#suffix' => '</div>',
      ];

      $form['fields']['items'][$i]['val'] = [
        '#type' => 'textfield',
        '#title' => 'UTM content',
        '#default_value' => $values[$i]['val'],
        '#placeholder' => 'google',
        '#prefix' => '<div id="fields-items-' . $i . '-val">',
        '#suffix' => '</div>',
      ];

      $form['fields']['items'][$i]['from'] = [
        '#type' => 'textfield',
        '#title' => 'Telephone from',
        '#default_value' => $values[$i]['from'],
        '#prefix' => '<div id="fields-items-' . $i . '-from">',
        '#placeholder' => '+1 (999) 999-99-99',
        '#suffix' => '</div>',
      ];

      $form['fields']['items'][$i]['to'] = [
        '#type' => 'textfield',
        '#title' => 'Telephone to',
        '#default_value' => $values[$i]['to'],
        '#placeholder' => '+1 (999) 999-99-99',
        '#prefix' => '<div id="fields-items-' . $i . '-to">',
        '#suffix' => '</div></div>',
      ];
    }

    // Add more button.
    $form['fields']['more_fields'] = [
      '#type' => 'submit',
      '#value' => t('+ Add'),
      '#submit' => ['::calltracking_add_more_add_one'],
      '#ajax' => [
        'callback' => [$this, 'calltracking_add_more_callback'],
        'wrapper' => 'names-fieldset-wrapper',
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#submit' => ['::submitForm'],
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    return $form;
  }

  /**
   * Add more button callback.
   */
  public function calltracking_add_more_callback(array &$form, FormStateInterface $form_state) {
    return $form['fields'];
  }

  /**
   * Add more button handler
   */
  public function calltracking_add_more_add_one(array &$form, FormStateInterface $form_state) {
    $utmCounts = $form_state->get('utm');
    $form_state->set('utm', $utmCounts + 1);
    $form_state->setRebuild();
  }

  /**
   * Submit.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $config = $this->config('calltracking.settings');
    $validate = TRUE;
    $fields = [];
    $i = 0;

    // Each forms for validate.
    foreach ($form_state->getValue('fields')['items'] as $item) {
      $empty = 0;
      if (!$item['key']) {
        $empty += 1;
      }
      if (!$item['val']) {
        $empty += 1;
      }
      if (!$item['from']) {
        $empty += 1;
      }
      if (!$item['to']) {
        $empty += 1;
      }

      if ($empty == 0) {
        $fields[] = $item;
      }

      // Checking empty fields.
      foreach ($item as $key => $val) {
        $color = '';
        if ($empty < 4 && $empty > 0 && !$item[$key]) {
          $color = 'red';
          $validate = FALSE;
        }
        $ajax_response->addCommand(new CssCommand(
          '#fields-items-' . $i . '-' . $key . ' input',
          ['border-color' => $color]
        ));
      }
      $i++;
    }

    // Is validate.
    if ($validate) {
      // Write data to config file.
      $config->set('fields', $fields);
      $config->save();
      $message = [
        'type' => 'status',
        'text' => t('Success'),
      ];
    }
    else {
      $message = [
        'type' => 'error',
        'text' => t('Error'),
      ];
    }

    // Response status message.
    $ajax_response->addCommand(new HtmlCommand(
      '#validate-state-message',
      '<div class="messages messages--'
      . $message['type'] . '">'
      . $message['text'] .
      '</div>'
    ));

    return $ajax_response;
  }
}
