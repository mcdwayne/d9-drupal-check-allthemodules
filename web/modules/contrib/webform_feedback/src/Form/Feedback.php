<?php

namespace Drupal\webform_feedback\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Feedback.
 */
class Feedback extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_feedback_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'webform_feedback.webform_feedback_form',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Add the first option in the dropdown to the beginning of the array.
    $no_feedback = $this->t("No Webform Selected");
    $field_query[0] = $no_feedback;
    // Find all webforms with block turned on.
    $webform_nids = db_query("SELECT entity_id FROM {node__webform}")->fetchCol();
    if (!empty($webform_nids)) {
      $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($webform_nids);
      // Get the titles of all the webforms with block turned on.
      foreach ($nodes as $value) {
        $title = $value->title->value;
        $nid = $value->nid->value;
        $field_query[$nid] = $title;
      }
    }
    $config = $this->config('webform_feedback.webform_feedback_form');
    $form['webform_feedback_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The text on the feedback button'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('webform_feedback_text'),
      '#weight' => '0',
    ];
    $form['webform_feedback'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose a feedback webform'),
      '#description' => $this->t('Choose a webform.'),
      '#default_value' => $config->get('webform_feedback'),
      '#options' => $field_query,
      '#weight' => '0',
    ];
    $form['webform_feedback_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose which side you want the feedback button to be located'),
      '#options' => [
        'no_style' => $this->t('No Style'),
        'left' => $this->t('left'),
        'right' => $this->t('right'),
      ],
      '#default_value' => 'left',
      '#default_value' => $config->get('webform_feedback_position'),
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable('webform_feedback.webform_feedback_form')
      ->set('webform_feedback_text', $form_state->getValue('webform_feedback_text'))
      ->set('webform_feedback', $form_state->getValue('webform_feedback'))
      ->set('webform_feedback_position', $form_state->getValue('webform_feedback_position'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
