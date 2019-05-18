<?php

namespace Drupal\content_packager\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rest\Plugin\views\display\RestExport;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the content package generation form.
 *
 * @package Drupal\content_packager\Form
 */
class CreatePackage extends FormBase {

  private $entityTypeManager;
  private $fileSystem;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $typeManager, FileSystem $fileSystem) {
    $this->entityTypeManager = $typeManager;
    $this->fileSystem = $fileSystem;
    $this->setConfigFactory($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_packager_create_package';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = [];
    $config = $this->config('content_packager.settings');

    $available_views = content_packager_get_rest_export_views();
    $view_options = [];

    foreach ($available_views as $display) {
      $view_options[$display[0]] = $display[0];
    }

    $selected_view = $form_state->getValue('selected_view', reset($view_options));

    $displays = [];
    if ($selected_view) {
      $view = Views::getView($selected_view);
      $displays = $view->storage->get('display');
    }

    $rest_displays = [];
    foreach ($displays as $display) {
      if ($display['display_plugin'] === 'rest_export') {
        $rest_displays[$display['id']] = $display['id'];
      }
    }

    $package_uri = content_packager_package_uri();
    $zip_name = $config->get('zip_name');
    $this->buildExistingPackageInfo($package_uri, $zip_name, $form);

    $form['make_package'] = [
      '#type' => 'details',
      '#title' => $this->t('Make a content package'),
      '#open' => TRUE,
    ];
    $form['make_package']['selected_view'] = [
      '#type' => 'select',
      '#title' => $this->t('View to export'),
      '#default_value' => '',
      '#options' => $view_options,
      '#ajax' => [
        'callback' => [$this, 'viewSelectChanged'],
        'event' => 'change',
        'wrapper' => 'display-wrapper',
      ],
    ];

    $form['make_package']['display_ajax'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => ['display-wrapper'],
      ],
    ];

    $form['make_package']['display_ajax']['selected_display'] = [
      '#type' => 'select',
      '#title' => $this->t('View display'),
      '#default_value' => '',
      '#options' => $rest_displays,
    ];

    $form['make_package']['destination'] = [
      '#markup' => "<p>Package destination: $package_uri</p>",
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Copy Files'),
      '#button_type' => 'primary',
    ];
    $form['actions']['zip'] = [
      '#type' => 'submit',
      '#value' => $this->t('Zip Package'),
      '#submit' => ['::zip'],
    ];

    return $form;
  }

  /**
   * Ajax callback.
   */
  public function viewSelectChanged(array $form, FormStateInterface $form_state) {
    return $form['make_package']['display_ajax'];
  }

  /**
   * Builds notice if a package is already present.
   */
  private function buildExistingPackageInfo($package_uri, $zip_name, &$form) {
    $full_package_uri = $package_uri . DIRECTORY_SEPARATOR . $zip_name;
    if (!file_exists($full_package_uri)) {
      return;
    }

    $url = file_create_url($full_package_uri);

    $form['existing_file'] = [
      '#type' => 'details',
      '#title' => $this->t('Current Package'),
      '#prefix' => $this->t('A package already exists and <a href=":package_link">can be downloaded</a> at any time.',
        [':package_link' => $url]),
      '#open' => FALSE,
    ];

    $form['existing_file']['file'] = [
      '#type' => 'item',
      '#title' => 'File',
      '#markup' => $this->t('<a href=":package_uri">%display_uri</a>',
        [
          ':package_uri' => $url,
          '%display_uri' => $full_package_uri,
        ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Make sure our directory is actually there!
    $package_uri = content_packager_package_uri();

    if (!file_exists($package_uri)) {
      $errors = content_packager_prepare_directory($package_uri);

      if (!file_exists($package_uri) || $errors) {
        $msg = $this->t('Directory for packaging is incorrectly configured or cannot be found.');
        $this->messenger->addError($msg);
        return;
      }
    }

    content_packager_clear_processed();

    $image_styles = array_keys($this->config('content_packager.settings')->get('image_styles'));
    $field_blacklist = $this->config('content_packager.settings')->get('fields_ignored');

    $view_id = $form_state->getValue('selected_view');
    $display_id = $form_state->getValue('selected_display');
    $view = Views::getView($view_id);
    $view->setDisplay($display_id);

    $has_access = $view->access($display_id);
    $display = $view->getDisplay();

    $content_filename = $display instanceof RestExport ?
      "{$display_id}.{$display->getContentType()}" : "{$display_id}.data";

    $data_uri = $package_uri . DIRECTORY_SEPARATOR . $content_filename;

    $operations[] = [
      'Drupal\content_packager\BatchOperations::prepareDestination',
      [$package_uri],
    ];

    $options = [
      'image_styles' => $image_styles,
      'field_blacklist' => $field_blacklist,
      'data_path' => $data_uri,
    ];

    if (!$display_id) {
      $msg = $this->t('Could not find an appropriate REST Export display for the selected view to render!');
      $this->messenger->addError($msg);
      return;
    }

    if ($has_access) {
      $operations[] = [
        'Drupal\content_packager\BatchOperations::renderAndSaveViewOutput',
        [$view_id, $display_id, $data_uri],
      ];
    }

    // This function does a lot, so we aren't using the existing $view.
    $view_rows = $has_access ? views_get_view_result($view_id, $display_id) : [];

    foreach ($view_rows as $row) {
      $infos = [];

      /* @var \Drupal\Core\Entity\Entity $entity */
      $entity = $row->_entity;

      $access = $entity->access('view');
      if (!$access) {
        continue;
      }
      $infos[] = ['id' => $entity->id(), 'type' => $entity->getEntityTypeId()];

      $operations[] = [
        'Drupal\content_packager\BatchOperations::copyEntityFiles',
        [
          $infos,
          $package_uri,
          $options,
        ],
      ];
    }

    $batch = [
      'operations' => $operations,
      'finished' => 'Drupal\content_packager\BatchOperations::packingFinished',
    ];

    batch_set($batch);
  }

  /**
   * Form submission handler for the 'zip package' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function zip(array &$form, FormStateInterface $form_state) {

    // Make sure our directory is actually there!
    $package_dir_uri = content_packager_package_uri();

    $zip_name = $this->config('content_packager.settings')->get('zip_name');
    $zip_path = $this->fileSystem->realpath($package_dir_uri . DIRECTORY_SEPARATOR . $zip_name);

    file_unmanaged_delete($zip_path);

    $operations = [];

    $add_to_batch = function ($uri) use ($zip_path, &$operations) {
      $zip_pathinfo = pathinfo($zip_path);
      $file_path = $this->fileSystem->realpath($uri);
      $file_pathinfo = pathinfo($file_path);

      $operations[] = [
        'Drupal\content_packager\BatchOperations::zipFile',
        [
          $zip_pathinfo['basename'],
          $zip_pathinfo['dirname'],
          $file_pathinfo['basename'],
          $file_pathinfo['dirname'],
        ],
      ];
    };

    file_scan_directory($package_dir_uri, '/.*/', [
      'callback' => $add_to_batch,
    ]);

    $batch = [
      'operations' => $operations,
      'finished' => 'Drupal\content_packager\BatchOperations::packingFinished',
    ];

    batch_set($batch);
  }

}
