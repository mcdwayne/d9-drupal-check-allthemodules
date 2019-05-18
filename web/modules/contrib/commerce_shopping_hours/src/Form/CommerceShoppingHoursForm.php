<?php

namespace Drupal\commerce_shopping_hours\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CommerceShoppingHoursForm.
 *
 * @package Drupal\commerce_shopping_hours\Form
 */
class CommerceShoppingHoursForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_shopping_hours.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_shopping_hours_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_shopping_hours.settings');

    $form['working_hours'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Opening and closing hours'),
    ];

    // Monday.
    $form['working_hours']['monday_from'] = [
      '#title' => $this->t('Monday'),
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 5,
      '#default_value' => $config->get('monday_from'),
      '#attributes' => [
        'class' => ['shopping-hours'],
        'autocomplete' => 'off',
      ],
      '#prefix' => '<div class="container-inline">',
    ];
    $form['working_hours']['monday_to'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 5,
      '#default_value' => $config->get('monday_to'),
      '#attributes' => [
        'class' => ['shopping-hours'],
        'autocomplete' => 'off',
      ],
      '#suffix' => '</div>',
    ];

    // Tuesday.
    $form['working_hours']['tuesday_from'] = [
      '#title' => $this->t('Tuesday'),
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 5,
      '#default_value' => $config->get('tuesday_from'),
      '#attributes' => [
        'class' => ['shopping-hours'],
        'autocomplete' => 'off',
      ],
      '#prefix' => '<div class="container-inline">',
    ];
    $form['working_hours']['tuesday_to'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 5,
      '#default_value' => $config->get('tuesday_to'),
      '#attributes' => [
        'class' => ['shopping-hours'],
        'autocomplete' => 'off',
      ],
      '#suffix' => '</div>',
    ];

    // Wednesday.
    $form['working_hours']['wednesday_from'] = [
      '#title' => $this->t('Wednesday'),
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 5,
      '#default_value' => $config->get('wednesday_from'),
      '#attributes' => [
        'class' => ['shopping-hours'],
        'autocomplete' => 'off',
      ],
      '#prefix' => '<div class="container-inline">',
    ];
    $form['working_hours']['wednesday_to'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 5,
      '#default_value' => $config->get('wednesday_to'),
      '#attributes' => [
        'class' => ['shopping-hours'],
        'autocomplete' => 'off',
      ],
      '#suffix' => '</div>',
    ];

    // Thursday.
    $form['working_hours']['thursday_from'] = [
      '#title' => $this->t('Thursday'),
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 5,
      '#default_value' => $config->get('thursday_from'),
      '#attributes' => [
        'class' => ['shopping-hours'],
        'autocomplete' => 'off',
      ],
      '#prefix' => '<div class="container-inline">',
    ];
    $form['working_hours']['thursday_to'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 5,
      '#default_value' => $config->get('thursday_to'),
      '#attributes' => [
        'class' => ['shopping-hours'],
        'autocomplete' => 'off',
      ],
      '#suffix' => '</div>',
    ];

    // Friday.
    $form['working_hours']['friday_from'] = [
      '#title' => $this->t('Friday'),
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 5,
      '#default_value' => $config->get('friday_from'),
      '#attributes' => [
        'class' => ['shopping-hours'],
        'autocomplete' => 'off',
      ],
      '#prefix' => '<div class="container-inline">',
    ];
    $form['working_hours']['friday_to'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 5,
      '#default_value' => $config->get('friday_to'),
      '#attributes' => [
        'class' => ['shopping-hours'],
        'autocomplete' => 'off',
      ],
      '#suffix' => '</div>',
    ];

    // Saturday.
    $form['working_hours']['saturday_from'] = [
      '#title' => $this->t('Saturday'),
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 5,
      '#default_value' => $config->get('saturday_from'),
      '#attributes' => [
        'class' => ['shopping-hours'],
        'autocomplete' => 'off',
      ],
      '#prefix' => '<div class="container-inline">',
    ];
    $form['working_hours']['saturday_to'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 5,
      '#default_value' => $config->get('saturday_to'),
      '#attributes' => [
        'class' => ['shopping-hours'],
        'autocomplete' => 'off',
      ],
      '#suffix' => '</div>',
    ];

    // Sunday.
    $form['working_hours']['sunday_from'] = [
      '#title' => $this->t('Sunday'),
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 5,
      '#default_value' => $config->get('sunday_from'),
      '#attributes' => [
        'class' => ['shopping-hours'],
        'autocomplete' => 'off',
      ],
      '#prefix' => '<div class="container-inline">',
    ];
    $form['working_hours']['sunday_to'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 5,
      '#default_value' => $config->get('sunday_to'),
      '#attributes' => [
        'class' => ['shopping-hours'],
        'autocomplete' => 'off',
      ],
      '#suffix' => '</div>',
    ];

    $form['working_hours']['show_shopping_hours'] = [
      '#title' => $this->t('Show shopping hours'),
      '#type' => 'checkbox',
      '#description' => $this->t('Enable this option if you want to display shopping hours on the warning page.'),
      '#default_value' => $config->get('show_shopping_hours'),
    ];

    // Message.
    $form['message'] = [
      '#title' => $this->t('Message'),
      '#type' => 'textarea',
      '#description' => $this->t('Enter the message to display to users when your shop is disabled.'),
      '#default_value' => $config->get('message'),
    ];

    $form['#attached']['library'][] = 'commerce_shopping_hours/commerce_shopping_hours';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      if (strpos($key, 'y_from') || strpos($key, 'y_to')) {
        $date_obj = \DateTime::createFromFormat('d.m.Y H:i', '10.10.2010 ' . $value);
        if (!$date_obj) {
          $form_state->setErrorByName($key, $this->t('You must enter a valid time.'));
        }
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('commerce_shopping_hours.settings')
      ->set('monday_from', $values['monday_from'])
      ->set('monday_to', $values['monday_to'])
      ->set('tuesday_from', $values['tuesday_from'])
      ->set('tuesday_to', $values['tuesday_to'])
      ->set('wednesday_from', $values['wednesday_from'])
      ->set('wednesday_to', $values['wednesday_to'])
      ->set('thursday_from', $values['thursday_from'])
      ->set('thursday_to', $values['thursday_to'])
      ->set('friday_from', $values['friday_from'])
      ->set('friday_to', $values['friday_to'])
      ->set('saturday_from', $values['saturday_from'])
      ->set('saturday_to', $values['saturday_to'])
      ->set('sunday_from', $values['sunday_from'])
      ->set('sunday_to', $values['sunday_to'])
      ->set('show_shopping_hours', $values['show_shopping_hours'])
      ->set('message', $values['message'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
