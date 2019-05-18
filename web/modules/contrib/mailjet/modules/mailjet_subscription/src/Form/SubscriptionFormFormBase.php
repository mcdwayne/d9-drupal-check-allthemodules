<?php

namespace Drupal\mailjet_subscription\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ingroup mailjet_subscription
 */
class SubscriptionFormFormBase extends EntityForm {

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;


  public function __construct(QueryFactory $query_factory) {
    $this->entityQueryFactory = $query_factory;
  }


  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.query'));
  }


  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    $form['primary_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Title and description'),
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['primary_settings']['name'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#description' => t('Subscription form title'),
      '#size' => 35,
      '#maxlength' => 32,
      '#default_value' => $entity->name,
      '#required' => TRUE,
    ];

    $form['description'] = [];
    $form['primary_settings']['description'] = [
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#default_value' => $entity->description,
      '#rows' => 2,
      '#maxlength' => 700,
      '#description' => t('Description shown below the subscription form title (max. 700 characters)'),
    ];


    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Form Settings'),
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['email_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Subscription confirmation email'),
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['form_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Confirmation and error messages'),
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['sumbit_label'] = [];
    $form['settings']['sumbit_label'] = [
      '#type' => 'textfield',
      '#title' => t('Submit button label'),
      '#required' => 'TRUE',
      '#default_value' => $entity->sumbit_label,
    ];

    $form['destination_page'] = [];
    $form['settings']['destination_page'] = [
      '#type' => 'textfield',
      '#title' => t('Form destination page'),
      '#description' => 'Leave blank to stay on the form page.  Example: <b>node/300</b> ',
      '#default_value' => $entity->destination_page,
    ];

    $form['confirmation_message'] = [];
    $form['form_settings']['confirmation_message'] = [
      '#type' => 'textfield',
      '#title' => t('Confirmation message'),
      '#description' => t('Subscription confirmation email sent to %. Please check your inbox and confirm the subscription.<br /><b>Note:</b> The % symbol is a placeholder for the email of the subscriber.'),
      '#default_value' => $entity->confirmation_message,
    ];

    $form['error_token'] = [];
    $form['form_settings']['error_token'] = [
      '#type' => 'textfield',
      '#title' => t('Error. Token verification failed.'),
      '#description' => '',
      '#default_value' => $entity->error_token,
    ];


    $form['confirmation_email_text'] = [];
    $form['email_settings']['confirmation_email_text'] = [
      '#type' => 'textfield',
      '#title' => t('Email heading text'),
      '#description' => '',
      '#default_value' => $entity->confirmation_email_text,
    ];

    $form['email_text_button'] = [];
    $form['email_settings']['email_text_button'] = [
      '#type' => 'textfield',
      '#title' => t('Subscription confirmation button text'),
      '#description' => '',
      '#default_value' => $entity->email_text_button,
    ];

    $form['email_text_description'] = [];
    $form['email_settings']['email_text_description'] = [
      '#type' => 'textfield',
      '#title' => t('Email body text'),
      '#description' => '',
      '#default_value' => $entity->email_text_description,
    ];

    $form['email_text_thank_you'] = [];
    $form['email_settings']['email_text_thank_you'] = [
      '#type' => 'textfield',
      '#title' => t('Thanks,'),
      '#description' => '',
      '#default_value' => $entity->email_text_thank_you,
    ];

    $form['email_owner'] = [];
    $form['email_settings']['email_owner'] = [
      '#type' => 'textfield',
      '#title' => t('Owner name'),
      '#description' => ('Site owner name, shown at the end of the subscription confirmation email.'),
      '#default_value' => $entity->email_owner,
    ];


    $form['subscribe_error'] = [];
    $form['form_settings']['subscribe_error'] = [
      '#type' => 'textfield',
      '#title' => t('Subscription failed. Please try again later!'),
      '#description' => t('Shown in case an error occurs during subscription form submission.'),
      '#default_value' => $entity->subscribe_error,
    ];

    $form['contact_exist'] = [];
    $form['form_settings']['contact_exist'] = [
      '#type' => 'textfield',
      '#title' => t('The contact % is already subscribed'),
      '#default_value' => $entity->contact_exist,
      '#description' => t('Shown when the subscriber already exists in your Mailjet contact list.<br /><b>Note:</b> The % symbol is a placeholder for the email address of the subscriber.'),
    ];

    $form['success_message_subsribe'] = [];
    $form['form_settings']['success_message_subsribe'] = [
      '#type' => 'textfield',
      '#title' => t('"Thanks for subscribing" message'),
      '#description' => t('Shown when the subscriber is successfully added to the contact list.'),
      '#default_value' => $entity->success_message_subsribe,
    ];

    $form['email_footer_text'] = [];
    $form['email_settings'] ['email_footer_text'] = [
      '#type' => 'textfield',
      '#title' => t('Email footer text'),
      '#description' => '',
      '#default_value' => $entity->email_footer_text,
    ];

    $form['error_data_types'] = [];
    $form['form_settings'] ['error_data_types'] = [
      '#type' => 'textfield',
      '#title' => t('Data type mismatch error'),
      '#description' => t('Incorrect data values. Please enter correct data type in %id <br /><b>Note:</b> The %id symbol is a placeholder for the field name.'),
      '#default_value' => $entity->error_data_types,
    ];


    $form['mailjet_lists'] = [
      '#type' => 'fieldset',
      '#title' => t('Mailjet List Selection & Configuration'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $lists_mailjet = mailjet_get_lists();

    $form['mailjet_lists']['ml_lists'] = [
      '#type' => 'select',
      '#title' => t('Mailjet contact list'),
      '#description' => t('Select a contact list where all new subscribers will be added. You can create additional contact lists in Mailjet.'),
      '#options' => $lists_mailjet,
      '#required' => TRUE,
      '#default_value' => $entity->lists,
    ];

    $mailjet_properties = mailjet_get_properties();
    $counter_prop = 0;

    foreach ($mailjet_properties as $key => $prop) {

      if (strpos($prop, 'message') !== FALSE) {
        unset($mailjet_properties[$key]);
      }
      elseif (strpos($prop, 'commerce') !== FALSE) {
        unset($mailjet_properties[$key]);
      }
      elseif (strpos($prop, 'mailjet') !== FALSE) {
        unset($mailjet_properties[$key]);
      }
      elseif (strpos($prop, 'signup') !== FALSE) {
        unset($mailjet_properties[$key]);
      }
      $counter_prop++;
    }

    $form['mailjet_lists']['sort_fields'] = [];
    $form['mailjet_lists']['sort_fields'] = [
      '#type' => 'textfield',
      '#title' => t('You can sort the fields in your subscription form. To do that, enter their names in the desired order in the field below separated by <b>comma</b>.'),
      '#description' => t('Example: You have selected firstname, lastname and age. You wish to display them in <b>alphabetical order</b>. Do so by entering: <b>age, firstname, lastname</b>.<br /> <b>Note:</b> The email field is always shown in first position.'),
      '#default_value' => $entity->sort_fields,
    ];

    $form['mailjet_lists']['ml_fields'] = [
      '#type' => 'checkboxes',
      '#title' => t('Contact properties - select which contact properties to show in your subscription form'),
      '#description' => '',
      '#options' => $mailjet_properties,
      '#default_value' => explode(',', $entity->fields_mailjet),
      '#max' => 3,
    ];

    $form['js_field'] = [
      '#type' => 'textarea',
      '#title' => t('Enter your inline JS here.'),
      '#description' => '',
      '#default_value' => $entity->js_field,
    ];

    $form['css_field'] = [
      '#type' => 'textarea',
      '#title' => t('Enter your inline CSS here.'),
      '#description' => '',
      '#default_value' => $entity->css_field,
    ];


    if ($entity->isNew()) {
      $entity_id = 'mailjet_subscription_form_' . rand(1, 1000000) . '_' . rand(1, 1000000);
    }
    else {
      $entity_id = $entity->id();
    }

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $entity_id,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique and can only contain lowercase letters, numbers, and underscores. It can not be the reserved word "custom".',
      ],
      '#disabled' => TRUE,
    ];


    if ($entity->isNew()) {
      $created_date = date('Y/m/d', time());
    }
    else {
      $created_date = $entity->created_date;
    }

    $form['created_date'] = [
      '#type' => 'hidden',
      '#default_value' => $created_date,
    ];

    $form['changed_date'] = [
      '#type' => 'hidden',
      '#default_value' => date('Y/m/d', time()),
    ];


    // Return the form.
    return $form;
  }


  public function exists($entity_id, array $element, FormStateInterface $form_state) {

    $query = $this->entityQueryFactory->get('mailjet_subscription_form');

    // Query the entity ID to see if its in use.
    $result = $query->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();

    // We don't need to return the ID, only if it exists or not.
    return (bool) $result;
  }


  protected function actions(array $form, FormStateInterface $form_state) {
    // Get the basic actins from the base class.
    $actions = parent::actions($form, $form_state);

    // Change the submit button text.
    $actions['submit']['#value'] = $this->t('Save');

    // Return the result.
    return $actions;
  }


  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);

  }


  public function save(array $form, FormStateInterface $form_state) {

    $arr_selected_prop = array_filter($form_state->getValue('ml_fields'));

    $string_fields = implode(',', $arr_selected_prop);

    $entity = $this->getEntity();
    $entity->set('name', $form_state->getValue('primary_settings')['name']);
    $entity->set('description', $form_state->getValue('primary_settings')['description']);

    $entity->set('sumbit_label', $form_state->getValue('settings')['sumbit_label']);
    $entity->set('destination_page', $form_state->getValue('settings')['destination_page']);

    $entity->set('confirmation_message', $form_state->getValue('form_settings')['confirmation_message']);
    $entity->set('error_token', $form_state->getValue('form_settings')['error_token']);
    $entity->set('success_message_subsribe', $form_state->getValue('form_settings')['success_message_subsribe']);
    $entity->set('error_data_types', $form_state->getValue('form_settings')['error_data_types']);
    $entity->set('contact_exist', $form_state->getValue('form_settings')['contact_exist']);
    $entity->set('subscribe_error', $form_state->getValue('form_settings')['subscribe_error']);

    $entity->set('confirmation_email_text', $form_state->getValue('email_settings')['confirmation_email_text']);
    $entity->set('email_footer_text', $form_state->getValue('email_settings')['email_footer_text']);
    $entity->set('email_owner', $form_state->getValue('email_settings')['email_owner']);
    $entity->set('email_text_thank_you', $form_state->getValue('email_settings')['email_text_thank_you']);
    $entity->set('email_text_description', $form_state->getValue('email_settings')['email_text_description']);
    $entity->set('email_text_button', $form_state->getValue('email_settings')['email_text_button']);

    $entity->set('sort_fields', $form_state->getValue('sort_fields'));

    $entity->set('lists', $form_state->getValue('ml_lists'));
    $entity->set('fields_mailjet', $string_fields);

    $entity->set('created_date', $form_state->getValue('created_date'));
    $entity->set('changed_date', $form_state->getValue('changed_date'));

    if (count($arr_selected_prop) > 3) {
      drupal_set_message(t('You may add a maximum of 3 contact properties in your subscription form. Please update your selection and save the form again.'), 'error');
      return;
    }

    $status = $entity->save();

    $url = $entity->urlInfo();

    // Create an edit link.
    $edit_link = Link::fromTextAndUrl($this->t('Edit'), $url)->toString();

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('Subscription Form %label has been updated.', ['%label' => $entity->label()]));
    }
    else {

      drupal_set_message($this->t('Subscription Form %label has been added.', ['%label' => $entity->label()]));
    }

    $form_state->setRedirect('entity.mailjet_subscription_form.list');
  }

}
