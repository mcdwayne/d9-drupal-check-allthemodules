<?php

namespace Drupal\fbl\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FblConfiguration.
 *
 * @package Drupal\fbi\Form
 */
class FblConfiguration extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new EntityFieldManager.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityFieldManagerInterface $entity_manager) {
    $this->entityFieldManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_fbl_configuration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fbl.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Getting the configuration value.
    $default_value_config = $this->config('fbl.settings');
    $default_value = $default_value_config->get('field_based_login');
    $form['field_based_login'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Field based login Configurations'),
      '#collapsible' => FALSE,
      '#tree' => TRUE,
    ];
    // Todo : in feature profile 2 should work :
    $entity_type_id = 'user';
    $bundle = 'user';
    $bundleFields = [];
    foreach ($this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        if ($field_definition->getType() == 'string' || $field_definition->getType() == 'integer' || $field_definition->getType() == 'telephone') {
          $bundleFields[$field_name] = $field_definition->getLabel();
        }
      }
    }
    $form['field_based_login']['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Unique field'),
      '#options' => $bundleFields,
      '#empty_option' => '- Select -',
      '#default_value' => isset($default_value['field']) ? $default_value['field'] : '',
      '#description' => $this->t('Unique field to allow users to login with this field. Note : Selected field will become unique filed.'),
    ];

    $form['field_based_login']['allow_user_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow login with username'),
      '#default_value' => isset($default_value['allow_user_name']) ? $default_value['allow_user_name'] : 1,
    ];

    $form['field_based_login']['allow_user_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow login with E-mail'),
      '#default_value' => isset($default_value['allow_user_email']) ? $default_value['allow_user_email'] : '',
    ];

    $form['field_based_login']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User login form - User name field Label'),
      '#default_value' => isset($default_value['label']) ? $default_value['label'] : '',
      '#description' => $this->t('Ex: Phone / E-mail'),
      '#size' => 60,
      '#maxlength' => 60,
    ];

    // Added description field for configuration.
    $form['field_based_login']['field_desc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User login form - User name field Description'),
      '#default_value' => isset($default_value['field_desc']) ? $default_value['field_desc'] : '',
      '#description' => $this->t('Ex: Provide description for custom login field'),
      '#size' => 60,
      '#maxlength' => 60,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $field_name = $form_state->getValue(['field_based_login', 'field']);
    $allow_user_login_by_name = $form_state->getValue(['field_based_login', 'allow_user_name']);
    if (isset($field_name) && empty($field_name) && ($allow_user_login_by_name == 0) && ($allow_user_login_by_name == 0)) {
      $form_state->setErrorByName('field_based_login][field', $this->t('Please select any one of the option to login'));
    }
    $user_count = _fbl_user_count();
    if (isset($field_name) && !empty($field_name)) {
      $field_data_count = _fbl_field_data_count($field_name);
    }
    $entity_type_id = 'user';
    $bundle = 'user';
    foreach ($this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle) as $field_name_value => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        if ($field_name_value == $field_name) {
          $is_required = $field_definition->isRequired();
        }
      }
    }
    if (!empty($field_name)) {
      if ($is_required == '') {
        drupal_set_message($this->t('Selected field is not mandatory.'), 'warning');
      }
      if ($user_count > $field_data_count) {
        drupal_set_message($this->t('Selected field is not having data for some of the user'), 'warning');
      }
      if (_fbl_check_for_duplicates($field_name)) {
        $form_state->setErrorByName('field_based_login][field', $this->t('Selected field is not unique. There are duplicates found, Please select other field.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('fbl.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

}

/**
 * Helper function to check duplicate records of user data.
 *
 * @param string $field_name
 *   Machine name of the user account field.
 */
function _fbl_check_for_duplicates($field_name) {
  $table_name = 'user__' . $field_name;
  $table_column = $field_name . '_value';
  $connection = \Drupal::database();
  $query = $connection->select($table_name, 't');
  $query->fields('t', [$table_column]);
  $query->groupBy('t.' . $table_column . '');
  $query->condition('t.bundle', 'user');
  $query->addExpression('COUNT(' . $table_column . ')', 'field_count');
  $query->range(0, 1);
  $duplicate_count = $query->execute()->fetchAll();
  foreach ($duplicate_count as $count) {
    $count = $count->field_count;
  }
  // Todo : exception handling  ??
  if ($count > 1) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Returns number of user.
 *
 * @return string
 *   Returns User
 */
function _fbl_user_count() {
  $connection = \Drupal::database();
  $query = $connection->select('users', 'u');
  $query->fields('u', ['uid']);
  $query->condition('u.uid', '0', '!=');
  $user_count = $query->countQuery()->execute()->fetchField();
  return $user_count;
}

/**
 * Helper function to count.
 *
 * @param string $field_name
 *   Machine name of the user account field.
 *
 * @return int
 *   Returns how many fields having value
 */
function _fbl_field_data_count($field_name) {
  $table_name = 'user__' . $field_name;
  $table_column = $field_name . '_value';
  $connection = \Drupal::database();
  $query = $connection->select($table_name, 'field_value');
  $query->fields('field_value', [$table_column]);
  $query->condition('field_value.bundle', 'user');
  $field_data_count = $query->countQuery()->execute()->fetchField();
  return $field_data_count;
}
