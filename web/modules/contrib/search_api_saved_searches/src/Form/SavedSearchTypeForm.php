<?php

namespace Drupal\search_api_saved_searches\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Display\DisplayPluginManager;
use Drupal\search_api\Utility\DataTypeHelperInterface;
use Drupal\search_api_saved_searches\Notification\NotificationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for adding and editing saved search types.
 */
class SavedSearchTypeForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\search_api_saved_searches\SavedSearchTypeInterface
   */
  protected $entity;

  /**
   * The notification plugin manager.
   *
   * @var \Drupal\search_api_saved_searches\Notification\NotificationPluginManagerInterface|null
   */
  protected $notificationPluginManager;

  /**
   * The display plugin manager.
   *
   * @var \Drupal\search_api\Display\DisplayPluginManager|null
   */
  protected $displayPluginManager;

  /**
   * The data type helper.
   *
   * @var \Drupal\search_api\Utility\DataTypeHelperInterface|null
   */
  protected $dataTypeHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $form */
    $form = parent::create($container);

    $form->setStringTranslation($container->get('string_translation'));
    $form->setEntityTypeManager($container->get('entity_type.manager'));
    $form->setNotificationPluginManager($container->get('plugin.manager.search_api_saved_searches.notification'));
    $form->setDisplayPluginManager($container->get('plugin.manager.search_api.display'));
    $form->setDataTypeHelper($container->get('search_api.data_type_helper'));

    return $form;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * Retrieves the notification plugin manager.
   *
   * @return \Drupal\search_api_saved_searches\Notification\NotificationPluginManagerInterface
   *   The notification plugin manager.
   */
  public function getNotificationPluginManager() {
    return $this->notificationPluginManager ?: \Drupal::service('plugin.manager.search_api_saved_searches.notification');
  }

  /**
   * Sets the notification plugin manager.
   *
   * @param \Drupal\search_api_saved_searches\Notification\NotificationPluginManagerInterface $notification_plugin_manager
   *   The new notification plugin manager.
   *
   * @return $this
   */
  public function setNotificationPluginManager(NotificationPluginManagerInterface $notification_plugin_manager) {
    $this->notificationPluginManager = $notification_plugin_manager;
    return $this;
  }

  /**
   * Retrieves the display plugin manager.
   *
   * @return \Drupal\search_api\Display\DisplayPluginManager
   *   The display plugin manager.
   */
  public function getDisplayPluginManager() {
    return $this->displayPluginManager ?: \Drupal::service('plugin.manager.search_api.display');
  }

  /**
   * Sets the display plugin manager.
   *
   * @param \Drupal\search_api\Display\DisplayPluginManager $display_plugin_manager
   *   The new display plugin manager.
   *
   * @return $this
   */
  public function setDisplayPluginManager(DisplayPluginManager $display_plugin_manager) {
    $this->displayPluginManager = $display_plugin_manager;
    return $this;
  }

  /**
   * Retrieves the data type helper.
   *
   * @return \Drupal\search_api\Utility\DataTypeHelperInterface
   *   The data type helper.
   */
  public function getDataTypeHelper() {
    return $this->dataTypeHelper ?: \Drupal::service('search_api.data_type_helper');
  }

  /**
   * Sets the data type helper.
   *
   * @param \Drupal\search_api\Utility\DataTypeHelperInterface $data_type_helper
   *   The new data type helper.
   *
   * @return $this
   */
  public function setDataTypeHelper(DataTypeHelperInterface $data_type_helper) {
    $this->dataTypeHelper = $data_type_helper;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $type = $this->entity;
    $form['#tree'] = TRUE;
    if ($type->isNew()) {
      $form['#title'] = $this->t('Create saved search type');
    }
    else {
      $args = ['%type' => $type->label()];
      $form['#title'] = $this->t('Edit saved search type %type', $args);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Type name'),
      '#description' => $this->t('Enter the displayed name for the saved search type.'),
      '#default_value' => $type->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => 50,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => '\Drupal\search_api_saved_searches\Entity\SavedSearchType::load',
        'source' => ['label'],
      ],
      '#disabled' => !$type->isNew(),
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('An optional description for this type. This will only be shown to administrators.'),
      '#default_value' => $type->getDescription(),
    ];
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Disabling a saved search type will prevent the creation of new saved searches of that type and stop notifications for existing searches of that type.'),
      '#default_value' => $type->status(),
    ];

    $display_options = [];
    $displays = $this->getDisplayPluginManager()->getInstances();
    foreach ($displays as $display_id => $display) {
      $display_options[$display_id] = $display->label();
    }
    $form['options']['displays'] = [
      '#type' => 'details',
      '#title' => $this->t('Search displays'),
      '#description' => $this->t('Select for which search displays saved searches of this type can be created.'),
      '#open' => $type->isNew(),
    ];
    if (count($display_options) > 0) {
      $form['options']['displays']['default'] = [
        '#type' => 'radios',
        '#options' => [
          1 => $this->t('For all displays except the selected'),
          0 => $this->t('Only for the selected displays'),
        ],
        '#default_value' => (int) $type->getOption('displays.default', TRUE),
      ];
      $form['options']['displays']['selected'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Search displays'),
        '#options' => $display_options,
        '#default_value' => $type->getOption('displays.selected', []),
      ];
    }
    else {
      $form['options']['displays']['default'] = [
        '#type' => 'radios',
        '#options' => [
          1 => $this->t('Applies to all displays by default'),
          0 => $this->t('Applies to no displays by default'),
        ],
        '#default_value' => (int) $type->getOption('displays.default', TRUE),
      ];
      $form['options']['displays']['selected'] = [
        '#type' => 'value',
        '#value' => [],
      ];
    }

    $form['notification_plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Notification method'),
      '#description' => $this->t('This determines how users will be notified of new results for their saved searches.'),
      '#default_value' => $type->getNotificationPluginIds(),
      '#ajax' => [
        'trigger_as' => ['name' => 'notification_plugins_configure'],
        'callback' => '::buildAjaxNotificationPluginConfigForm',
        'wrapper' => 'search-api-notification-plugins-config-form',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ];
    $notification_plugin_options = [];
    foreach ($this->getNotificationPluginManager()->createPlugins($type) as $plugin_id => $notification_plugin) {
      $notification_plugin_options[$plugin_id] = $notification_plugin->label();
      $form['notification_plugins'][$plugin_id]['#description'] = $notification_plugin->getDescription();
    }
    asort($notification_plugin_options, SORT_NATURAL | SORT_FLAG_CASE);
    $form['notification_plugins']['#options'] = $notification_plugin_options;

    $form['notification_configs'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'search-api-notification-plugins-config-form',
      ],
      '#tree' => TRUE,
    ];

    $form['notification_plugin_configure_button'] = [
      '#type' => 'submit',
      '#name' => 'notification_plugins_configure',
      '#value' => $this->t('Configure'),
      '#limit_validation_errors' => [['notification_plugins']],
      '#submit' => ['::submitAjaxNotificationPluginConfigForm'],
      '#ajax' => [
        'callback' => '::buildAjaxNotificationPluginConfigForm',
        'wrapper' => 'search-api-notification-plugins-config-form',
      ],
      '#attributes' => ['class' => ['js-hide']],
    ];

    $this->buildNotificationPluginConfigForm($form, $form_state);

    $form['misc'] = [
      '#type' => 'details',
      '#title' => $this->t('Miscellaneous'),
      '#open' => $type->isNew(),
    ];
    $form['misc']['allow_keys_change'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow changing of keywords'),
      '#description' => $this->t('Enable to allow users to change the search keywords for existing saved searches.'),
      '#default_value' => $type->getOption('allow_keys_change', FALSE),
      '#parents' => ['options', 'allow_keys_change'],
    ];
    $form['misc']['date_field'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Method for determining new results'),
      '#description' => $this->t('The method by which to decide which results are new. "Determine by result ID" will internally save the IDs of all results that were previously found by the user and only report results not already reported. (This might use a lot of memory for large result sets.) The other options check whether the date in the selected field is later than the date of last notification.'),
    ];
    /** @var \Drupal\search_api\IndexInterface[] $indexes */
    $indexes = $this->getEntityTypeManager()
      ->getStorage('search_api_index')
      ->loadMultiple();
    $data_type_helper = $this->getDataTypeHelper();
    foreach ($indexes as $index_id => $index) {
      $fields = [];
      foreach ($index->getFields() as $key => $field) {
        // We misuse isTextType() here to check for the "Date" type instead.
        if ($data_type_helper->isTextType($field->getType(), ['date'])) {
          $fields[$key] = $this->t('Determine by @name', ['@name' => $field->getLabel()]);
        }
      }
      if ($fields) {
        $fields = [NULL => $this->t('Determine by result ID')] + $fields;
        $form['misc']['date_field'][$index_id] = [
          '#type' => 'select',
          '#title' => count($indexes) === 1 ? NULL : $this->t('Searches on index %index', ['%index' => $index->label()]),
          '#options' => $fields,
          '#default_value' => $type->getOption("date_field.$index_id"),
          '#parents' => ['options', 'date_field', $index_id],
        ];
      }
      else {
        $form['misc']['date_field'][$index_id] = [
          '#type' => 'value',
          '#value' => NULL,
          '#parents' => ['options', 'date_field', $index_id],
        ];
      }
    }
    $form['misc']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('User interface description'),
      '#description' => $this->t('Enter a text that will be displayed to users when creating a saved search. You can use HTML in this field.'),
      '#default_value' => $type->getOption('description', ''),
      '#parents' => ['options', 'description'],
    ];

    return $form;
  }

  /**
   * Builds the configuration forms for all selected notification plugins.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  protected function buildNotificationPluginConfigForm(array &$form, FormStateInterface $form_state) {
    $type = $this->entity;

    $selected_plugins = $form_state->getValue('notification_plugins');
    if ($selected_plugins === NULL) {
      // Initial form build, use the saved notification plugins (or none for new
      // indexes).
      $plugins = $type->getNotificationPlugins();
    }
    else {
      // The form is being rebuilt – use the notification plugins selected by
      // the user instead of the ones saved in the config.
      $plugins = $this->getNotificationPluginManager()
        ->createPlugins($type, $selected_plugins);
    }
    $form_state->set('notification_plugins', array_keys($plugins));

    $show_message = FALSE;
    foreach ($plugins as $plugin_id => $plugin) {
      if ($plugin instanceof PluginFormInterface) {
        // Get the "sub-form state" and appropriate form part to send to
        // buildConfigurationForm().
        $plugin_form = [];
        if (!empty($form['notification_configs'][$plugin_id])) {
          $plugin_form = $form['notification_configs'][$plugin_id];
        }
        $plugin_form_state = SubformState::createForSubform($plugin_form, $form, $form_state);
        $form['notification_configs'][$plugin_id] = $plugin->buildConfigurationForm($plugin_form, $plugin_form_state);

        $show_message = TRUE;
        $form['notification_configs'][$plugin_id]['#type'] = 'details';
        $form['notification_configs'][$plugin_id]['#title'] = $this->t('Configure the %notification notification method', ['%notification' => $plugin->label()]);
        $form['notification_configs'][$plugin_id]['#open'] = $type->isNew();
      }
    }

    // If the user changed the notification plugins and there is at least one
    // plugin config form, show a message telling the user to configure it.
    if ($selected_plugins && $show_message) {
      drupal_set_message($this->t('Please configure the used notification methods.'), 'warning');
    }
  }

  /**
   * Handles changes to the selected notification plugins.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The part of the form to return as AJAX.
   */
  public function buildAjaxNotificationPluginConfigForm(array $form, FormStateInterface $form_state) {
    return $form['notification_configs'];
  }

  /**
   * Form submission handler for buildEntityForm().
   *
   * Takes care of changes in the selected notification plugins.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function submitAjaxNotificationPluginConfigForm(array $form, FormStateInterface $form_state) {
    $form_state->setValue('id', NULL);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\search_api_saved_searches\SavedSearchTypeInterface $type */
    $type = $this->getEntity();

    // Store the selected displays as a numerically indexed array.
    $key = ['options', 'displays', 'selected'];
    $selected = $form_state->getValue($key, []);
    $selected = array_keys(array_filter($selected));
    $form_state->setValue($key, $selected);

    // Store the array of notification plugin IDs with integer keys.
    $plugin_ids = array_values(array_filter($form_state->getValue('notification_plugins', [])));
    $form_state->setValue('notification_plugins', $plugin_ids);

    // Call validateConfigurationForm() for each enabled notification plugin
    // with a form.
    $plugins = $this->getNotificationPluginManager()
      ->createPlugins($type, $plugin_ids);
    $previous_plugins = $form_state->get('notification_plugins');
    foreach ($plugins as $plugin_id => $plugin) {
      if ($plugin instanceof PluginFormInterface) {
        if (!in_array($plugin_id, $previous_plugins)) {
          $form_state->setRebuild();
          continue;
        }
        $plugin_form = &$form['notification_configs'][$plugin_id];
        $plugin_form_state = SubformState::createForSubform($plugin_form, $form, $form_state);
        $plugin->validateConfigurationForm($plugin_form, $plugin_form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\search_api_saved_searches\SavedSearchTypeInterface $type */
    $type = $this->getEntity();

    $plugin_ids = $form_state->getValue('notification_plugins', []);
    $plugins = $this->getNotificationPluginManager()
      ->createPlugins($type, $plugin_ids);
    foreach ($plugins as $plugin_id => $plugin) {
      if ($plugin instanceof PluginFormInterface) {
        $plugin_form_state = SubformState::createForSubform($form['notification_configs'][$plugin_id], $form, $form_state);
        $plugin->submitConfigurationForm($form['notification_configs'][$plugin_id], $plugin_form_state);
      }
    }
    $type->setNotificationPlugins($plugins);

    if ($this->entity->isNew()) {
      $form_state->setRedirect('entity.search_api_saved_search_type.edit_form', [
        'search_api_saved_search_type' => $type->id(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = parent::save($form, $form_state);

    $this->messenger()->addStatus($this->t('Your settings have been saved.'));

    return $return;
  }

}
