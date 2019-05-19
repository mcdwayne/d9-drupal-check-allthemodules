<?php

namespace Drupal\user_csv_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\RoleInterface;
use Drupal\user_csv_import\Controller\UserCsvImportController;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManager;

/**
 * Provides methods to define and build the user import form.
 */
class UserCsvImportForm extends FormBase {

  /**
   * Provides messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Provides user entity.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityManager;

  /**
   * User import Form constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   * @param \Drupal\Core\Entity\EntityFieldManager $entityManager
   */
  public function __construct(MessengerInterface $messenger, EntityFieldManager $entityManager) {
    $this->messenger = $messenger;
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('messenger'),
      $container->get('entity_field.manager')
    );

  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {

    return 'user_csv_import_form';

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   Return the form object.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#tree'] = TRUE;

    // Options field set.
    $form['config_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Options'),
    ];

    // Roles field.
    $roles = user_role_names();
    unset($roles['anonymous']);

    $form['config_options']['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#options' => $roles,
    ];

    // Special handling for the inevitable "Authenticated user" role.
    $form['config_options']['roles'][RoleInterface::AUTHENTICATED_ID] = [
      '#default_value' => TRUE,
      '#disabled' => TRUE,
    ];

    // Default password.
    $form['config_options']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default password'),
      '#required' => TRUE,
    ];

    // Status.
    $form['config_options']['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => [
        '0' => $this->t('Blocked'),
        '1' => $this->t('Active'),
      ],
    ];

    // Fields field set.
    $form['config_fields'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Fields'),
    ];

    // Get user entity fields.
    $user_fields = $this->filterDefaultFields($this->entityManager->getFieldStorageDefinitions('user'));

    // Construct values for checkboxes.
    $selectable_fields = [];

    foreach ($user_fields as $field) {
      $selectable_fields[$field->getName()] = $field->getLabel();
    }

    // Select all fields.
    $form['config_fields']['check_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('All'),
    ];

    // User form fields.
    $form['config_fields']['fields'] = [
      '#type' => 'checkboxes',
      '#options' => $selectable_fields,
    ];

    // File to upload.
    $form['file'] = [
      '#type' => 'file',
      '#title' => 'CSV file upload',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import users'),
      '#button_type' => 'primary',
    ];

    // By default, render the form using theme_system_config_form().
    $form['#theme'] = 'system_config_form';

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Get form data.
    $roles = $form_state->getValue(['config_options', 'roles']);
    $fields = $form_state->getValue(['config_fields', 'fields']);

    // Filter vales and clean empty.
    $roles_selected = array_filter($roles, function ($item) {
      return ($item);
    });

    $fields_selected = array_filter($fields, function ($item) {
      return ($item);
    });

    // If there is no options selected, show the error.
    if (empty($roles_selected)) {

      $form_state->setErrorByName('roles', $this->t('Please select at least one role to apply to the imported user(s).'));

    }
    elseif (empty($fields_selected)) {

      $form_state->setErrorByName('fields', $this->t('Please select at least one field to apply to the imported user(s).'));

      // If "mail" and "name" fields are not selected, show an error.
    }
    elseif (!array_key_exists('mail', $fields_selected) or !array_key_exists('name', $fields_selected)) {

      $form_state->setErrorByName('roles', $this->t('The email and username fields is required'));
    }

    // Validate file.
    $this->file = file_save_upload('file', $form['file']['#upload_validators']);

    if (!$this->file[0]) {

      $form_state->setErrorByName('file');

    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get form data.
    $file = $this->file[0];
    $roles = $form_state->getValue(['config_options', 'roles']);
    $fields = $form_state->getValue(['config_fields', 'fields']);

    // Construct data to send to the controller.
    $config = [
      'roles' => array_filter($roles, function ($item) {
        return ($item);
      }),
      'fields' => array_filter($fields, function ($item) {
        return ($item);
      }),
      'password' => $form_state->getValue(['config_options', 'password']),
      'status' => $form_state->getValue(['config_options', 'status']),
    ];

    // Return success message.
    if ($created = UserCsvImportController::processUpload($file, $config)) {

      $this->messenger->addMessage($this->t('Successfully imported @count users.', ['@count' => count($created)]));
    }

    else {

      // Return error message.
      $this->messenger->addMessage($this->t('No users imported.'));
    }

    // Redirect to admin users page.
    $form_state->setRedirectUrl(new Url('entity.user.collection'));

  }

  /**
   * Unset user account default fields.
   */
  private function filterDefaultFields($fields) {

    unset($fields['uid']);
    unset($fields['uuid']);
    unset($fields['langcode']);
    unset($fields['preferred_langcode']);
    unset($fields['preferred_admin_langcode']);
    unset($fields['pass']);
    unset($fields['timezone']);
    unset($fields['status']);
    unset($fields['created']);
    unset($fields['changed']);
    unset($fields['access']);
    unset($fields['login']);
    unset($fields['init']);
    unset($fields['roles']);
    unset($fields['default_langcode']);
    unset($fields['user_picture']);

    return $fields;

  }

}
