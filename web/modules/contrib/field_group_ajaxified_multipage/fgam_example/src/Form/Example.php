<?php

namespace Drupal\fgam_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the Example form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class Example extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Defining fields.
    $form['full_name'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Full Name'),
    ];
    $form['full_name']['first'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First name'),
    ];
    $form['full_name']['last'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last name'),
    ];

    $form['address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Address'),
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];

    // The groups_custom attribute is necessary to allow the module to obtain
    // the groups.
    $form['#groups_custom'] = [
      // Step.
      'group_identity' => [
        'group_name' => 'group_identity',
        'label' => 'Identity',
        // This format type is each step.
        'format_type' => 'multipage',
        // Here are the fields to show in this step by their keys.
        'children' => [
          'full_name',
        ],
      ],
      // Step.
      'group_contact' => [
        'group_name' => 'group_contact',
        'label' => 'Contact',
        'format_type' => 'multipage',
        'children' => [
          'address',
        ],
      ],
      // Step.
      'group_description' => [
        'group_name' => 'group_description',
        'label' => 'Description',
        'format_type' => 'multipage',
        'children' => [
          'description',
        ],
      ],
      // Group all the steps.
      'group_steps' => [
        'group_name' => 'group_steps',
        'label' => 'Steps',
        // Here you define that is a group multipage.
        'format_type' => 'multipage_group',
        'children' => [
          'group_identity',
          'group_contact',
          'group_description',
        ],
        // This settings helps to customize the multipage.
        'format_settings' => [
          'label' => 'Steps',
          // To work properly it needs to be 1.
          'ajaxify' => '1',
          // To work properly it needs to be 0.
          'nonjs_multistep' => '0',
          'classes' => ' group-steps field-group-multipage-group',
          // The options of page_header are:
          // 0: Hidden label.
          // 1: Label only.
          // 2: Step 1 of 10.
          // 3: Step 1 of 10 [Label].
          'page_header' => '3',
          // The options of page_counter are:
          // 0: Hidden counter.
          // 1: Format 1/10.
          // 2: The count number only.
          'page_counter' => '1',
          // The option of move_button is:
          // 1: Move the submit button to the last multipage step.
          'move_button' => '1',
          // The value of scroll_top is:
          // 1: It appends a js that allows scroll top on each step.
          'scroll_top' => '1',
          // The value of button_label is:
          // 1: Allows to overwrite the buttons labels of next and prev.
          'button_label' => '1',
          // Text for next button label.
          'button_label_next' => $this->t('Next button label'),
          // Text for previous button label.
          'button_label_prev' => $this->t('Previous button label'),
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fgam_example';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // It validates that the multipage is enabled.
    if (!is_null($form_state->get('field_group_ajaxified_multipage_enabled')) && !is_null($form_state->get('all')['values'])) {
      $values = $form_state->get('all')['values'];
      drupal_set_message(
        $this->t(
          'The form has been submitted. name="@first @last", address="@address", description="@description"', [
            '@first' => $values['first'],
            '@last' => $values['last'],
            '@address' => $values['address'],
            '@description' => $values['description'],
          ]
        )
      );
    }
    elseif (!is_null($form_state->getValues())) {
      $values = $form_state->getValues();
      drupal_set_message(
        $this->t(
          'The form is not multipage but here are the values. name="@first @last", address=@address, description=@description', [
            '@first' => $values['first'],
            '@last' => $values['last'],
            '@address' => $values['address'],
            '@description' => $values['description'],
          ]
        )
      );
    }
    else {
      drupal_set_message($this->t('There are no values or the form failed'));
    }
    return '';
  }

}
