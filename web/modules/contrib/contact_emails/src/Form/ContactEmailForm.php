<?php

namespace Drupal\contact_emails\Form;

use Drupal\contact\Entity\ContactForm;
use Drupal\contact_emails\ContactEmails;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the tax service add/edit form.
 */
class ContactEmailForm extends ContentEntityForm {

  /**
   * Drupal\contact_emails\ContactEmails definition.
   *
   * @var \Drupal\contact_emails\ContactEmails
   */
  protected $contactEmails;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager, ContactEmails $contactEmails) {
    parent::__construct($entity_manager);

    $this->contactEmails = $contactEmails;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('contact_emails.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $contact_form = $this->getContactForm($form_state);
    $email_fields = FALSE;
    $reference_fields = FALSE;

    if ($contact_form) {
      // Load fields related to this contact form.
      $email_fields = $this->contactEmails->getContactFormFields($contact_form->id(), 'email');
      $reference_fields = $this->contactEmails->getContactFormFields($contact_form->id(), 'entity_reference');

      // Store the contact form id.
      $form['contact_form']['widget'][0]['target_id']['#type'] = 'hidden';
      $form['contact_form']['widget'][0]['target_id']['#value'] = $contact_form->id();
    }

    // Add validation to the subject.
    $form['subject']['widget'][0]['value']['#element_validate'][] = 'token_element_validate';
    $form['subject']['widget'][0]['value']['#token_types'][] = 'contact_message';
    $form['subject']['widget'][0]['value']['#description'] = $this->t('The subject of the email. To add a variable, click within the above field and then click the "Browse available tokens" link below. You will find the fields of your forms within Contact Message. Note that tokens that produce html markup are not supported for an email subject.');

    // Token help for body field.
    $form['token_help_subject'] = [
      '#theme' => 'token_tree_link',
      '#weight' => -9,
      '#token_types' => ['contact_message'],
    ];

    // Body field.
    $form['message']['widget'][0] = array_merge($form['message']['widget'][0], [
      '#title' => $this->t('Message'),
      '#description' => $this->t('The body of the email.'),
      '#element_validate' => ['token_element_validate'],
      '#token_types' => ['contact_message'],
    ]);

    // Token help for body field.
    $form['token_help_body'] = [
      '#theme' => 'token_tree_link',
      '#weight' => -1,
      '#token_types' => ['contact_message'],
    ];

    // Recipient type.
    $form['recipient_type']['widget'][0]['value'] = array_merge($form['recipient_type']['widget'][0]['value'], [
      '#type' => 'select',
      '#options' => [
        'default' => $this->t('The website from address'),
        'submitter' => $this->t('The submitter of the form'),
        'field' => $this->t('The value of a specific field in the form'),
        'reference' => $this->t('The value of a specific field in an entity reference'),
        'manual' => $this->t('Specific email address(es)'),
      ],
      '#size' => NULL,
      '#description' => $this->t('Choose how to determine who the email recipient(s) should be.'),
    ]);

    // Recipient is a value in a field.
    if ($email_fields) {
      $form['recipient_field']['widget'][0]['value'] = array_merge($form['recipient_field']['widget'][0]['value'], [
        '#type' => 'select',
        '#options' => $email_fields,
        '#description' => $this->t('Send the email to the value of this field.'),
        '#size' => NULL,
      ]);
    }
    else {
      $form['recipient_field']['widget'][0]['value'] = array_merge($form['recipient_field']['widget'][0]['value'], [
        '#type' => 'item',
        '#title' => $this->t('No fields available'),
        '#description' => $this->t('You must have at least one email field available to use this option.'),
        '#size' => NULL,
      ]);
    }
    $form['recipient_field']['widget'][0]['value'] = array_merge($form['recipient_field']['widget'][0]['value'], [
      '#states' => [
        'visible' => [
          ':input[name="recipient_type[0][value]"]' => [
            'value' => 'field',
          ],
        ],
      ],
    ]);

    // Recipient is a value of a reference field.
    if ($reference_fields) {
      $form['recipient_reference']['widget'][0]['value'] = array_merge($form['recipient_reference']['widget'][0]['value'], [
        '#type' => 'select',
        '#options' => $reference_fields,
        '#description' => $this->t('Send the email to the value of this referenced field.'),
        '#size' => NULL,
      ]);
    }
    else {
      $form['recipient_reference']['widget'][0]['value'] = array_merge($form['recipient_reference']['widget'][0]['value'], [
        '#type' => 'item',
        '#title' => $this->t('No fields available'),
        '#description' => $this->t('You must have at least one referenced entity available that has at least one email field available to use this option.'),
      ]);
    }
    $form['recipient_reference']['widget'][0]['value'] = array_merge($form['recipient_reference']['widget'][0]['value'], [
      '#states' => [
        'visible' => [
          ':input[name="recipient_type[0][value]"]' => [
            'value' => 'reference',
          ],
        ],
      ],
    ]);

    // Recipient is manually set.
    $form['recipients']['widget'][0]['value'] = array_merge($form['recipients']['widget'][0]['value'], [
      '#states' => [
        'visible' => [
          ':input[name="recipient_type[0][value]"]' => [
            'value' => 'manual',
          ],
        ],
      ],
      '#description' => $this->t('Enter one or more recipients, separating multiple by commas.'),
    ]);

    // Recipient type.
    $form['reply_to_type']['widget'][0]['value'] = array_merge($form['reply_to_type']['widget'][0]['value'], [
      '#type' => 'select',
      '#options' => [
        'default' => $this->t('Email replies should go to the website from email address (default)'),
        'submitter' => $this->t('Email replies should go to the submitter of the form'),
        'field' => $this->t('Email replies should go to the value of a specific field in the form'),
        'reference' => $this->t('Email replies should go to the value of a specific field in an entity reference'),
        'manual' => $this->t('Email replies should go a specific email address'),
      ],
      '#size' => NULL,
      '#description' => $this->t('Choose how to determine where email replies should be sent.'),
    ]);

    // Reply-to is a value in a field.
    if ($email_fields) {
      $form['reply_to_field']['widget'][0]['value'] = array_merge($form['reply_to_field']['widget'][0]['value'], [
        '#type' => 'select',
        '#options' => $email_fields,
        '#description' => $this->t('Email replies should go to the value of this field. Please note that if the field is not required and is left blank by the user, the reply-to will be set as the default website email instead.'),
        '#size' => NULL,
      ]);
    }
    else {
      $form['reply_to_field']['widget'][0]['value'] = array_merge($form['reply_to_field']['widget'][0]['value'], [
        '#type' => 'item',
        '#title' => $this->t('No fields available'),
        '#description' => $this->t('You must have at least one email field available to use this option.'),
      ]);
    }
    $form['reply_to_field']['widget'][0]['value'] = array_merge($form['reply_to_field']['widget'][0]['value'], [
      '#states' => [
        'visible' => [
          ':input[name="reply_to_type[0][value]"]' => [
            'value' => 'field',
          ],
        ],
      ],
    ]);

    // Recipient is a value of a reference field.
    if ($reference_fields) {
      $form['reply_to_reference']['widget'][0]['value'] = array_merge($form['reply_to_reference']['widget'][0]['value'], [
        '#type' => 'select',
        '#options' => $reference_fields,
        '#description' => $this->t('Email replies should go to the value of this reference field. Please note that if the field is not required and is left blank by the user, the reply-to will be set as the default website email instead.'),
        '#size' => NULL,
      ]);
    }
    else {
      $form['reply_to_reference']['widget'][0]['value'] = array_merge($form['reply_to_reference']['widget'][0]['value'], [
        '#type' => 'item',
        '#title' => $this->t('No fields available'),
        '#description' => $this->t('You must have at least one referenced entity available that has at least one email field available to use this option.'),
      ]);
    }
    $form['reply_to_reference']['widget'][0]['value'] = array_merge($form['reply_to_reference']['widget'][0]['value'], [
      '#states' => [
        'visible' => [
          ':input[name="reply_to_type[0][value]"]' => [
            'value' => 'reference',
          ],
        ],
      ],
    ]);

    // Reply to is manually set.
    $form['reply_to_email']['widget'][0]['value'] = array_merge($form['reply_to_email']['widget'][0]['value'], [
      '#type' => 'email',
      '#states' => [
        'visible' => [
          ':input[name="reply_to_type[0][value]"]' => [
            'value' => 'manual',
          ],
        ],
      ],
      '#description' => $this->t('Enter the reply to email recipient.'),
    ]);

    return $form;
  }

  /**
   * Get the contact form from the current request.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return \Drupal\contact\ContactFormInterface
   *   The contact form.
   */
  protected function getContactForm(FormStateInterface $form_state) {
    /** @var \Drupal\contact_emails\Entity\ContactEmailInterface $entity */
    $entity = $this->entity;

    if (!$entity->get('contact_form')->isEmpty()) {
      $contactForm = $entity->get('contact_form')->target_id;
    }

    if (empty($contactForm) && $form_state->hasValue('contact_form')) {
      $value = $form_state->getValue('contact_form', NULL);
      $contactForm = (is_array($value)) ? $value[0]['target_id'] : NULL;
    }

    /** @var \Drupal\contact\ContactFormInterface $contactForm */
    if (empty($contactForm)) {
      $contactForm = \Drupal::routeMatch()->getParameter('contact_form');
    }

    if (is_string($contactForm)) {
      $contactForm = ContactForm::load($contactForm);
    }

    return $contactForm;
  }

  /**
   * Get body potentially with format.
   */
  protected function getBody($form, $form_state) {
    /** @var \Drupal\contact_emails\Entity\ContactEmailInterface $entity */
    $entity = $this->buildEntity($form, $form_state);
    return !$entity->get('message')->isEmpty() ? $entity->get('message') : '';
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    drupal_set_message($this->t('Saved the %label contact email.', [
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirect('entity.contact_email.collection', [
      'contact_form' => $form_state->getValue('contact_form')[0]['target_id'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Require recipients if manual recipient type.
    if ($values['recipient_type'][0]['value'] == 'manual' && !$values['recipients'][0]['value']) {
      $form_state->setErrorByName('recipients', $this->t('Please add at least one recipient.'));
    }

    // Require field if field recipient type.
    if ($values['recipient_type'][0]['value'] == 'field' && !$values['recipient_field'][0]['value']) {
      $form_state->setErrorByName('recipient_field', $this->t('Please select a field with the email type to use this recipient type.'));
    }

    // Require reference field if reference recipient type.
    if ($values['recipient_type'][0]['value'] == 'reference' && !$values['recipient_reference'][0]['value']) {
      $form_state->setErrorByName('recipient_reference', $this->t('Please select a referenced field with the email type to use this recipient type.'));
    }

    // Require field if field reply-to type.
    if ($values['reply_to_type'][0]['value'] == 'field' && !$values['reply_to_field'][0]['value']) {
      $form_state->setErrorByName('reply_to_field', $this->t('Please select a field with the email type to use this reply-to type.'));
    }

    // Require email if email reply-to type.
    if ($values['reply_to_type'][0]['value'] == 'manual' && !$values['reply_to_email'][0]['value']) {
      $form_state->setErrorByName('reply_to_email', $this->t('Please enter a reply-to email address to use this reply-to type.'));
    }

    // Require reference field if reference reply to type.
    if ($values['reply_to_type'][0]['value'] == 'reference' && !$values['reply_to_reference'][0]['value']) {
      $form_state->setErrorByName('reply_to_reference', $this->t('Please select a reference field to use this reply-to type.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    /** @var \Drupal\contact_emails\ContactEmailStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('contact_email');

    // Warn the user if they are disabling the core contact emails for the
    // first time.
    $has_emails = $storage->hasContactEmails($values['contact_form'][0]['target_id'], TRUE);
    if (!$has_emails && $this->entity->isNew()) {
      drupal_set_message($this->t('The default contact email from the form settings has been disabled and your new email has replaced it.'), 'warning');
    }

    // Save the contact email and rebuild the cache.
    parent::submitForm($form, $form_state);
    $this->contactEmails->rebuildCache();
  }

}
