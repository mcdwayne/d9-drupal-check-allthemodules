<?php

namespace Drupal\message_thread\Form;

use Drupal\views\Views;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\message\MessagePurgePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for node type forms.
 */
class MessageThreadTemplateForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\message\Entity\MessageTemplate
   */
  protected $entity;

  /**
   * The purge plugin manager.
   *
   * @var \Drupal\message\MessagePurgePluginManager
   */
  protected $purgeManager;

  /**
   * The template storage manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $templateStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs the message thread template form.
   *
   * @param \Drupal\message\MessagePurgePluginManager $purge_manager
   *   The message purge plugin manager service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $template_storage
   *   The message purge plugin manager service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The message purge plugin manager service.
   */
  public function __construct(MessagePurgePluginManager $purge_manager, EntityStorageInterface $template_storage, EntityTypeManager $entity_type_manager) {
    $this->purgeManager = $purge_manager;
    $this->templateStorage = $template_storage;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.message.purge'),
      $container->get('entity_type.manager')->getStorage('message_template'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\message\Entity\MessageTemplate $template */
    $template = $this->entity;

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $template->label(),
      '#description' => $this->t('The human-readable name of this message thread template . This text will be displayed as part of the list on the <em>Add message</em> page. It is recommended that this name begin with a capital letter and contain only letters, numbers, and spaces. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['template'] = [
      '#type' => 'machine_name',
      '#default_value' => $template->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => '\Drupal\message_thread\Entity\MessageThreadTemplate::load',
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this message thread template . It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %message-add page, in which underscores will be converted into hyphens.', [
        '%message-add' => $this->t('Add message'),
      ]),
    ];

    $settings = $this->entity->getSettings();

    $form['settings'] = [
      // Placeholder for other module to add their settings, that should be
      // added to the settings column.
      '#tree' => TRUE,
    ];

    $message_templates = $this->entityTypeManager->getListBuilder('message_template')->load();
    $templates = [];
    foreach ($message_templates as $name => $template) {
      $templates[$name] = $template->label();
    }

    $form['settings']['message_template'] = [
      '#type' => 'select',
      '#options' => $templates,
      '#default_value' => isset($settings['message_template']) ? $settings['message_template'] : '',
      '#required' => TRUE,
      '#description' => $this->t('Select the message template  to which this message thread template applies.', [
        '%message-add' => $this->t('Add message'),
      ]),
    ];

    // Message thread views.
    $options = ['_none' => 'None'];
    $options += $this->getMessageViews('message_thread_field_data');
    $form['settings']['thread_view_id'] = [
      '#title' => $this->t('Message Thread View'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => isset($settings['thread_view_id']) ? $settings['thread_view_id'] : '',
      '#description' => $this->t('Select the View you wish to use to display threads messages of this type in the tab on the User page.'),
      '#target_type' => 'view',
      '#ajax' => [
        'callback' => [$this, 'getThreadDisplayIds'],
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Getting display Ids...'),
        ],
      ],
    ];

    $default_value = isset($settings['thread_view_id']) ? $settings['thread_view_id'] : '';
    if ($default_value == '') {
      $options = $this->getAllViewsDisplayIds();
    }
    else {
      $options = $this->getViewDisplayIds($settings['thread_view_id']);
    }

    $form['settings']['thread_view_display_id'] = [
      '#title' => $this->t('Message Thread View Display'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => isset($settings['thread_view_display_id']) ? $settings['thread_view_display_id'] : '',
      '#description' => $this->t('Select the Display from the View you selected above.'),
      '#attributes' => [
        'class' => [
          'message-thread-view-display-id',
        ],
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[thread_view_id]"]' => ['!value' => '_none'],
        ],
      ],

    ];

    // Message views.
    $form['settings']['view_id'] = [
      '#title' => $this->t('Message View'),
      '#type' => 'select',
      '#options' => $this->getMessageViews(),
      '#default_value' => isset($settings['view_id']) ? $settings['view_id'] : '',
      '#description' => $this->t('Select the View you wish to use to display messages of this type when viewing a thread.'),
      '#target_type' => 'view',
      '#ajax' => [
        'callback' => [$this, 'getDisplayIds'],
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Getting display Ids...'),
        ],
      ],
    ];

    $default_value = isset($settings['view_id']) ? $settings['view_id'] : '';
    if ($default_value == '') {
      $options = $this->getAllViewsDisplayIds();
    }
    else {
      $options = $this->getViewDisplayIds($settings['view_id']);
    }

    $form['settings']['view_display_id'] = [
      '#title' => $this->t('Message View Display'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => isset($settings['view_display_id']) ? $settings['view_display_id'] : '',
      '#description' => $this->t('Select the Display from the View you selected above.'),
      '#attributes' => [
        'class' => [
          'message-view-display-id',
        ],
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[view_id]"]' => ['!value' => '_none'],
        ],
      ],

    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textfield',
      '#default_value' => $this->entity->getDescription(),
      '#description' => $this->t('The human-readable description of this message thread template .'),
    ];

    $form['settings']['token options']['clear'] = [
      '#title' => $this->t('Clear empty tokens'),
      '#type' => 'checkbox',
      '#description' => $this->t('When this option is selected, empty tokens will be removed from display.'),
      '#default_value' => isset($settings['token options']['clear']) ? $settings['token options']['clear'] : FALSE,
    ];

    $form['settings']['token options']['token replace'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Token replace'),
      '#description' => $this->t('When this option is selected, token processing will happen.'),
      '#default_value' => !isset($settings['token options']['token replace']) || !empty($settings['token options']['token replace']),
    ];

    return $form;
  }

  /**
   * Helper function to get select array of all views of entity type message.
   */
  protected function getMessageViews($entity_data_field = 'message_field_data') {
    $views = Views::getAllViews();
    $options = [];
    foreach ($views as $view) {
      if ($view->get('base_table') == $entity_data_field) {
        foreach ($view->get('display') as $display) {
          $options[$view->id()] = $view->label();
        }
      }
    }
    return $options;
  }

  /**
   * Helper function to get all display ids.
   */
  protected function getAllViewsDisplayIds() {
    $views = Views::getAllViews();
    $options = [];
    foreach ($views as $view) {
      foreach ($view->get('display') as $display) {
        $options[$display['id']] = $display['display_title'];
      }
    }
    return $options;
  }

  /**
   * Helper to get display ids for a particular View.
   */
  protected function getViewDisplayIds($entity_id) {
    $views = Views::getAllViews();
    $options = [];

    foreach ($views as $view) {
      if ($view->get('id') == $entity_id) {
        foreach ($view->get('display') as $display) {
          $options[$display['id']] = $display['display_title'];
        }
      }
    }
    return $options;
  }

  /**
   * Get display ids of View.
   */
  public function getThreadDisplayIds(array &$form, FormStateInterface $form_state) {
    return $this->getDisplayIds($form, $form_state, '.message-thread-view-display-id', 'thread_view_id');
  }

  /**
   * AJAX function to get display IDs for a particular View.
   */
  public function getDisplayIds(array &$form, FormStateInterface $form_state, $element_class = '.message-view-display-id', $view = 'view_id') {
    $values = $form_state->getValues();
    // Obtain the display ids for the given View.
    $options = $this->getViewDisplayIds($values['settings'][$view]);
    $element_class = $element_class;

    // Construct the html.
    $html = '<optgroup>';
    foreach ($options as $key => $option) {
      $html .= '<option value="' . $key . '">' . $option . '</option>';
    }
    $html .= '</optgroup>';
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand($element_class, render($html)));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save message thread template');
    $actions['delete']['#value'] = t('Delete message thread template');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $settings = $this->entity->getSettings();
    $this->entity->setSettings($settings);
  }

  /**
   * Ajax callback for the "Add another item" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    return $form['text'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    parent::save($form, $form_state);

    $params = [
      '@template' => $form_state->getValue('label'),
    ];

    drupal_set_message(t('The message thread template @template created successfully.', $params));
    $form_state->setRedirect('message_thread.overview_templates');
    return $this->entity;
  }

}
