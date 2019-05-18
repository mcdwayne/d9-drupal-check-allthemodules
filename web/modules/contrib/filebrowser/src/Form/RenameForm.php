<?php

namespace Drupal\filebrowser\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class RenameForm extends ConfirmFormBase {

  /**
   * The node holding the dir-listing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * @var string $fids String of the fids of files to be edited
   */
  protected $fids;

  /**
   * @var string
   */
  protected $relativeRoot;

  /**
   * @var string
   */
  protected $queryFid;

  /**
   * @var array $contents
   */
  protected $contents;

  /**
   * Filebrowser object holds specific data
   *
   * @var \Drupal\filebrowser\Filebrowser
   */
  protected $filebrowser;

  /**
   * @var \Drupal\filebrowser\Services\FilebrowserStorage
   */
  protected $storage;

  /**
   * @var array Array containing the old file names before renaming
   */
  protected $oldNames;

  /**
   * @var \Drupal\filebrowser\Services\Common
   */
  public $common;

  /**
   * @var array
   * Array containing the redirect parts for a route
   */
  protected $route;

  public function getFormId() {
    return 'filebrowser_rename_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $nid = null, $query_fid = null, $fids_str = null, $ajax = null) {
    /** @var \Drupal\filebrowser\Services\FilebrowserValidator $validate */
    $this->common = \Drupal::service('filebrowser.common');
    $this->storage = \Drupal::service('filebrowser.storage');
    $this->node = Node::load($nid);
    $this->oldNames = [];
    $this->fids = explode(',', $fids_str);
    $this->queryFid = $query_fid;
    $this->contents = $this->storage->nodeContentLoadMultiple($this->fids);
    $this->relativeRoot = reset($this->contents)['root'];
    $this->route = $this->common->redirectRoute($this->queryFid, $this->node->id());

    $form['#tree'] = true;

    // If this form is to be presented in a slide-down window we
    // will set the attributes and at a close-window link
    if($ajax) {
      $form['#attributes'] = ['class' => ['form-in-slide-down'],];
      $form['close-window'] = $this->common->closeButtonMarkup();
    }

    $form['help'] = [
      '#markup' => $this->t('Enter new filename <strong>without</strong> extension.'),
    ];

    foreach($this->contents as $key => $row) {
      $this->contents[$key]['data_array'] = $data = unserialize($row['file_data']);
      $filename = \Drupal::service('file_system')->basename($data->uri);
      $name = pathinfo($filename, PATHINFO_FILENAME);
      $this->oldNames[$key] = $name;
      $form['new_name'][$key] = [
        '#type' => 'textfield',
        '#title' => $filename,
        '#default_value' => $name,
       // '#description' => $row['description'],
      ];
    }
    $form = parent::buildForm($form, $form_state);
    $form['actions']['cancel']['#attributes']['class'][] = 'button btn btn-default';
    return $form;
  }

  public function getQuestion() {
    return $this->t('Rename selected items ...');
  }

  public function getDescription() {
    return $this->t('This action cannot be undone.');
  }

  public function getConfirmText() {
    return $this->t('Rename');
  }

  public function getCancelText() {
    return $this->t('Cancel');
  }

  public function getCancelUrl() {
    return Url::fromRoute($this->route['name'], $this->route['node'], $this->route['query']);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Do not accept path separators into new file names.
    foreach ($form_state->getValue('new_name') as $fid => $new_name) {
      if (strpos($new_name, '/') !== false || strpos($new_name, "\\") !== false) {
        $form_state->setErrorByName('new_name[' . $fid . ']', $this->t('Invalid filename: :filename', [':filename' => $new_name]));
      }
    }
    parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $new_names = $form_state->getValue('new_name');

    foreach ($new_names as $fid => $new_name) {
      // Check if names were changed
      if (!empty($new_name) && ($new_name != $this->oldNames[$fid])) {
        $file_data = unserialize($this->contents[$fid]['file_data']);
        $relative_root = $this->relativeRoot == '/' ? "" : $this->relativeRoot;
        if ($is_file = ($file_data->type == 'file')) {
          $new_filename = $relative_root . '/' . $new_name . "." . pathinfo($file_data->filename, PATHINFO_EXTENSION);
          $new_uri = $this->node->filebrowser->folderPath . $new_filename;

          $success = rename($file_data->uri, $new_uri);
          if ($success) {
            drupal_set_message($this->t('Renamed @old to @new', [
              '@old' => $file_data->uri,
              '@new' => $new_uri
            ]));
            $this->updateFileData($file_data, $new_uri);

            // serialize the updated data and store it in DB
            $data = serialize($file_data);
            $this->storage->updateContentField('fid', $fid, 'file_data', $data);
            $this->storage->updateContentField('fid', $fid, 'path', $new_filename);
          }
          else {
            drupal_set_message($this->t('Can not rename @old', ['@old' => $file_data->uri]), 'error');
          }
        }
        else {
          // this is a folder, we will not change anything
          drupal_set_message($this->t('@old is a folder. Folder rename is not supported', ['@old' => $file_data->uri]), 'error');
        }
      }
    }
    Cache::invalidateTags(['filebrowser:node:' . $this->node->id()]);
    $form_state->setRedirect($this->route['name'], $this->route['node'], $this->route['query']);;
  }

// Folder renaming is disabled in D8 version.
// Folder renaming is very expensive on remote storage because directories are prefixes to the files.
// Renaming these "directories" requires all the files with these prefixes to be changed. Also, it requires
// all the paths to be changed in the database. Until we find a safe and stable way to do this,
// while maintaining the assigned fids, Folder renaming is not available.

//  public function submitForm(array &$form, FormStateInterface $form_state) {
//    $new_names = $form_state->getValue('new_name');
//
//    foreach ($new_names as $fid => $new_name) {
//      // Check if names were changed
//      if (!empty($new_name) && ($new_name != $this->oldNames[$fid])) {
//        $file_data = unserialize($this->contents[$fid]['file_data']);
//        $relative_root = $this->relativeRoot == '/' ? "" : $this->relativeRoot;
//        if ($is_file = ($file_data->type == 'file')) {
//          $new_filename = $relative_root . '/' . $new_name . "." . pathinfo($file_data->filename, PATHINFO_EXTENSION);
//        }
//        else {
//          $is_file = false;
//          $new_filename = $relative_root . '/' . $new_name;
//          $old_filename = $relative_root . '/' . $file_data->name;
//        }
//        $new_uri = $this->node->filebrowser->folderPath . $new_filename;
//
//        $success = rename($file_data->uri, $new_uri);
//        if ($success) {
//          drupal_set_message($this->t('Renamed @old to @new', ['@old' => $file_data->uri, '@new' => $new_uri]));
//          $this->updateFileData($file_data, $new_uri);
//
//          // serialize the updated data and store it in DB
//          $data = serialize($file_data);
//          $this->storage->updateContentField('fid', $fid, 'file_data', $data);
//          $this->storage->updateContentField('fid', $fid, 'path', $new_filename);
//
//          // if this is a folder we have to update the root for this and other files also:
//          if (!$is_file) {
//            // get all the content for this node.
//            // I am not very happy with this fiddling around with the DB.
//            // todo: better solution using entities
//            $rows = $this->storage->loadAllRecordsFromRoot($this->node->id());
//            foreach($rows as $row) {
//              $common_part_old = substr($row->root, 0, strlen($old_filename));
//              if ($common_part_old == $old_filename) {
//                $fileData = unserialize($row->file_data);
//                // Update root
//                $root = str_replace($common_part_old, $new_filename, $row->root);
//
//                // Update path
//                $path = str_replace($common_part_old, $new_filename, $row->path);
//
//                // Update uri
//                $uri = !empty($fileData->uri) ? str_replace($common_part_old, $new_filename, $fileData->uri) : null;
//
//                // Update url
//                $url = !empty($fileData->url) ? str_replace($common_part_old, $new_filename, $fileData->url) : null;
//                if (!is_null($uri)) {
//                  $fileData->uri = $uri;
//                  $fileData->url = $url;
//                  $data = serialize($fileData);
//                }
//                else {
//                  $data = "";
//                }
//
//                $array = [
//                  'root' => $root,
//                  'path' => $path,
//                  'file_data' => $data,
//                ];
//                foreach($array as $key => $value) {
//                  $this->storage->updateContentField('fid', $row->fid, $key, $value);
//                }
//              }
//            }
//          }
//          Cache::invalidateTags(['filebrowser:node:' . $this->node->id()]);
//        }
//        else {
//          drupal_set_message(t('Can not rename @old', ['@old' => $file_data->uri]), 'error');
//        }
//      }
//    }
//    $form_state->setRedirect($this->route['name'], $this->route['node'], $this->route['query']);;
//  }

  protected function updateFileData(&$file, $uri) {
    $file->uri = $uri;
    $file->filename = \Drupal::service('file_system')->basename($uri);
    $file->name = pathinfo($file->filename, PATHINFO_FILENAME);
    $file->url = file_create_url($file->uri);
  }

}
