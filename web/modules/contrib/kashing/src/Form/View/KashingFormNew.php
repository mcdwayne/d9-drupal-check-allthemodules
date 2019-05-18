<?php

namespace Drupal\kashing\form\View;

use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\kashing\Entity\KashingValid;
use Drupal\block\Entity\Block;

/**
 * Kashing New Form class.
 */
class KashingFormNew {

  /**
   * New Page content.
   */
  public function addNewFormPage(array &$form) {
    $form['add_mode'] = [
      '#type' => 'details',
      '#group' => 'kashing_settings',
      '#title' => t('Add New Form'),
    ];

    $form['add_mode']['general_field'] = [
      '#type' => 'fieldset',
      '#title' => t('New Form Data'),
    ];

    $form['add_mode']['general_field']['kashing_form_title'] = [
      '#type' => 'textfield',
      '#title' => t('Form Title'),
      '#description' => t('The form title.'),
      '#attributes' => [
        'id' => 'kashing-new-form-title',
      ],
    ];

    $form['add_mode']['general_field']['kashing_form_id'] = [
      '#type' => 'textfield',
      '#title' => t('Form ID'),
      '#description' => t('The form ID (machine name).'),
      '#attributes' => [
        'id' => 'kashing-new-form-id',
        'enabled' => 'false',
      ],
    ];

    $form['add_mode']['general_field']['kashing_form_amount'] = [
      '#type' => 'textfield',
      '#title' => t('Amount'),
      '#description' => t('Enter the form amount that will be processed with the payment system.'),
      '#attributes' => [
        'id' => 'kashing-new-form-amount',
      ],
    ];

    $form['add_mode']['general_field']['kashing_form_description'] = [
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#description' => t('The form transaction description.'),
      '#attributes' => [
        'id' => 'kashing-new-form-description',
      ],
    ];

    $form['add_mode']['form_field'] = [
      '#type' => 'fieldset',
      '#title' => t('Optional fields'),
    ];

    $form['add_mode']['form_field']['checkboxes'] = [
      '#type' => 'fieldset',
      '#title' => t('Form Fields'),
    ];

    $form['add_mode']['form_field']['checkboxes']['kashing_form_checkboxes'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'address2' => t('Address 2'),
        'email' => t('Email'),
        'phone' => t('Phone'),
      ],
            // '#title' => $this->t('Form Fields'),.
      '#description' => t('Enable selected form fields.'),
    ];

    $form['add_mode']['kashing_form_submit'] = [
      '#type' => 'button',
      '#name' => 'add_mode_kashing_form_submit_button_name',
      '#value' => t('Add New Form'),
      '#ajax' => [
        'callback' => 'Drupal\kashing\form\View\KashingFormNew::addNewForm',
        'wrapper' => 'kashing-new-form-result',
      ],
      '#suffix' => '<div id="kashing-new-form-result"></div>',
    ];
  }

  /**
   * New form function.
   */
  public function addNewForm(array &$form, FormStateInterface $form_state) {

    $configuration_errors = FALSE;
    $error_info = '<strong>' . t('Form errors:') . ' </strong><ul>';
    $ajax_response = new AjaxResponse();

    $form_title = Html::escape($form_state->getValue('kashing_form_title'));
    $form_id = Html::escape($form_state->getValue('kashing_form_id'));
    $form_amount = Html::escape($form_state->getValue('kashing_form_amount'));
    $form_description = Html::escape($form_state->getValue('kashing_form_description'));
    $form_checkboxes = Html::escape($form_state->getValue('kashing_form_checkboxes'));

    $kashing_validate = new KashingValid();

    // If title is set.
    if (!$kashing_validate->validateRequiredField($form_title)) {
      $ajax_response->addCommand(new InvokeCommand('#kashing-new-form-title', 'addClass', ['error']));
      $configuration_errors = 'true';
      $error_info .= '<li>' . t('Missing Form Title') . '</li>';
    }
    else {
      $ajax_response->addCommand(new InvokeCommand('#kashing-new-form-title', 'removeClass', ['error']));
    }

    // If ID is set and is available.
    $entity = entity_load('block', $form_id);

    if (!$kashing_validate->validateRequiredField($form_id)) {
      $ajax_response->addCommand(new InvokeCommand('#kashing-new-form-id', 'addClass', ['error']));
      $configuration_errors = 'true';
      $error_info .= '<li>' . t('Missing Form ID') . '</li>';
    }
    elseif (!$kashing_validate->validateIdField($form_id)) {
      $ajax_response->addCommand(new InvokeCommand('#kashing-new-form-id', 'addClass', ['error']));
      $configuration_errors = 'true';
      $error_info .= '<li>' . t('Invalid Form ID') . '</li>';
    }
    elseif ($entity) {
      $ajax_response->addCommand(new InvokeCommand('#kashing-new-form-id', 'addClass', ['error']));
      $configuration_errors = 'true';
      $error_info .= '<li>' . t('Form with selected ID already exists') . '</li>';
    }
    else {
      $ajax_response->addCommand(new InvokeCommand('#kashing-new-form-id', 'removeClass', ['error']));
    }

    // If form amount is set and is a positive float.
    if (!$kashing_validate->validateRequiredField($form_amount)) {
      $ajax_response->addCommand(new InvokeCommand('#kashing-new-form-amount', 'addClass', ['error']));
      $configuration_errors = 'true';
      $error_info .= '<li>' . t('Missing Amount') . '</li>';
    }
    elseif (!$kashing_validate->validateAmountField($form_amount)) {
      $ajax_response->addCommand(new InvokeCommand('#kashing-new-form-amount', 'addClass', ['error']));
      $configuration_errors = 'true';
      $error_info .= '<li>' . t('Invalid Amount') . '</li>';
    }
    else {
      $ajax_response->addCommand(new InvokeCommand('#kashing-new-form-amount', 'removeClass', ['error']));
    }

    // If description is set.
    if (!$kashing_validate->validateRequiredField($form_description)) {
      $ajax_response->addCommand(new InvokeCommand('#kashing-new-form-description', 'addClass', ['error']));
      $configuration_errors = 'true';
      $error_info .= '<li>' . t('Missing Description') . '</li>';
    }
    else {
      $ajax_response->addCommand(new InvokeCommand('#kashing-new-form-description', 'removeClass', ['error']));
    }

    if (!$configuration_errors) {

      $kashing_block = Block::create([
        'id' => $form_id,
        'weight' => 0,
        'status' => TRUE,
        'region' => 'footer',
        'plugin' => 'kashing_block',
        'settings' => [
          'label' => $form_title,
          'kashing_form_settings' => [
            'kashing_form_amount' => $form_amount,
            'kashing_form_description' => $form_description,
            'kashing_form_checkboxes' => $form_checkboxes,
          ],
        ],
        // 'theme' => 'seven',.
        'visibility' => [
          'request_path' => [
            'id' => 'request_path',
            'negate' => FALSE,
            'pages' => '/path',
          ],
        ],
      ]);

      $kashing_block->save();

      // New form is created.
      $ajax_response->addCommand(new InvokeCommand('#kashing-new-form-title', 'removeClass', ['error']));
      $ajax_response->addCommand(new InvokeCommand('#kashing-new-form-result', 'removeClass', ['messages--error messages']));
      $ajax_response->addCommand(new HtmlCommand('#kashing-new-form-result', t('New Form added successfully!')));
      $ajax_response->addCommand(new InvokeCommand('#kashing-new-form-result', 'addClass', ['messages--status messages']));

    }
    else {

      // New form is not created (failed)
      $ajax_response->addCommand(new InvokeCommand('#kashing-new-form-result', 'removeClass', ['messages--status messages']));
      $ajax_response->addCommand(new InvokeCommand('#kashing-new-form-result', 'addClass', ['messages--error messages']));
      $ajax_response->addCommand(new HtmlCommand('#kashing-new-form-result', $error_info));
    }

    return $ajax_response;

  }

}
