<?php

namespace Drupal\bulk_copy_fields\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\LanguageInterface;

/**
 * Bulk Copy Fields Form.
 */
class BulkCopyFieldsForm extends FormBase implements FormInterface {

  /**
   * Set a var to make stepthrough form.
   *
   * @var step
   */
  protected $step = 0;
  /**
   * Keep track of user input.
   *
   * @var userInput
   */
  protected $userInput = [];

  /**
   * To store input.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  private $sessionManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Constructs a \Drupal\bulk_copy_fields\Form\BulkCopyFieldsForm.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   Function construct temp store factory.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   Function construct session manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Function construct current user.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   Route.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   LanguageManager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, SessionManagerInterface $session_manager, AccountInterface $current_user, RouteBuilderInterface $route_builder, LanguageManager $language_manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;
    $this->routeBuilder = $route_builder;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('session_manager'),
      $container->get('current_user'),
      $container->get('router.builder'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_copy_fields_form';
  }

  /**
   * {@inheritdoc}
   */
  public function bulkCopyFields() {
    $entities = $this->userInput['entities'];
    $fields = $this->userInput['fields'];
    $languages = $this->userInput['languages'];
    $batch = [
      'title' => $this->t('Updating Fields...'),
      'operations' => [
        [
          '\Drupal\bulk_copy_fields\BulkCopyFields::copyFields',
          [$entities, $fields, $languages],
        ],
      ],
      'finished' => '\Drupal\bulk_copy_fields\BulkCopyFields::bulkCopyFieldsFinishedCallback',
    ];
    batch_set($batch);
    return 'All fields were copied successfully';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    switch ($this->step) {
      case 0:
        $form_state->setRebuild();
        break;
      case 1:
        $form_state->setRebuild();
        break;

      case 2:
        $data_to_process = array_diff_key(
                            $form_state->getValues(),
                            array_flip(
                              [
                                'op',
                                'submit',
                                'form_id',
                                'form_build_id',
                                'form_token',
                              ]
                            )
                          );
        $this->userInput['fields'] = array_merge($this->userInput['fields'], $data_to_process);
        $form_state->setRebuild();
        break;

      case 3:
        if (method_exists($this, 'bulkCopyFields')) {
          $return_verify = $this->bulkCopyFields();
        }
        drupal_set_message($return_verify);
        $this->routeBuilder->rebuild();
        break;

    }
    $this->step++;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (isset($this->form)) {
      $form = $this->form;
    }
    $form['#title'] = $this->t('Bulk Copy Fields');
    $submit_label = 'Next';

    switch ($this->step) {
      case 0:
        // Retrieve IDs from the temporary storage.
        $this->userInput['entities'] = $this->tempStoreFactory
          ->get('bulk_copy_fields_ids')
          ->get($this->currentUser->id());
        $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_ALL);
        $options = [];
        foreach($languages as $language) {
          $options[$language->getId()]['field_name'] = $language->getName();
        }
        $header = [
          'field_name' => $this->t('Installed Languages to use Bulk Copy Fields on'),
        ];
        $form['#title'] .= ' - ' . $this->t('Select Languages to Copy Values From and To');
        $form['table'] = [
          '#type' => 'tableselect',
          '#header' => $header,
          '#options' => $options,
          '#empty' => $this->t('No languages found'),
        ];
        break;
      case 1:
        $options = [];
        foreach ($this->userInput['entities'] as $entity) {
          $this->entity = $entity;
          $fields = $entity->getFieldDefinitions();
          foreach ($fields as $field) {
            if ($field->getFieldStorageDefinition()->isBaseField() === FALSE && !isset($options[$field->getName()])) {
              $options[$field->getName()]['field_name'] = $field->getName();
            }
          }
        }
        $header = [
          'field_name' => $this->t('Field Name'),
        ];
        $form['#title'] .= ' - ' . $this->t('Select Fields to Copy Values From');
        $form['table'] = [
          '#type' => 'tableselect',
          '#header' => $header,
          '#options' => $options,
          '#empty' => $this->t('No fields found'),
        ];
        break;

      case 2:
        foreach ($this->userInput['fields'] as $field_name) {
          $options = $this->userInput['field_options'][$field_name];
          $form[$field_name] = [
            '#type' => 'select',
            '#title' => $this->t('From Field @field_name To Field:', ['@field_name' => $field_name]),
            '#options' => $options,
            '#default_value' => $options[$field_name],
          ];
        }
        $form['#title'] .= ' - ' . $this->t('Enter New Field to copy values to');
        break;

      case 3:
        $form['#title'] .= ' - ' . $this->t('Are you sure you want to copy @count_fields fields on @count_entities entities?',
                                     [
                                       '@count_fields' => count($this->userInput['fields']),
                                       '@count_entities' => count($this->userInput['entities']),
                                     ]);
        $submit_label = 'Copy Fields';
        break;

    }
    drupal_set_message($this->t('This module is experiemental. PLEASE do not use on production databases without prior testing and a complete database dump.'), 'warning');
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $submit_label,
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    switch ($this->step) {
      case 0:
        $this->userInput['languages'] = array_filter($form_state->getValues()['table']);
        if (empty($this->userInput['languages'])) {
          $form_state->setError($form, $this->t('No languages selected.'));
        }
        break;
      case 1:
        // Get all fields possible.
        $all_options = [];
        $field_types = [];
        $this->userInput['fields'] = array_filter($form_state->getValues()['table']);
        if (empty($this->userInput['fields'])) {
          $form_state->setError($form, $this->t('No fields selected.'));
        }
        // Match field types.
        foreach ($this->userInput['entities'] as $entity) {
          $fields = $entity->getFieldDefinitions();
          foreach ($fields as $field) {
            if ($field->getFieldStorageDefinition()->isBaseField() === FALSE) {
              $type = $field->getType();
              // Allow er rev to map with er.
              if (strpos($type, 'entity_reference_revisions') !== FALSE) {
                $type = 'entity_reference';
              }
              // Allow string_long to map with text_with_summary and vice versa.
              if (in_array($type, ['string_long', 'text_with_summary'])) {
                $type = 'string_long_or_text_with_summary';
              }
              $all_options[$field->getName()] = $type;
              $field_types[$type][$field->getName()] = $field->getName();
            }
          }
        }
        // Unset same field and throw error if no fields to copy to.
        foreach ($this->userInput['fields'] as $field_name) {
          $this->userInput['field_options'][$field_name] = array_unique($field_types[$all_options[$field_name]]);
          unset($this->userInput['field_options'][$field_name][$field_name]);
          if (empty($this->userInput['field_options'][$field_name])) {
            $form_state->setError($form, $this->t('No fields of the same type to copy to on @field', ['@field' => $field_name]));
          }
        }
      break;
    }
  }

}
