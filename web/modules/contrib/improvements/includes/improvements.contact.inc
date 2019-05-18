<?php

use Drupal\Component\Utility\Html;
use Drupal\contact\ContactFormInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter(): contact_form_edit_form.
 */
function improvements_form_contact_form_edit_form_alter(&$form, FormStateInterface $form_state) {
  /** @var EntityFormInterface $form_object */
  $form_object = $form_state->getFormObject();
  /** @var ContactFormInterface $contact_form */
  $contact_form = $form_object->getEntity();

  $form['additional_settings'] = [
    '#type' => 'fieldset',
    '#title' => t('Additional settings'),
  ];

  $form['additional_settings']['page_title'] = [
    '#type' => 'textfield',
    '#title' => t('Page title'),
    '#default_value' => $contact_form->getThirdPartySetting('improvements', 'page_title'),
  ];

  $form['additional_settings']['subject_label'] = [
    '#type' => 'textfield',
    '#title' => t('Subject label'),
    '#default_value' => $contact_form->getThirdPartySetting('improvements', 'subject_label'),
  ];

  $form['additional_settings']['message_label'] = [
    '#type' => 'textfield',
    '#title' => t('Message label'),
    '#default_value' => $contact_form->getThirdPartySetting('improvements', 'message_label'),
  ];

  $form['additional_settings']['submit_button_text'] = [
    '#type' => 'textfield',
    '#title' => t('Submit button text'),
    '#default_value' => $contact_form->getThirdPartySetting('improvements', 'submit_button_text'),
  ];

  $form['additional_settings']['required_fields'] = [
    '#type' => 'checkboxes',
    '#title' => t('Required fields'),
    '#options' => [
      'name' => t('Name'),
      'mail' => t('E-mail'),
      'subject' => t('Subject'),
      'message' => t('Message'),
    ],
    '#default_value' => $contact_form->getThirdPartySetting('improvements', 'required_fields', ['name', 'mail', 'subject', 'message']),
  ];

  $form['additional_settings']['ajax_submit'] = [
    '#type' => 'checkbox',
    '#title' => t('AJAX submit'),
    '#default_value' => $contact_form->getThirdPartySetting('improvements', 'ajax_submit'),
  ];

  $form['additional_settings']['hide_preview'] = [
    '#type' => 'checkbox',
    '#title' => t('Hide preview button'),
    '#default_value' => $contact_form->getThirdPartySetting('improvements', 'hide_preview'),
  ];

  $form['#entity_builders'][] = 'improvements_contact_form_edit_form_entity_builder';
  $form['actions']['submit']['#submit'][] = 'improvements_contact_form_edit_form_submit';
}

/**
 * Contact form entity builder.
 * @see improvements_form_contact_form_edit_form_alter().
 */
function improvements_contact_form_edit_form_entity_builder($entity_type, ContactFormInterface $contact_form, &$form, FormStateInterface $form_state) {
  $simple_settings = [
    'page_title',
    'subject_label',
    'message_label',
    'submit_button_text',
    'ajax_submit',
    'hide_preview',
  ];
  foreach ($simple_settings as $name) {
    $contact_form->setThirdPartySetting('improvements', $name, $form_state->getValue($name));
  }

  $contact_form->setThirdPartySetting('improvements', 'required_fields', array_keys(array_filter($form_state->getValue('required_fields'))));
}

/**
 * Custom submit callback for contact edit form.
 * @see improvements_form_contact_form_edit_form_alter().
 */
function improvements_contact_form_edit_form_submit($form, FormStateInterface $form_state) {
  /** @var EntityFormInterface $form_object */
  $form_object = $form_state->getFormObject();
  /** @var ContactFormInterface $contact_form */
  $contact_form = $form_object->getEntity();
  /** @var EntityFieldManagerInterface $entity_field_manager */
  $entity_field_manager = \Drupal::service('entity_field.manager');
  $contact_form_id = $contact_form->id();
  $contact_message_fields = $entity_field_manager->getFieldDefinitions('contact_message', $contact_form_id);

  if ($subject_label = $form_state->getValue('subject_label')) {
    $contact_message_fields['subject']->getConfig($contact_form_id)->setLabel($subject_label)->save();
  }
  if ($message_label = $form_state->getValue('message_label')) {
    $contact_message_fields['message']->getConfig($contact_form_id)->setLabel($message_label)->save();
  }

  $required_fields = array_keys(array_filter($form_state->getValue('required_fields')));
  foreach (['subject', 'message'] as $field_name) {
    $required = in_array($field_name, $required_fields);
    $contact_message_fields[$field_name]->getConfig($contact_form_id)->setRequired($required)->save();
  }
}

/**
 * Implements hook_form_BASE_FORM_ID_alter(): contact_message_form.
 */
function improvements_form_contact_message_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  /** @var ContactFormInterface $contact_form */
  $contact_form = $form_state->getFormObject()->getEntity()->getContactForm();
  $required_fields = $contact_form->getThirdPartySetting('improvements', 'required_fields', []);

  if (!in_array('name', $required_fields)) {
    $form['name']['#required'] = FALSE;
  }
  if (!in_array('mail', $required_fields)) {
    $form['mail']['#required'] = FALSE;
  }
  if ($page_title = $contact_form->getThirdPartySetting('improvements', 'page_title')) {
    $form['#pre_render_title'] = $page_title;
    $form['#pre_render'][] = 'improvements_form_contact_message_form_pre_render';
  }
  if ($submit_button_text = $contact_form->getThirdPartySetting('improvements', 'submit_button_text')) {
    $form['actions']['submit']['#value'] = $submit_button_text;
  }
  if ($contact_form->getThirdPartySetting('improvements', 'hide_preview')) {
    $form['actions']['preview']['#access'] = FALSE;
  }
  if ($contact_form->getThirdPartySetting('improvements', 'ajax_submit')) {
    $form_ajax_wrapper_id = Html::getId($form['#form_id']) . '-ajax-wrapper';
    $form['#prefix'] = '<div id="' . $form_ajax_wrapper_id . '">';
    $form['#suffix'] = '</div>';

    $form['actions']['submit']['#ajax'] = [
      'wrapper' => $form_ajax_wrapper_id,
      'callback' => 'improvements_form_contact_message_form_ajax',
      'event' => 'click',
    ];
  }
}

/**
 * Pre render message form.
 */
function improvements_form_contact_message_form_pre_render($form) {
  if (isset($form['#pre_render_title'])) {
    $form['#title'] = $form['#pre_render_title'];
  }

  return $form;
}

/**
 * Contact form ajax submit callback.
 */
function improvements_form_contact_message_form_ajax(&$form, FormStateInterface $form_state) {
  if ($form_state->getErrors()) {
    return $form;
  }
  else {
    /** @var ContactFormInterface $contact_form */
    $contact_form = $form_state->getFormObject()->getEntity()->getContactForm();
    return ['#markup' => $contact_form->getMessage()];
  }
}
