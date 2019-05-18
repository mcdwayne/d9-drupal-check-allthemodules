<?php

namespace Drupal\filebrowser;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility;
use Drupal\filebrowser\Events\MetadataInfo;
use Drupal\filebrowser\Services\FilebrowserValidator;
use Drupal\filebrowser\Services\Common;
use Drupal\filebrowser\Services\FilebrowserStorage;

class FilebrowserManager extends ControllerBase {

  /**
   * @var \Drupal\filebrowser\Services\FilebrowserStorage
   */
  public $storage;

  /**
   * @var \Drupal\filebrowser\Services\FilebrowserValidator
   */
  public $validator;

  /**
   * @var \Drupal\filebrowser\Services\Common
   */
  public $common;


  public $user;

  /**
   * @var \Drupal\filebrowser\Filebrowser
   */
  protected $filebrowser;


  /**
   * FilebrowserManager constructor.
   * @param \Drupal\filebrowser\Services\FilebrowserStorage $storage
   * @param \Drupal\filebrowser\Services\FilebrowserValidator $validator
   * @param \Drupal\Core\Session\AccountInterface
   * @param \Drupal\filebrowser\Services\Common $common
   */
  public function __construct(FilebrowserStorage $storage, FilebrowserValidator $validator, AccountInterface $user, Common
  $common) {
    $this->storage = $storage;
    $this->validator = $validator;
    $this->user = $user;
    $this->common = $common;
  }

  /**
   * Adds form element to node type dir_listing or to filebrowser config settings form
   *
   * @param array $form Form to be altered or config settings form
   * @param FormStateInterface $form_state
   * @param null $node In case of form-alter a node can be passed for existing nodes
   * @param bool|false $isConfigForm If these fields are added to the config settings form this
   * will de set to true.
   * @return array
   */
  public function addFormExtraFields(&$form, $form_state, $node = null, $isConfigForm = false) {
    /** @var \Drupal\filebrowser\Filebrowser $nodeValues */

    $config = \Drupal::config('filebrowser.settings');
    $config = $config->get('filebrowser');
    $dispatcher = \Drupal::service('event_dispatcher');
    $nodeValues = isset($node->filebrowser) ? $node->filebrowser : null;

    $form['filebrowser'] = [
      '#tree' => true,
      '#type' => 'fieldset',
      '#title' => ($isConfigForm) ? $this->t('Filebrowser default settings') : 'Filebrowser',
      '#weight' => 10,
      '#collapsed' => ($isConfigForm ) ? true : false,
    ];

    // Don't set the folder path and filesystem for a config form
    if (!$isConfigForm) {
      $form['filebrowser']['folder_path'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Directory uri'),
        '#required' => true,
        '#description' => $this->folderPathDescription(),
        '#default_value' => isset($nodeValues->folderPath) ? $nodeValues->folderPath : '',
        '#attributes' => [
          'placeholder' => 'public://your_folder_here'
        ],
        '#element_validate' => [[__CLASS__, 'validateFolderPath'],],
      ];
    }

    if (!$isConfigForm) {
      if (\Drupal::moduleHandler()->moduleExists('token')) {
        $form['filebrowser']['token']['token_browser'] = [
          '#theme' => 'token_tree_link',
          '#token_types' => ['node'],
        ];
      }
      else {
        $form['filebrowser']['token']['token_browser'] = [
          '#markup' => $this->tokenMessage(),
        ];
      }
    }

    $form['filebrowser']['folder_path_encoded'] = [
      '#type' => 'value',
    ];

    // Folder rights
    $form['filebrowser']['rights'] = [
      '#type' => 'details',
      '#title' => $this->t('Folder rights'),
      '#open' => ($isConfigForm) ? false : true,
    ];

    $form['filebrowser']['rights']['explore_subdirs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show subdirectories if present.'),
      '#options' => [0, 1],
      '#default_value' => isset($nodeValues->exploreSubdirs) ? $nodeValues->exploreSubdirs : $config['rights']['explore_subdirs'],
    ];

    $form['filebrowser']['rights']['download_archive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Allow folder's files to be downloaded as an archive"),
      '#description' => $this->t("Users with proper permission may download files as an archive"),
      '#options' => [0, 1],
      '#default_value' => isset($nodeValues->downloadArchive) ? $nodeValues->downloadArchive : $config['rights']['download_archive'],
    ];

    $form['filebrowser']['rights']['create_folders'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Allow folder to be created"),
      '#description' => $this->t("Users with proper permission may create create new folders."),
      '#options' => [0, 1],
      '#default_value' => isset($nodeValues->createFolders) ? $nodeValues->createFolders : $config['rights']['create_folders'],
    ];

    // D7 hook_filebrowser_download_manager_info changed to Event
    // removed: hook/event removed
    // Now hardcoded in Common
    $manager_options = $this->common->getDownloadManagerOptions();

    $form['filebrowser']['rights']['download_manager'] = [
      '#type' => 'select',
      '#title' => $this->t("Download manager"),
      '#description' => $this->t("A download manager will handle the way of download folder files."),
      '#options' => $this->common->toCheckboxes($manager_options),
      '#default_value' => isset($nodeValues->downloadManager) ? $nodeValues->downloadManager : $config['rights']['download_manager'],
    ];

    $form['filebrowser']['rights']['force_download'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Force download"),
      '#options' => [0, 1],
      '#description' => $this->t("If you select this options clicking a file-link will download the file. Leave this option off if you want the file to open in your browser."),
      '#default_value' => isset($nodeValues->forceDownload) ? $nodeValues->forceDownload :
        $config['rights']['force_download'],
    ];

    $form['filebrowser']['rights']['forbidden_files'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Blacklist. These files will not show in your listing'),
      '#description' => $this->t('List of patterns of forbidden files, you can use wildcards (ex. .*).'),
      '#default_value' => isset($nodeValues->forbiddenFiles) ? $nodeValues->forbiddenFiles : $config['rights']['forbidden_files'],
    ];

    $form['filebrowser']['rights']['whitelist'] = [
      '#type' => 'textarea',
      '#title' => $this->t('White list'),
      '#description' => $this->t('List of patterns to filter, one per line, you can use wildcards (ex. *.pdf).'),
    //  '#weight' => $weight++,
      '#default_value' => isset($nodeValues->whitelist) ? $nodeValues->whitelist : $config['rights']['whitelist'],
    ];

    $form['filebrowser']['uploads'] = [
      //  '#tree' => false,
      '#type' => 'details',
      '#title' => $this->t('Upload'),
      '#open' => ($isConfigForm) ? false : true,
   //  '#weight' => $weight++,
    ];

    $form['filebrowser']['uploads']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow uploads'),
      '#description' => $this->t('Users with proper permissions may upload files.'),
      '#options' => [0, 1],
      '#default_value' => isset($nodeValues->enabled) ? $nodeValues->enabled : $config['uploads']['enabled'],
    ];

    $form['filebrowser']['uploads']['allow_overwrite'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow overwrites'),
      '#description' => $this->t('Overwrite existing files if they already exists.'),
      '#options' => [0, 1],
      '#default_value' => isset($nodeValues->allowOverwrite) ? $nodeValues->allowOverwrite : $config['uploads']['allow_overwrite'],
    ];

    $form['filebrowser']['uploads']['accepted'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Accepted files for upload'),
      '#description' => $this->t('List of file extensions accepted for upload, separated by a comma or space. Do not include the leading dot.'),
      '#default_value' => isset($nodeValues->accepted) ? $nodeValues->accepted : $config['uploads']['accepted'],
      '#required' => true,
    ];

    $form['filebrowser']['presentation'] = [
      // '#tree' => false,
      '#type' => 'details',
      '#title' => $this->t('Presentation'),
      '#open' => ($isConfigForm) ? false : true,
    ];

    // D7 hook_filebrowser_presentation converted to Event
    // removed: Removed View Options hook/event.
    // Now hardcoded in Common->getFolderViewOptions

    $view_options = $this->common->getFolderViewOptions();

    $form['filebrowser']['presentation']['default_view'] = [
      '#type' => 'select',
      '#title' => $this->t("Default view"),
      '#options' => $this->common->toCheckboxes($view_options),
      '#default_value' => isset($nodeValues->defaultView) ? $nodeValues->defaultView : $config['presentation']['default_view'],
    ];

    $form['filebrowser']['presentation']['encoding'] = [
      '#type' => 'textfield',
      '#title' => $this->t('FileSystem encoding'),
      '#description' => $this->t('Set your file system encoding (UTF-8, ISO-8859-15, etc.).'),
      '#default_value' => isset($nodeValues->encoding) ? $nodeValues->encoding : $config['presentation']['encoding'],
    ];

    $form['filebrowser']['presentation']['hide_extension'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide file extensions'),
      '#options' => [0, 1],
      '#default_value' => isset($nodeValues->hideExtension) ? $nodeValues->hideExtension : $config['presentation']['hide_extension'],
    ];

    /**  @var MetadataInfo $event */
    $options = [];
    $e = new MetadataInfo($options);
    $event = $dispatcher->dispatch('filebrowser.metadata_info', $e);
    $columns = $event->getMetaDataInfo();
    $column_options = $this->common->toCheckboxes($columns);
    $sortable = [];

    $form['filebrowser']['presentation']['visible_columns'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t("Visible columns"),
      '#options' => $column_options,
      '#default_value' => isset($nodeValues->visibleColumns) ? $nodeValues->visibleColumns : $config['presentation']['visible_columns'],
    ];
    // set name option to true and disable it
    $form['filebrowser']['presentation']['visible_columns']['name']['#value'] = Common::NAME;
    $form['filebrowser']['presentation']['visible_columns']['name']['#disabled'] = true;

    foreach ($columns as $key => $data) {
      if (isset($data['sortable']) && $data['sortable']) {
        $sortable[$key] = $data['title'];
      }
    }

    $form['filebrowser']['presentation']['default_sort'] = [
      '#type' => 'select',
      '#title' => $this->t("Default sort"),
      '#options' => $sortable,
      '#default_value' => isset($nodeValues->defaultSort) ? $nodeValues->defaultSort : $config['presentation']['default_sort'],
    ];

    $form['filebrowser']['presentation']['default_sort_order'] = [
      '#type' => 'select',
      '#title' => $this->t("Default sort order"),
      '#options' => [
        'asc' => $this->t('Ascending'),
        'desc' => $this->t('Descending'),
      ],
      '#default_value' => isset($nodeValues->defaultSortOrder) ? $nodeValues->defaultSortOrder : $config['presentation']['default_sort_order'],
    ];

    // Don't set for a config form
    if (!$isConfigForm) {
      $form['filebrowser']['presentation']['grid_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Settings for grid display'),
      ];

      $form['filebrowser']['presentation']['grid_settings']['alignment'] = [
        '#type' => 'select',
        '#title' => $this->t('Grid orientation'),
        '#options' => [
          'horizontal' => $this->t('horizontal'),
        ],
        '#description' => $this->t('Select the orientation for aligning the grids'),
      ];

      $form['filebrowser']['presentation']['grid_settings']['columns'] = [
        '#type' => 'number',
        '#min' => 1,
        '#max' => 9,
        '#step' => 1,
        '#title' => $this->t('Number of columns'),
        '#default_value' => isset($nodeValues->gridColumns) ? $nodeValues->gridColumns : 4,
        //'#open'=> true,
      ];
      $image_styles = \Drupal::service('entity_type.manager')->getStorage('image_style')->loadMultiple();
      $styles = [];
      foreach ($image_styles as $key => $image_style) {
        $styles[$key] = $image_style->label();
      }
      $form['filebrowser']['presentation']['grid_settings']['image_style'] = [
        '#type' => 'select',
        '#title' => $this->t('Image style'),
        '#options' => $styles,
        '#default_value' => isset($nodeValues->gridImageStyle) ? $nodeValues->gridImageStyle : 'thumbnail',
        '#description' => $this->t('Select the image style to be applied to the images in the gris. You can define your own images in /admin/config/media/image-styles'),
      ];

      $form['filebrowser']['presentation']['grid_settings']['auto_width'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Automatic width'),
        '#options' => [0, 1],
        //'#default_value' => isset($nodeValues->hideExtension) ? $nodeValues->hideExtension : $config['hide_extension'],
      ];

      $form['filebrowser']['presentation']['grid_settings']['grid_hide_title'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Hide title'),
        '#options' => [0, 1],
        '#default_value' => isset($nodeValues->gridHideTitle) ? $nodeValues->gridHideTitle : 0,
      ];

      $form['filebrowser']['presentation']['grid_settings']['grid_width'] = [
        '#type' => 'number',
        '#min' => 100,
        '#max' => 500,
        '#step' => 20,
        '#title' => $this->t('Width of the grid'),
        '#default_value' => isset($nodeValues->gridWidth) ? $nodeValues->gridWidth : 100,
        //'#open'=> true,
      ];
      $form['filebrowser']['presentation']['grid_settings']['grid_height'] = [
        '#type' => 'number',
        '#min' => 100,
        '#max' => 500,
        '#step' => 20,
        '#title' => $this->t('Height of the grids'),
        '#default_value' => isset($nodeValues->gridHeight) ? $nodeValues->gridHeight : 100,
        //'#open'=> true,
      ];
    }
    return $form;
  }

  /**
   * @inheritdoc
   */
  public function loadMultipleData($nids) {
    $result = $this->storage->StorageLoadMultipleData($nids, true);
    return $result;
  }

  public function loadData($nid) {
    $result = $this->storage->storageLoadData($nid);
    return $result;
  }


  public function updateFilebrowser(Filebrowser $filebrowser, $mode) {
    /** @var \Drupal\Core\Database\Connection $connection */

    if (empty($filebrowser->nid)) {
      \Drupal::messenger()->addError($this->t('No filebrowser data available in node - remove exit'));
      exit();
    }

    $record['nid'] = $filebrowser->nid;
    $record['folder_path'] = $filebrowser->folderPath;
    $record['properties'] = serialize($filebrowser);

    if ($mode == 'insert') {
      $this->storage->insert($record);
    }
    elseif ($mode == 'edit') {
      $this->storage->update($record);
    }
  }

  public static function validateFolderPath($element, FormStateInterface $form_state) {

    $folder_path = $form_state->getValue('filebrowser')['folder_path'];
    $file_service = \Drupal::service('file_system');
    $scheme = $file_service->uriScheme($folder_path);
    $error = false;
    $message = '';

    // Scheme is valid?
    if (!$scheme || !$file_service->validScheme($scheme)) {
      $message = t('The scheme: %scheme in your uri is not valid.', ['%scheme' => $scheme]);
      $error = true;
      $form_state->setError($element, $message);
    }
    // is directory name contains illegal characters?
    if (strpbrk($folder_path, "\\/?%*:|\"<>") === TRUE) {
      $message = t('This @name contains illegal characters.', ['name => $folder_path']);
      $error = true;
      $form_state->setError($element, $message);
    }

    if (!$error) {
      // name is safe, create the folder if it doesn't exists.
      if (!file_exists($folder_path)) {
        if (\Drupal::service('file_system')->mkdir($folder_path, NULL, TRUE, NULL)) {
          \Drupal::messenger()->addMessage('Folder location @uri created.', ['@uri' => $folder_path]);
        }
        else {
          $error = true;
          $message = t('@url does not exist and Filebrowser can not create it.', ['@url' => $folder_path]);
          $form_state->setError($element, $message );
        }
      }
    }
    return [
      'error' => $error,
      'error_msg' => $message,
    ];
  }

  /**
   * Ajax callback to validate the folder path field.
   * @param array $form
   * @param FormStateInterface $form_state
   * @return AjaxResponse
   */
//  public function validateFolderPathAjax(array &$form, FormStateInterface $form_state) {
//    $result = $this->checkFolderPath($form, $form_state);
//    $response = new AjaxResponse();
//    if ($result['error']) {
//      $css = ['border' => '1px solid green'];
//      $message = $this->t('Folder uri OK.');
//    }
//    else {
//      $css = ['border' => '1px solid red'];
//      $message = $this->$result['error_msg'];
//    }
//    $response->addCommand(new CssCommand('#edit-filebrowser-folder-path', $css));
//    $response->addCommand(new HtmlCommand('.folder-path-valid-message', $message));
//    return $response;
//  }
//
//  public function checkFolderPath($form, FormStateInterface $form_state) {
//
//    $folder_path = $form_state->getValue('filebrowser')['folder_path'];
//    $file_service = \Drupal::service('file_system');
//    $scheme = $file_service->uriScheme($folder_path);
//    $error = false;
//    $message = '';
//
//    // Scheme is valid?
//    if (!$scheme || !$file_service->validScheme($scheme)) {
//      $message = $this->t('The scheme: %scheme in your uri is not valid.', ['%scheme' => $scheme]);
//      $error = true;
//     // $form_state->setError($element, $message);
//    }
//    // is directory name contains illegal characters?
//    //todo:check
//    if (strpbrk($folder_path, "\\/?%*:|\"<>") === TRUE) {
//      $message = $this->t('This @name contains illegal characters.', ['name => $folder_path']);
//      $error = true;
//     // $form_state->setError($element, $message);
//    }
//
//    if (!$error) {
//      // name is safe, create the folder if it doesn't exists.
//      if (!file_exists($folder_path)) {
//        if (\Drupal::service('file_system')->mkdir($folder_path, NULL, TRUE, NULL)) {
//          drupal_set_message($this->t('Folder location @uri created.', ['@uri' => $folder_path]));
//        }
//        else {
//          $error = true;
//          $message = $this->t('@url does not exist and Filebrowser can not create it.', ['@url' => $folder_path]);
//      //    $form_state->setError($element, $message );
//        }
//      }
//    }
//    return [
//      'error' => $error,
//      'error_msg' => $message,
//    ];
//  }

  /**
   * @param \Drupal\node\NodeInterface $node
   * @param array $display_list
   * @return null
   */
  public function createPresentation($node, array $display_list) {

    $view = $node->filebrowser->defaultView;
    $presentation = new Presentation($node, $display_list);
    switch ($view) {
      case 'list-view':
        return $presentation->listView();
      case 'icon-view':
        return $presentation->iconView();
      default:
        return
          \Drupal::messenger()->addError($this->t('Selected display @display not available ', ['@display' => $view]));
    }
  }

  protected function folderPathDescription() {
    $text = $this->t('Uri of the directory: <strong>scheme://directory[/sub-directory]</strong><br>The schemes <strong>public://</strong>&nbsp;and&nbsp;<strong>private://</strong> are available by default.<br>');
    $array = ['@drupal-flysystem' => 'http://drupal.org/project/flysystem'];
    if (\Drupal::hasService('flysystem_factory')) {
      $available_schemes = \Drupal::service('flysystem_factory')->getSchemes();
      $extra = $this->t('Schemes provided by <a href = "@drupal-flysystem">Flysystem module:</a><ul>', $array);
      foreach($available_schemes as $scheme) {
        $extra .= '<li><strong>' . $scheme . '://</strong></li>';
      }
      $extra .= '</li>';
    }
    else {
      $extra = $this->t('You can use remote storage (such as AWS s3) by installing the <a href = "@drupal-flysystem">Flysystem module</a>', $array);
    }
    return $text . $extra;
  }

  protected function tokenMessage() {
    return $this->t('If you install the <a href = "@token_module">token module</a> you can use tokens in your folder path.', ['@token_module' => 'https://www.drupal.org/project/token']);
  }

}