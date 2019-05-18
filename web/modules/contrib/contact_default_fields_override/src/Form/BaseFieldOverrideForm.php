<?php

namespace Drupal\contact_default_fields_override\Form;

use Drupal\Core\Url;
use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class BaseFieldOverrideForm.
 *
 * @package Drupal\contact_default_fields_override\Form
 */
class BaseFieldOverrideForm extends FormBase {

  /**
   * The Drupal messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current contact_form entity we're editing.
   *
   * @var \Drupal\contact\Entity\ContactForm
   */
  protected $contactForm;

  /**
   * The base field definition we're editing.
   *
   * @var \Drupal\Core\Field\BaseFieldDefinition
   */
  protected $baseFieldDefinition;

  /**
   * The field definition we're editing.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * BaseFieldOverrideForm constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The dependency injection container.
   */
  public function __construct(ContainerInterface $container) {
    $this->messenger = $container->get('messenger');
    $routeMatch = $this->getRouteMatch();
    $this->contactForm = ContactForm::load($routeMatch->getParameter('contact_form'));

    $field_name = $routeMatch->getParameter('field_name');

    /* @var \Drupal\Core\Entity\EntityFieldManager $entityFieldManager */
    $entityFieldManager = $container->get('entity_field.manager');

    if (!$this->contactForm || empty($field_name)) {
      throw new NotFoundHttpException();
    }

    $baseFieldDefinitions = $entityFieldManager->getBaseFieldDefinitions('contact_message');
    $this->baseFieldDefinition = $baseFieldDefinitions[$field_name];

    $fieldDefinitions = $entityFieldManager->getFieldDefinitions('contact_message', $this->contactForm->id());
    $this->fieldDefinition = $fieldDefinitions[$field_name];
  }

  /**
   * Get the title for this form.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The form title.
   */
  public function getTitle() {
    return $this->t('%field settings for %bundle', [
      '%field' => $this->fieldDefinition->getLabel(),
      '%bundle' => $this->contactForm->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contact_default_fields_override_basefieldoverride_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $field_name = $this->fieldDefinition->getName();
    $currentOveriddenLabel = $this->contactForm->getThirdPartySetting('contact_default_fields_override', $field_name . '_label');

    $label = $this->fieldDefinition->getLabel();
    $description = $this->fieldDefinition->getDescription();

    if ($field_name === 'name' && empty($currentOveriddenLabel)) {
      // Not overidden yet. Use default values from \Drupal\contact\MessageForm.
      $label = $this->t('Your name');
      $description = '';
    }

    if ($field_name === 'mail' && empty($currentOveriddenLabel)) {
      // Not overidden yet. Use default values from \Drupal\contact\MessageForm.
      $label = $this->t('Your email address');
      $description = '';
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $label,
      '#required' => TRUE,
      '#weight' => -20,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Help text'),
      '#default_value' => $description,
      '#rows' => 5,
      '#description' => $this->t('Instructions to present to the user below this field on the editing form.<br />Allowed HTML tags: @tags', ['@tags' => FieldFilteredMarkup::displayAllowedTags()]) . '<br />' . $this->t('This field supports tokens.'),
      '#weight' => -10,
    ];

    $form['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required field'),
      '#default_value' => $this->fieldDefinition->isRequired(),
      '#weight' => -5,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger->addMessage($this->t('Saved %label configuration.', ['%label' => $this->fieldDefinition->getLabel()]));

    $field_name = $this->fieldDefinition->getName();

    $this->contactForm->setThirdPartySetting('contact_default_fields_override', $field_name . '_label', $form_state->getValue('label'));
    $this->contactForm->setThirdPartySetting('contact_default_fields_override', $field_name . '_description', $form_state->getValue('description'));

    $required = 1;
    if ($form_state->getValue('required') != 1) {
      $required = 0;
    }

    $this->contactForm->setThirdPartySetting('contact_default_fields_override', $field_name . '_required', $required);
    $this->contactForm->save();

    $url = Url::fromRoute('entity.contact_message.field_ui_fields', ['contact_form' => $this->contactForm->id()]);

    $form_state->setRedirectUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container
    );
  }

}
