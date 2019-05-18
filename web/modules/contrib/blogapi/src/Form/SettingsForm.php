<?php

namespace Drupal\blogapi\Form;

use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\blogapi\BlogapiCommunicator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\blogapi\Form
 */
class SettingsForm extends ConfigFormBase {

  private $entityTypeManager;
  private $entityFieldManager;
  private $blogapiCommunicator;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $etManager, EntityFieldManager $efManager, BlogapiCommunicator $communicator) {
    $this->entityTypeManager = $etManager;
    $this->entityFieldManager = $efManager;
    $this->blogapiCommunicator = $communicator;
  }

  /**
   * Create a new instance ff the form object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('service.communicator.blogapi')
    );
  }

  /**
   * @return string
   *   Return the form ID.
   */
  public function getFormID() {
    return 'blogapi_settings_form';
  }

  /**
   * @return array
   *   Return the config file that will be edited.
   */
  public function getEditableConfigNames() {
    return [
      'blogapi.settings',
    ];
  }

  /**
   * Build the form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('blogapi.settings');
    $contentTypes = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $ct_keys = [];

    // Get all available text formats.
    $plugins = filter_formats();
    $filters = [];
    foreach ($plugins as $format) {
      $filters[$format->id()] = $format->get('name');
    }

    // Get all the available content types.
    foreach ($contentTypes as $id => $type) {
      $ct_keys[$id] = $type->label();
    }

    // Get the default text filter from config.
    $default_text_format = $config->get('text_format');

    // Get previously enabled content types.
    $enabled_content_types = $config->get('content_types');

    // Get possible body and taxonomy fields for every enabled content type.
    $field_storage = [];
    foreach ($enabled_content_types as $ct) {
      if (!empty($ct)) {
        // Get all field definitions of a content type.
        $definitions = $this->entityFieldManager->getFieldDefinitions('node', $ct);
        foreach ($definitions as $field) {
          $id = $field->getName();
          $label = $field->getLabel();

          // If the field is a taxonomy field.
          if ($this->blogapiCommunicator->fieldIsTaxonomy($field)) {
            $field_storage[$ct]['taxonomy'][$id] = $label . ' (' . $id . ')';
          }
          elseif ($field->getType() == 'comment') {
            $field_storage[$ct]['comment'][$id] = $label . ' (' . $id . ')';
          }
          // Any other field is a possible body field.
          elseif ($field instanceof FieldConfig) {
            $field_storage[$ct]['body'][$id] = $label . ' (' . $id . ')';
          }
        }
      }
    }

    // Markup element with info.
    $form['blogapi_info_wrapper'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('BlogAPI endpoint'),
      'blogapi_info' => [
        '#type' => 'markup',
        '#markup' => Url::fromRoute('xmlrpc')->setAbsolute()->toString(),
      ],
    );

    // Element to select enabled content types.
    $form['content_types'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Select content types to manage with BlogAPI'),
      '#options' => $ct_keys,
      '#default_value' => $enabled_content_types,
      '#description' => $this->t('Select the content types available to external blogging clients via Blog API. If supported, each enabled content type will be displayed as a separate "blog" by the external client..'),
    );

    // Element to select default text format.
    $form['text_format'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Select default text format'),
      '#options' => $filters,
      '#default_value' => $default_text_format,
      '#description' => $this->t('The selected text format will be applied to the body field if the client doesn\'t specify which text format to use.'),
    );

    // Create fieldsets for each content type, with radio buttons
    // for selecting the body and taxonomy fields.
    foreach ($field_storage as $enabled_ct => $fields) {
      // Fieldset for a content type.
      $form['fields_config']['fields_' . $enabled_ct] = [
        '#type' => 'fieldset',
        '#title' => $ct_keys[$enabled_ct],
      ];

      // Body field select.
      $form['fields_config']['fields_' . $enabled_ct]['body_' . $enabled_ct] = [
        '#type' => 'radios',
        '#title' => $this->t('Body field'),
        '#options' => $fields['body'],
        '#default_value' => $config->get('body_' . $enabled_ct),
        '#description' => $this->t('This field will be used to store the body text.'),
      ];

      // Taxonomy field select.
      if (isset($fields['taxonomy'])) {
        $form['fields_config']['fields_' . $enabled_ct]['taxonomy_' . $enabled_ct] = [
          '#type' => 'radios',
          '#title' => $this->t('Vocabulary'),
          '#options' => $fields['taxonomy'],
          '#default_value' => $config->get('taxonomy_' . $enabled_ct),
          '#description' => $this->t('If possible this field will be used to store selected tags.'),
        ];
      }
      else {
        $form['fields_config']['fields_' . $enabled_ct]['taxonomy_' . $enabled_ct] = [
          '#markup' => $this->t('There are not taxonomy fields available for this content type.'),
        ];
      }

      // Comment field select.
      if (isset($fields['comment'])) {
        $form['fields_config']['fields_' . $enabled_ct]['comment_' . $enabled_ct] = [
          '#type' => 'radios',
          '#title' => $this->t('Comment'),
          '#options' => $fields['comment'],
          '#default_value' => $config->get('comment_' . $enabled_ct),
          '#description' => $this->t('This field will be used as the default comment field.'),
        ];
      }
      else {
        $form['fields_config']['fields_' . $enabled_ct]['taxonomy_' . $enabled_ct] = [
          '#markup' => $this->t('There are not comment fields available for this content type.'),
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validate the form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Currently no validation needed.
    parent::validateForm($form, $form_state);
  }

  /**
   * Form submit.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Save the enabled content types and default text format.
    $config = $this->config('blogapi.settings');
    $config->set('content_types', $form_state->getValue('content_types'))
      ->save();
    $config->set('text_format', $form_state->getValue('text_format'))
      ->save();

    // Save the body and taxonomy fields for every selected content type.
    $enabled_cts = $config->get('content_types');
    foreach ($enabled_cts as $ct) {
      if (!empty($ct)) {

        // Save the body field.
        $field_body = 'body_' . $ct;
        $body = $form_state->getValue($field_body);
        if (!is_null($body)) {
          $config->set('body_' . $ct, $body)->save();
        }

        // Save the taxonomy field.
        $field_vocab = 'taxonomy_' . $ct;
        $vocab = $form_state->getValue($field_vocab);
        if (!is_null($vocab)) {
          $config->set('taxonomy_' . $ct, $vocab)->save();
        }

        // Save the comment field.
        $field_comment = 'comment_' . $ct;
        $comment = $form_state->getValue($field_comment);
        if (!is_null($comment)) {
          $config->set('comment_' . $ct, $comment)->save();
        }
      }
    }

    drupal_set_message($this->t('BlogAPI settings have been saved.'));
  }

}
