<?php

namespace Drupal\content_packager\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidator;
use Drupal\field\Entity\FieldConfig;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Content Packager administration form.
 *
 * @package Drupal\content_packager\Form
 */
class ContentPackagerAdmin extends ConfigFormBase {

  private $fileSystem;
  private $pathValidator;

  /**
   * Constructs a ContentPackagerAdmin object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system service.
   * @param \Drupal\Core\Path\PathValidator $validator
   *   The path validator service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FileSystem $file_system, PathValidator $validator) {
    $this->fileSystem = $file_system;
    $this->pathValidator = $validator;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('file_system'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_packager_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['content_packager.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('content_packager.settings');

    $form['behavior'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Packaging behavior'),
    ];

    $form['behavior']['zip_enabled'] = [
      '#type' => 'checkbox',
      '#title' => 'Create zip file.',
      '#default_value' => $config->get('create_zip'),
      '#description' => $this->t('Some content might be so large that zip operations take too long and cause packaging operations to fail.  You may also prefer to use a scp or rsync workflow for incremental changes.'),
    ];

    $form['storage_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Package Filesystem Settings'),
      '#element_validate' => ['content_packager_check_directory_form'],
    ];

    $form['storage_settings']['package_scheme'] = [
      '#type' => 'select',
      '#title' => $this->t('File Scheme'),
      '#default_value' => $config->get('scheme'),
      '#options' => [
        'public://' => 'Public',
      ],
    ];

    $base_folder = $config->get('base_folder');
    $form['storage_settings']['base_folder'] = [
      '#type' => 'value',
      '#value' => $base_folder,
    ];

    $form['storage_settings']['package_destination'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File destination'),
      '#default_value' => $config->get('destination'),
      '#size' => 40,
      '#field_prefix' => $base_folder . DIRECTORY_SEPARATOR,
      '#description' => $this->t('This path is where content files and data export content will be saved.'),
    ];

    $form['storage_settings']['zip_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zip file name'),
      '#default_value' => $config->get('zip_name'),
      '#size' => 40,
      '#description' => $this->t('The name of the zip file, including file extension <b>(zip files must be enabled)</b>'),
      '#states' => [
        'disabled' => [
          'input#edit-zip-enabled' => ['unchecked' => TRUE],
        ],
      ],
    ];

    $form['image_styles'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Image Styles to Pack'),
    ];

    $styles = ImageStyle::loadMultiple();

    $form['image_styles']['unstyled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('@label', ['@label' => 'Original File']),
      '#default_value' => $config->get('include_orig_image'),
    ];

    /** @var \Drupal\image\Entity\ImageStyle $style */
    foreach ($styles as $style) {
      $style_id = $style->getName();

      $form['image_styles']['style_' . $style_id] = [
        '#type' => 'checkbox',
        '#title' => $this->t('@label', ['@label' => $style->label()]),
        '#default_value' => $config->get('image_styles.' . $style_id),
      ];
    }

    $form['fields'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Fields to ignore'),
    ];

    $form['fields']['field_ignore'] = [
      '#type' => 'tableselect',
      '#multiple' => TRUE,
      '#header' => [
        'field_name' => $this->t('Field Name'),
        'entity' => $this->t('Entity'),
      ],
    ];

    $field_instances = FieldConfig::loadMultiple();

    $options = [];
    $accepted_types = content_packager_accepted_field_types();
    $ignored_fields = $config->get('fields_ignored');
    $default_vals = [];
    /* @var \Drupal\field\Entity\FieldConfig $instance */
    foreach ($field_instances as $instance) {

      if (!in_array($instance->getType(), $accepted_types)) {
        continue;
      }

      $options[$instance->id()] = [
        'field_name' => $instance->getName(),
        'entity' => "{$instance->get('entity_type')} ({$instance->get('bundle')})",
      ];

      $default_vals[$instance->id()] = in_array($instance->id(), $ignored_fields) ? $instance->id() : 0;
    }

    $form['fields']['field_ignore']['#options'] = $options;
    $form['fields']['field_ignore']['#default_value'] = $default_vals;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('content_packager.settings');

    $values = $form_state->getValues();

    $should_zip = $values['zip_enabled'];

    $config->set('create_zip', $should_zip);

    // If a user has changed the directory, we want to make sure that we clean
    // up our old messes.
    $slashes = '\\/';
    $base_folder = $config->get('base_folder');

    $previous_scheme = $config->get('scheme');
    $new_scheme = $values['package_scheme'];
    $previous_dest = trim($config->get('destination'), $slashes);
    $new_dest = trim($values['package_destination'], $slashes);

    $previous_uri = $previous_scheme . $base_folder . DIRECTORY_SEPARATOR . $previous_dest;
    $new_uri = $new_scheme . $base_folder . DIRECTORY_SEPARATOR . $new_dest;

    if ($previous_uri !== $new_uri) {
      if (!file_unmanaged_delete_recursive($previous_uri)) {
        $this->logger('content_packager')->error('The previous directory %prev could not be successfully deleted.  You may have to manually remove it.',
          ['%prev' => $this->fileSystem->realpath($previous_uri)]);
      }
    }

    $config->set('destination', $new_dest)
      ->set('scheme', $new_scheme);

    $previous_name = $config->get('zip_name');
    $new_name = $values['zip_name'];

    $previous_zip_file = $previous_scheme . $base_folder . DIRECTORY_SEPARATOR . $previous_dest . DIRECTORY_SEPARATOR . $previous_name;

    if (file_exists($previous_zip_file) && (!$should_zip || $previous_name !== $new_name)) {

      if (!file_unmanaged_delete($previous_zip_file)) {
        $this->logger('content_packager')->error('The previous zip file %prev could not be successfully deleted.  You may have to manually remove it.',
          ['%prev' => $this->fileSystem->realpath($previous_zip_file)]);
      }
    }

    $config->set('zip_name', $values['zip_name']);

    $config->set('include_orig_image', $values['unstyled']);

    $config->set('image_styles', []);
    foreach ($values as $key => $value) {
      if (substr($key, 0, 6) == 'style_') {
        if ($value) {
          $config->set('image_styles.' . mb_substr($key, 6), $value);
        }

        unset($values[$key]);
        $form_state->unsetValue($key);
      }
    }

    $config->set('fields_ignored', []);
    $ignored = [];
    foreach ($values['field_ignore'] as $key => $value) {
      if ($value) {
        $ignored[] = $value;
      }
      unset($values['field_ignore'][$key]);
    }
    $form_state->unsetValue('field_ignored');

    $config->set('fields_ignored', $ignored);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
