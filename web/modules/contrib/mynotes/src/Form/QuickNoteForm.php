<?php

namespace Drupal\mynotes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Quick Note' form.
 */
class QuickNoteForm extends FormBase {

  /** Validation error messages. */
  protected $errorMessages = [];

  /**
   * The entityTypemanager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->errorMessages = [
      'title.required' => $this->t('Please enter a title.'),
      'description.required' => $this->t('Please enter a description.'),
    ];

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
    return 'mynotes_quick_note';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#suffix' => '<span id="validation-message-title" class="validation-message"></span>',
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#suffix' => '<span id="validation-message-description" class="validation-message"></span>',
    ];

    $labels = $this->getLabelOptions();

    if ($labels) {
      $form['labels'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Label'),
        '#options' => $labels,
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#ajax' => [
        'callback' => [$this, 'validateFormAjax'],
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $form['messages'] = [
      '#type' => '#markup',
      '#markup' => '<div id="success-message-saved" class="success-message"></div>',
    ];

    $form['#attached']['library'][] = 'mynotes/mynotes';

    return $form;
  }

  /**
   * Generate label options.
   */
  protected function getLabelOptions() {
    $label_terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadTree('labels');

    foreach ($label_terms as $label) {
      $labels[$label->tid] = $label->name;
    }

    return $labels;
  }

  /**
   * Validate the entire form via AJAX. If validation is passed, the submit
   * method is called.
   */
  public function validateFormAjax(array &$form, FormStateInterface $form_state) {
    $has_errors = FALSE;
    $response = new AjaxResponse();

    // Validate each of the form fields.
    $title_validation = $this->validateTitle($form, $form_state);
    $description_validation = $this->validateDescription($form, $form_state);

    // Initialize message variables.
    $title_message = '';
    $description_message = '';

    // Check the validations of each field, and retrieve the error message
    // if present.
    if (!$title_validation['is_valid']) {
      $title_message = $title_validation['message'];
      $has_errors = TRUE;
    }
    if (!$description_validation['is_valid']) {
      $description_message = $description_validation['message'];
      $has_errors = TRUE;
    }

    if ($has_errors) {
      // Display validation error messages.
      $response->addCommand(new HtmlCommand('#validation-message-title', $title_message));
      $response->addCommand(new HtmlCommand('#validation-message-description', $description_message));
      $response->addCommand(new HtmlCommand('#success-message-saved', ''));
      return $response;
    }
    else {
      return $this->submitFormAjax($form, $form_state);
    }
  }

  /**
   * Validate title.
   */
  protected function validateTitle(array &$form, FormStateInterface $form_state) {
    $validation = [
      'is_valid' => TRUE,
      'message' => '',
    ];
    $title = $form_state->getValue('title');
    if (!$title) {
      $validation['is_valid'] = FALSE;
      $validation['message'] = $this->errorMessages['title.required'];
    }
    return $validation;
  }

  /**
   * Validate description.
   */
  protected function validateDescription(array &$form, FormStateInterface $form_state) {
    $validation = [
      'is_valid' => TRUE,
      'message' => '',
    ];
    $description = $form_state->getValue('description');
    if (!$description) {
      $validation['is_valid'] = FALSE;
      $validation['message'] = $this->errorMessages['description.required'];
    }
    return $validation;
  }

  /**
   * Submit data.
   */
  public function submitFormAjax(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $title = $form_state->getValue('title');
    $description = $form_state->getValue('description');
    $labels = $form_state->getValue('labels');

    $node = Node::create([
      'type' => 'note',
      'title' => $title,
      'body' => [
        'value' => $description,
        'format' => 'basic_html',
      ],
      'field_labels' => $this->mapLabels($labels),
    ]);
    $node->save();

    $response->addCommand(new InvokeCommand('.form-text', 'val', ['']));
    $response->addCommand(new InvokeCommand('.form-textarea', 'val', ['']));
    $response->addCommand(new InvokeCommand('.form-checkbox', 'removeAttr', ['checked']));
    $response->addCommand(new HtmlCommand('#validation-message-title', ''));
    $response->addCommand(new HtmlCommand('#validation-message-description', ''));
    $response->addCommand(new HtmlCommand('#success-message-saved', $this->t('Note successfully added.')));
    return $response;
  }

  protected function mapLabels($labels) {
    $label_tids = [];
    foreach ($labels as $key => $value) {
      if ($value) {
        $label_tids[] = $value;
      }
    }
    return $label_tids;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
