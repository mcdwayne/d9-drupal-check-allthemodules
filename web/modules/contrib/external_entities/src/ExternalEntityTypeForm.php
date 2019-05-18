<?php

namespace Drupal\external_entities;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Form controller for node type forms.
 */
class ExternalEntityTypeForm extends EntityForm {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The external storage client manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $storageClientManager;

  /**
   * The decoder.
   *
   * @var \Drupal\external_entities\ResponseDecoderFactoryInterface
   */
  protected $decoder;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs the NodeTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $storage_client_manager
   *   The external storage client manager.
   * @param \Drupal\external_entities\ResponseDecoderFactoryInterface $decoder
   *   The response decoder.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, PluginManagerInterface $storage_client_manager, ResponseDecoderFactoryInterface $decoder, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->storageClientManager = $storage_client_manager;
    $this->decoder = $decoder;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.external_entity_storage_client'),
      $container->get('external_entity.storage_client.response_decoder_factory'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\external_entities\ExternalEntityTypeInterface $type */
    $type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = Html::escape($this->t('Add external entity type'));
      $base_fields = $this->entityFieldManager->getBaseFieldDefinitions('external_entity');
      $fields = $this->entityFieldManager->getFieldDefinitions('external_entity', $type->id());
    }
    else {
      $form['#title'] = $this->t('Edit %label external entity type', ['%label' => $type->label()]);
      $base_fields = $this->entityFieldManager->getFieldDefinitions('external_entity', $type->id());
      $fields = $this->entityFieldManager->getFieldDefinitions('external_entity', $type->id());
    }
    unset($fields[$this->entityTypeManager->getDefinition('external_entity')->getKey('uuid')]);
    unset($fields[$this->entityTypeManager->getDefinition('external_entity')->getKey('bundle')]);

    $form['label'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => $this->t('The human-readable name of this external entity type. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['type'] = [
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$type->isNew(),
      '#machine_name' => [
        'exists' => ['\Drupal\external_entities\Entity\ExternalEntityType', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this external entity type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->getDescription(),
      '#description' => $this->t('Describe this external entity type.'),
    ];

    $form['read_only'] = [
      '#title' => $this->t('Read only'),
      '#type' => 'checkbox',
      '#default_value' => $type->isReadOnly(),
      '#description' => $this->t('Wheter or not this external entity type is read only.'),
    ];

    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
      '#attached' => [
        'library' => ['node/drupal.content_types'],
      ],
    ];

    $form['field_mappings'] = [
      '#type' => 'details',
      '#title' => $this->t('Field mappings'),
      '#group' => 'additional_settings',
      '#open' => TRUE,
    ];

    foreach ($fields as $field) {
      $form['field_mappings'][$field->getName()] = [
        '#title' => $field->getLabel(),
        '#type' => 'textfield',
        '#default_value' => $type->getFieldMapping($field->getName()),
        '#required' => isset($base_fields[$field->getName()]),
      ];
    }

    $form['storage_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Storage settings'),
      '#group' => 'additional_settings',
      '#open' => FALSE,
    ];

    $plugins = $this->storageClientManager->getDefinitions();
    $client_options = [];
    foreach ($plugins as $client) {
      $client_options[$client['id']] = $client['name'];
    }
    $form['storage_settings']['client'] = [
      '#type' => 'select',
      '#title' => $this->t('Storage client'),
      '#options' => $client_options,
      '#required' => TRUE,
      '#default_value' => $type->getClient(),
    ];

    $formats = $this->decoder->supportedFormats();
    $form['storage_settings']['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#options' => array_combine($formats, $formats),
      '#required' => TRUE,
      '#default_value' => $type->getFormat(),
    ];

    $form['storage_settings']['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#required' => TRUE,
      '#default_value' => $type->getEndpoint(),
      '#size' => 255,
      '#maxlength' => 255,
    ];

    $pager_settings = $type->getPagerSettings();

    $form['pager_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Pager settings'),
      '#group' => 'additional_settings',
      '#open' => FALSE,
    ];

    $form['pager_settings']['default_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default number of items per page'),
      '#required' => FALSE,
      '#default_value' => $pager_settings['default_limit'],
    ];

    $form['pager_settings']['page_parameter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page parameter'),
      '#required' => FALSE,
      '#default_value' => $pager_settings['page_parameter'],
    ];

    $form['pager_settings']['page_parameter_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Page parameter type'),
      '#required' => FALSE,
      '#options' => [
        'pagenum' => $this->t('Page number'),
        'startitem' => $this->t('Starting item'),
      ],
      '#description' => $this->t('Use "Page number" when the pager uses page numbers to determine the item to start at, use "Starting item" when the pager uses the item number to start at.'),
      '#default_value' => $pager_settings['page_parameter_type'],
    ];

    $form['pager_settings']['page_size_parameter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page size parameter'),
      '#required' => FALSE,
      '#default_value' => $pager_settings['page_size_parameter'],
    ];

    $form['pager_settings']['page_size_parameter_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Page size parameter type'),
      '#required' => FALSE,
      '#options' => [
        'pagesize' => $this->t('Number of items per page'),
        'enditem' => $this->t('Ending item'),
      ],
      '#description' => $this->t('Use "Number of items per pager" when the pager uses this parameter to determine the amount of items on each page, use "Ending item when the pager uses this parameter to determine the number of the last item on the page.'),
      '#default_value' => $pager_settings['page_size_parameter_type'],
    ];

    $api_key_settings = $type->getApiKeySettings();

    $form['api_key_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('API key settings'),
      '#group' => 'additional_settings',
      '#open' => FALSE,
    ];

    $form['api_key_settings']['header_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header name'),
      '#description' => $this->t('The HTTP header name for the API key. Leave blank if no API key is required.'),
      '#required' => FALSE,
      '#default_value' => $api_key_settings['header_name'],
    ];

    $form['api_key_settings']['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('The API key needed to communicate with the entered endpoint. Leave blank if no API key is required.'),
      '#required' => FALSE,
      '#default_value' => $api_key_settings['key'],
    ];

    $form['parameters'] = [
      '#type' => 'details',
      '#title' => $this->t('Parameters'),
      '#group' => 'additional_settings',
      '#open' => FALSE,
    ];

    $parameters = $type->getParameters();

    $list_lines = [];
    foreach ($parameters['list'] as $parameter => $value) {
      $list_lines[] = "$parameter|$value";
    }
    $form['parameters']['list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('List parameters'),
      '#description' => $this->t('Enter the parameters to add to the endpoint URL when loading the list of entities. One per line in the format "parameter_name|parameter_value"'),
      '#default_value' => implode("\n", $list_lines),
    ];

    $single_lines = [];
    foreach ($parameters['single'] as $parameter => $value) {
      $single_lines[] = "$parameter|$value";
    }
    $form['parameters']['single'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Single parameters'),
      '#description' => $this->t('Enter the parameters to add to the endpoint URL when loading a single of entities. One per line in the format "parameter_name|parameter_value"'),
      '#default_value' => implode("\n", $single_lines),
    ];

    $form['#tree'] = TRUE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save external entity type');
    $actions['delete']['#value'] = $this->t('Delete external entity type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('type'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('type', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", ['%invalid' => $id]));
    }
    $form_state->setValue('field_mappings', array_filter($form_state->getValue('field_mappings')));
    $form_state->setValue('format', $form_state->getValue(['storage_settings', 'format']));
    $form_state->setValue('client', $form_state->getValue(['storage_settings', 'client']));
    $form_state->setValue('endpoint', $form_state->getValue(['storage_settings', 'endpoint']));
    foreach (['list', 'single'] as $type) {
      $string = $form_state->getValue(['parameters', $type]);
      $parameters = [];
      $list = explode("\n", $string);
      $list = array_map('trim', $list);
      $list = array_filter($list, 'strlen');
      foreach ($list as $text) {
        // Check for an explicit key.
        $matches = [];
        if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
          // Trim key and value to avoid unwanted spaces issues.
          $key = trim($matches[1]);
          $value = trim($matches[2]);
        }
        // Otherwise see if we can use the value as the key.
        else {
          $key = $value = $text;
        }
        $parameters[$key] = $value;
      }

      $form_state->setValue(['parameters', $type], $parameters);
    }
    $form_state->unsetValue('storage_settings');
    $form_state->unsetValue('field_mappings');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\external_entities\ExternalEntityTypeInterface $type */
    $type = $this->entity;
    $type->set('type', trim($type->id()));
    $type->set('label', trim($type->label()));

    $status = $type->save();

    $t_args = ['%name' => $type->label()];

    if ($status == SAVED_UPDATED) {
      $this->messenger->addMessage($this->t('The external entity type %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      $this->messenger->addMessage($this->t('The external entity type %name has been added.', $t_args));
      $context = array_merge($t_args, ['link' => $type->toLink($this->t('View'), 'collection')->toString()]);
      $this
        ->logger('external_entities')
        ->notice('Added external entity type %name.', $context);
    }
    $this->entityFieldManager->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($type->toUrl('collection'));
  }

}
