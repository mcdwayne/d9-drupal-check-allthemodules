<?php

namespace Drupal\mailcamp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mailcamp\MailcampService;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class SignupForm.
 *
 * @package Drupal\mailcamp\Form
 */
class SignupForm extends FormBase {

  protected $mailcamp;
  protected $configuration;

  /**
   * Constructor, initializes the MailcampService.
   */
  public function __construct() {
    $this->mailcamp = new MailcampService();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'signup_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $configuration = NULL) {
    $this->configuration = $configuration;
    $fields = $this->mailcamp->getCustomFields($configuration['mailcamp_lists']);

    $form['description'] = [
      '#markup' => $this->configuration['description']['value'],
    ];
    $form['customfields'] = [
      '#tree' => TRUE,
    ];
    foreach ($configuration['mailcamp_customfields'] as $field_id) {
      if (!is_string($field_id)) {
        continue;
      }
      $field = $fields[$field_id];

      $form_field = [
        '#title' => $this->t($field['name']),
        '#required' => $field['required'],
      ];
      switch ($field['fieldtype']) {
        case 'text':
          $form_field['#type'] = 'textfield';
          break;

        case 'dropdown':
          $form_field['#type'] = 'select';
          $form_field['#options'] = unserialize($field['fieldsettings'])['Value'];
          break;

        case 'number':
          $form_field['#type'] = 'number';
          break;

        case 'textarea':
          $form_field['#type'] = 'textarea';
          break;

        default:
          drupal_set_message(
              $this->t('Unknown field type %fieldtype for field %fieldname.', [
                '%fieldtype' => $field['fieldtype'],
                '%fieldname' => $field['name'],
              ]),
              'error'
          );
          continue;
      }
      $form['customfields'][$field['fieldid']] = $form_field;
    }

    $form['email_address'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t($configuration['submit_button_label']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->mailcamp->addSubscriber($this->configuration['mailcamp_lists'], $form_state->getValues());

    if ($this->configuration['form_destination_page']) {
      return new RedirectResponse($this->configuration['form_destination_page']);
    }
    if ($this->configuration['confirmation_message']) {
      drupal_set_message($this->t($this->configuration['confirmation_message']));
    }
  }

}
