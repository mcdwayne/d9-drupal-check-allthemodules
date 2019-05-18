<?php

namespace Drupal\edit_in_place_field\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\edit_in_place_field\Ajax\RebindJSCommand;
use Drupal\edit_in_place_field\Ajax\StatusMessageCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class EditInPlaceFieldReferenceForm.
 *
 * @package Drupal\edit_in_place_field\Form
 */
class EditInPlaceFieldReferenceForm extends FormBase {

  const ERROR_INVALID_DATA = 'invalid_data';
  const ERROR_DATA_CANNOT_BE_SAVED = 'data_cannot_be_saved';
  const ERROR_ENTITY_CANNOT_BE_LOADED = 'entity_cannot_be_loaded';
  const ERROR_UPDATE_NOT_ALLOWED = 'update_not_allowed';

  /**
   * Entity type manager service.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_in_place_field_reference_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $data = []) {
    $form = [
      'in_place_field' => $this->getInPlaceField($data),
      'field_name' => [
        '#type' => 'hidden',
        '#value' => $data['field_name'],
      ],
      'entity_type' => [
        '#type' => 'hidden',
        '#value' => $data['entity_type'],
      ],
      'entity_id' => [
        '#type' => 'hidden',
        '#value' => $data['entity_id'],
      ],
      'ajax_replace' => [
        '#type' => 'hidden',
        '#value' => $data['ajax_replace'],
      ],
      'label_substitution' => [
        '#type' => 'hidden',
        '#value' => $data['label_substitution'],
      ],
      'actions' => [
        '#type' => 'fieldgroup',
        'save' => [
          '#type' => 'button',
          '#value' => 'Save',
          '#attributes' => [
            'class' => [
              'edit-in-place-save'
            ]
          ],
          '#ajax' => array(
            'callback' => [$this,'inPlaceAction'],
            'event' => 'click',
          ),
        ],
        'cancel' => [
          '#type' => 'button',
          '#value' => 'Cancel',
          '#attributes' => [
            'class' => [
              'edit-in-place-cancel'
            ],
            '#submit' => ['EditInPlaceFieldReferenceForm::cancelCallback'],
          ]
        ],
      ]
    ];
    return $form;
  }

  public function cancelCallback(){}

  /**
   * Generate a Ajax response or error.
   *
   * @param null $error_type
   *    Error type in case of error.
   * @param array $data
   *    Data used to process error messages.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *    Ajax response object.
   */
  protected function getResponse($error_type = NULL, $data = []) {
    $message = $this->t('Data saved successfully.');
    $message_type = Messenger::TYPE_STATUS;

    switch($error_type) {
      case self::ERROR_DATA_CANNOT_BE_SAVED:
        $message = $this->t('Data cannot be saved @error.',
          ['@error' => isset($data['error'])?$data['error']:'']
        );
        $message_type = Messenger::TYPE_ERROR;
        break;

      case self::ERROR_ENTITY_CANNOT_BE_LOADED:
        $message = $this->t('Entity @entity_id of type @entity_type cannot be loaded.',[
            '@entity_type' => isset($data['entity_type'])?$data['entity_type']:'',
            '@entity_id' => isset($data['entity_id'])?$data['entity_id']:'',
          ]
        );
        $message_type = Messenger::TYPE_WARNING;
        break;

      case self::ERROR_INVALID_DATA:
        $message = $this->t('Invalid data (field name: @field_name, entity_type: @entity_type, entity_id: @entity_id).',[
            '@field_name' => isset($data['field_name'])?$data['field_name']:'',
            '@entity_type' => isset($data['entity_type'])?$data['entity_type']:'',
            '@entity_id' => isset($data['entity_id'])?$data['entity_id']:'',
          ]
        );
        $message_type = Messenger::TYPE_WARNING;
        break;

      case self::ERROR_UPDATE_NOT_ALLOWED:
        $message = $this->t('Update not allowed for user @username..',[
            '@username' => isset($data['username'])?$data['username']:'',
          ]
        );
        $message_type = Messenger::TYPE_WARNING;
        break;
    }

    if ($message_type === Messenger::TYPE_WARNING) {
      $this->logger('edit_in_place_field')->warning($message);
    }

    $response = new AjaxResponse();
    $response->addCommand(new StatusMessageCommand($message_type, $message));
    return $response;
  }

  /**
   * Get the field select to change selected entity.
   *
   * @param $data
   *    Data to be pass to the build form method.
   *
   * @return array
   *    Render array of the choice field.
   */
  protected function getInPlaceField ($data) {
    $choice_field = [
      '#type' => 'edit_in_place_field_select',
      '#options' => $data['choice_list'],
      '#value' => $data['selected'],
      '#name' => 'in_place_field[]',
      '#attributes' => [
        'multiple' => ($data['cardinality'] !== 1)?TRUE:FALSE,
      ],
    ];
    return $choice_field;
  }

  /**
   * Check access to the form.
   *
   * @return bool|\Drupal\Core\Ajax\AjaxResponse
   *    TRUE if access is allowed or ajax response if access is denied.
   */
  protected function accessAllowed() {
    if (!\Drupal::currentUser()->hasPermission('edit in place field editing permission')) {
      return $this->getResponse(self::ERROR_UPDATE_NOT_ALLOWED, [
        'username' => \Drupal::currentUser()->getAccountName()
      ]);
    }
    return TRUE;
  }

  /**
   * Save data from ajax request.
   *
   * @param array $form
   *    Render array of Drupal form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *    Form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *    Ajax response object.
   */
  public function inPlaceAction(array &$form, FormStateInterface $form_state) {
    $access = $this->accessAllowed();
    if ($access !== TRUE) {
      return $access;
    }
    $data = $this->processRequest();
    return $this->processResponse($data);
  }

  /**
   * Process HTTP request and parameters.
   *
   * @return array
   *    Array of parameters needed to process a response.
   */
  protected function processRequest() {
    // Get data from ajax request.
    $field_value = \Drupal::requestStack()->getCurrentRequest()->get('in_place_field');
    $field_name = \Drupal::requestStack()->getCurrentRequest()->get('field_name');
    $entity_type = \Drupal::requestStack()->getCurrentRequest()->get('entity_type');
    $entity_id = \Drupal::requestStack()->getCurrentRequest()->get('entity_id');
    $ajax_replace = \Drupal::requestStack()->getCurrentRequest()->get('ajax_replace');
    $label_substitution = \Drupal::requestStack()->getCurrentRequest()->get('label_substitution');

    $replace_data = explode('-', $ajax_replace);
    $entity_langcode = end($replace_data);

    return [
      'field_value' => $field_value,
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'entity_id' => $entity_id,
      'ajax_replace' => $ajax_replace,
      'entity_langcode' => $entity_langcode,
      'label_substitution' => $label_substitution,
    ];
  }

  /**
   * Process a response from ajax request.
   *
   * @param $data
   *    Parameters needed to process the response
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *    Ajax response object
   */
  protected function processResponse($data) {
    $field_value = $data['field_value'];
    $field_name = $data['field_name'];
    $entity_type = $data['entity_type'];
    $entity_id = $data['entity_id'];
    $ajax_replace = $data['ajax_replace'];
    $entity_langcode = $data['entity_langcode'];
    $label_substitution = $data['label_substitution'];

    if (empty($field_name) || empty($entity_type) || empty($entity_id)) {
      return $this->getResponse(self::ERROR_INVALID_DATA, [
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
      ]);
    }

    $labels = [];
    $selected_entities = [];
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    try {
      $entity = $entity->getTranslation($entity_langcode);
    }catch(\Exception $e){}

    if (empty($entity)) {
      return $this->getResponse(self::ERROR_ENTITY_CANNOT_BE_LOADED, [
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
      ]);
    }

    try {
      $entity->{$field_name} = $field_value;
      $entity->save();

      foreach($entity->{$field_name} as $field_data) {
        $entity_field = $field_data->entity;
        try {
          $entity_field = $entity_field->getTranslation($entity_langcode);
        }catch(\Exception $e){}
        $entity_label = $entity_field->label();
        if (!empty($label_substitution) && isset($entity_field->{$label_substitution}) && !empty($entity_field->{$label_substitution}->value)) {
          $entity_label = $entity_field->{$label_substitution}->value;
        }
        $labels[] = $entity_label;
        $selected_entities[] = $entity_field;
      }
    }
    catch(EntityStorageException $e) {
      return $this->getResponse(self::ERROR_DATA_CANNOT_BE_SAVED, ['error' => $e->getMessage()]);
    }

    // Prepare response.
    $response = $this->getResponse();

    // Render entities labels.
    $labels_html = \Drupal::theme()->render('edit_in_place_reference_label', [
      'labels' => $labels,
      'entities' => $selected_entities,
      'entity_type' => $entity_type,
      'field_name' => $field_name,
      'entity_id' => $entity_id,
      'lang_code' => $entity_langcode,
    ]);

    // Labels replacement.
    $response->addCommand(new InsertCommand('.'.$ajax_replace.' .fieldset-wrapper .entity-label', $labels_html));

    // Bind JavaScript events after html replacement from ajax call.
    $response->addCommand(new RebindJSCommand('rebindJS', '.'.$ajax_replace));

    // Add a success/error message
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

}
