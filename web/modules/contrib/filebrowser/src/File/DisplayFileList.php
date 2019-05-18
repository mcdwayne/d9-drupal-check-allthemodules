<?php

namespace Drupal\filebrowser\File;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\filebrowser\Events\MetadataEvent;
use Drupal\filebrowser\ServerFileList;
use Drupal\node\NodeInterface;

/**
 * Class FileDisplayList
 * @package Drupal\filebrowser
 * This class holds the list of files to be displayed on the filebrowser node.
 * These files are retrieved from the filesystem and filtered for user and node access.
 * The array produced by this class contains all data required to be fed into the
 * presentation  (list-view, icon-view class).
 * FileDisplayList = obj
 *   ->data // data essential for this collection]
 *   ->files // files to be displayed]
 */

class DisplayFileList extends ControllerBase {

  protected $data;

  /**
   * List of the files ready to be passes to presentation
   * This list will appear on the node view
   * @var array $displayFiles
   */
  protected $files;
  /**
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * @var array $list;
   */
  protected $items;


  /**
   * List of files as retrieved from the server source and
   * already filters as per node and per user permissions
   * @var array $serverFileList
   */
  protected $serverFileList;

  /**
   * @var \Drupal\filebrowser\Services\Common
   */
  protected $common;


  /**
   * @var \Drupal\filebrowser\Services\FilebrowserValidator
   */
  protected $validator;

  /**
   * @var \Drupal\filebrowser\Services\FilebrowserStorage
   */
  protected $storage;

  /**
   * @var \Drupal\Core\Session\AccountInterface $user
   */
  protected $user;

  /**
   * @var \Drupal\filebrowser\Filebrowser
   */
  protected $filebrowser;

  /**
   * @var int $fid
   */
  protected $fid;

  /**
   * @var string $relativePath
   */
  protected $relativePath;

  /**
   * @var boolean
   */
  protected $isSubDir;

  /**
   * @var string fsRoot The root directory of this filebrowser
   */
  protected $fsRoot;

  public function __construct(NodeInterface $node, $fid) {
    $this->data = null;

    $this->files = [];
    $this->node = $node;
    $this->filebrowser = $node->filebrowser;
    $this->common = \Drupal::service('filebrowser.common');
    $this->storage = \Drupal::service('filebrowser.storage');
    $this->validator = \Drupal::service('filebrowser.validator');
    $this->user = \Drupal::currentUser();
    $this->fid = $fid;
    $this->createFileDisplayList();
  }

  /**
   * @return mixed
   */
  public function get() {
    return [
      'data' => $this->data,
      'files' => $this->files,
      ];
  }

  /**
   *  Creates the data required to build the Filebrowser listing in following steps
   *    - look at the request object and decide what files are requested
   *    - Retrieves the DB records for this Filebrowser
   *    - Get all the files from the server and filter them for *per-node* settings
   *    - Iterate over this file list and build the DB contents for storage
   *    - The DB contents should reflect the filtered file list from the server
   *    - The purpose of the DB contents is to provide an url over which files and
   *     folders can be requested/
   *    - return the list of files (DB contents) for display in the node.
   * Display depends on the selected view. example: list view returns a table
   * displaying one file per row.
   *
   * @return mixed
   */
  protected function createFileDisplayList() {
    // file list
    $cid = 'filebrowser/' . $this->node->id() . '/' . $this->fid;

    //you are requesting a subdir: node/23?fid=nn
    if ($this->fid) {
      $content = $this->storage->nodeContentLoadMultiple([$this->fid]);
      //debug($content[$this->fid]);
      if (empty($content[$this->fid])) {
        // There is no DB data for this fid
        return false;
      }
      // When accessing a subdir relativePath will be /subdir[/other sub dir]
      $this->relativePath = $content[$this->fid]['path'];
      $this->fsRoot = $this->relativePath;
    }
    // you are requesting the basic node like: /node/23
    else {
      $this->relativePath = '/';
    }

    $this->isSubDir = $this->relativePath != '/';
    // If this is a sub dir, check if we may access it, else redirect to root_dir.
    if ($this->isSubDir && !$this->common->canExploreSubFolders($this->node)) {
      drupal_set_message($this->t('You\'re not allowed to browse sub folders.'), 'error');
      return false;
    }

    // full path valid?
    if ($this->fsRoot === false) {
      drupal_set_message($this->t('Configured folder is not readable or is not a directory.'), 'error');
      return false;
    }

    // retrieve files from the system - returned list is filtered *per-node* settings
    $list = new ServerFileList($this->node, $this->relativePath);
    $this->serverFileList = $list->getList();
    //debug($this->serverFileList);

    // create DB contents and return the files for display ($result)
    $result = $this->processServerFileList();
    $this->files = $result;
    $this->data['fid'] = $this->fid;

    // cache the results
    $cache = [
      'files' => $this->files,
      'data' => $this->data,
    ];
    \Drupal::cache()->set($cid, $cache, -1,['filebrowser:node:' . $this->node->id()]);
    return $this;
  }


  protected function processServerFileList() {
    /** @var /Drupal/filebrowser/File/DisplayFile $result_file */

    $stats = ['folders_count' => 0, 'files_count' => 0, 'total_size' => 0 ];
    $encoding = $this->node->filebrowser->encoding;

    // get the DB_contents for this nid
    // first time after node creation $db_content = NULL; there is nothing in the DB
    // If there is content it will return a filename as key, with next indexes:
    // array ('nid' => '1', 'fid' => '3', 'root' => '/', 'path' => '/',)

    $db_content = $this->storage->loadRecordsFromRoot($this->node->id(), $this->relativePath);
    // debug($db_content, 'DB CONTENT');
    // Iterate over file list from the server
    if (!is_null($this->serverFileList)) {
      foreach ($this->serverFileList as $key => $fs_file) {

        // Build file relative path
        $file_relative_path = $this->buildFileRelativePath($fs_file->filename, $encoding);

        // Build database file record if it doesn't exists
        if (!isset($db_content[$file_relative_path])) {
          $db_content[$file_relative_path] = [
            'exists' => true,
            'nid' => $this->node->id(),
            'root' => $this->relativePath,
            'path' => $file_relative_path,
            'description' => $this->t('No description.'),
            'file_data' => $fs_file,
          ];
        }
        $db_content[$file_relative_path]['exists'] = true;
        $db_content[$file_relative_path]['display_name'] = $fs_file->filename;
        $result_file = new DisplayFile($this->node->id());
        $result_file->fileSetData($file_relative_path, $fs_file, $stats, $db_content[$file_relative_path], $this->fsRoot);
        $result_list[$fs_file->filename] = $result_file;
      }
    }

    // The abstracted filesystem does not provide . and .. files. Therefore
    // we will create them manually
    if ($this->isSubDir) {
      $subDir = new DisplayFile($this->node->id());
      // Create the .. file data
      $result_list['..'] = $subDir->createSubdir($this->relativePath);

      // Create the . file data
      $file = new DisplayFile($this->node->id());
      $result_list['.'] = $file->createUpDir($this->relativePath);

      // Set DB content for Up-directory. In this case the '/' folder
      $this->createUpDirContent($db_content['/']);

      //set DB record for current directory (. file)
      if (!isset($db_content[$this->relativePath] )) {
        $db_content[$this->relativePath] = [
          'exists' => TRUE,
          'nid' => $this->node->id(),
          'root' => $this->relativePath,
          'path' => $this->relativePath,
        ];
      }
      $db_content[$this->relativePath]['exists'] = true;
      $db_content[$this->relativePath]['display_name'] = '.';

    }
    else {
      // not a sub dir so we only set the . file and / DB data
      if (!isset($db_content['/'])) {
        $db_content['/'] = [
          'nid' => $this->node->id(),
          'root' => '/',
          'path' => '/',
        ];
      }
      $db_content['/']['exists'] = true;
      $db_content['/']['display_name'] = '.';

      // changes to the File System Array
      $result_file = new DisplayFile();
      $result_list['.'] = $result_file->createUpDir($this->relativePath);
    }
    //debug($db_content, 'END DB CONTENT');
    // Set global folder properties
    $this->data['stats'] = $this->buildStatistics($result_list);
    $this->data['relative_path'] = $this->relativePath;
    $this->dbSync($db_content, $result_list, $this->data);
    return $result_list;
  }

  /**
   * Synchronizes what we seen on filesystem with what is stored in database
   * We also build an access URL (link) for each file as it is why we stored
   * this stuff
   * in DB (have a unique ID for each file and path) to get rid of national character
   * mess in URLS.
   *
   * @param array $db_content
   * @param array $files
   * List of DisplayFiles objects
   * @param integer $subdir_fid
   */

  protected function dbSync(&$db_content, &$files, $subdir_fid = null) {
    /** @var DisplayFile $files[$key]  */
    $to_delete = [];
    // Build the fragment to be used with folders.
    $theme = \Drupal::theme()->getActiveTheme()->getName();
    $fragment = 'block-' . str_replace('_', '-', $theme) . '-page-title';

    foreach ($db_content as $path => &$record) {
      if (!isset($record['nid'])) {
      }
      if (!isset($record['exists'])) {
        $to_delete[] = $record['fid'];
      }
      else {
        if (!isset($record['fid'])) {
          $record['fid'] = $this->storage->insertRecord($record);
        }
        $key = $record['display_name'];
        $files[$key]->fid = $record['fid'];
        $link = $this->makeLink($files[$key], $record['fid'], $fragment);
        $files[$key]->link = $link->toRenderable();
        $files[$key]->href = $link->getUrl();

        // fire an event so modules can create metadata for this file.
        /** @var MetadataEvent $event */
        $dispatcher = \Drupal::service('event_dispatcher');
        $e = new MetadataEvent($this->node->id(), $record['fid'], $files[$key], $subdir_fid, $this->filebrowser->visibleColumns);
        $dispatcher->dispatch('filebrowser.metadata_event', $e);
      }
    }

    // A quick way to drip obsolete records
    if (count($to_delete)) {
      $this->storage->deleteFileRecords($to_delete);
    }
  }

  /** Creates links for the file list
   * @param DisplayFile $file
   * @param int $fid
   * @param string $fragment
   *
   * for file: 'http://drupal.dev/filebrowser/download/4'
   * for folder: 'http://drupal.dev/node/1?fid=23#fragment
   * @return Link
   */
  protected function makeLink(DisplayFile $file, $fid = null, $fragment = null) {
    $options = ['query' => ['fid' => $fid]];
      if (isset($fragment)) {
        $options['fragment'] = $fragment;
      }
    if ($file->displayName == '..') {
      $display_name = $this->t('Go up');
      return Link::createFromRoute($display_name, 'entity.node.canonical', ['node' => $this->node->id()], $options);
    }
    $name = $this->filebrowser->hideExtension ? pathinfo($file->displayName, PATHINFO_FILENAME) : $file->displayName;
    if ($file->fileData->type != 'file') {
      return Link::createFromRoute($name, 'entity.node.canonical', ['node' => $this->node->id()], $options);
    }
    else {
      return Link::createFromRoute($name, 'filebrowser.page_download',['fid' => $fid]);
    }
  }

  /**
   * Need this function to build the "Go-up" and Navigate-to-folder links in the icon view
   * todo: solve this better by integrating with makeLink()
   * @param \Drupal\filebrowser\File\DisplayFile $file
   * @param null|int $fid
   */
  protected function makeAnchor(DisplayFile $file, $fid = null) {
  }

  protected function buildFileRelativePath($fs_filename, $encoding) {
    $filename = $this->validator->encodingToFs($encoding, $fs_filename);
    return $this->relativePath . ($this->relativePath != '/' ? '/' : '') . $filename;
  }

  protected function createUpDirContent(&$array){
    $parent_path = $this->parentFolder();
    $content = $this->storage->loadRecordFromPath($this->node->id(), $parent_path);
    if ($content) {
      foreach ($content as $key => $value) {
        $array[$key] = $value;
      }
    }
    else {
      drupal_set_message($this->t('No content in method LoadRecordFromPath', 'error'));
    }
    $array['exists'] = true;
    $array['display_name'] = '..';
  }

  protected function parentFolder() {
    $array = explode('/', $this->relativePath);
    if (count($array) < 3 ) {
      return '/';
    }
    else {
      unset($array[count($array)-1]);
      return $result = implode('/', $array);
    }
  }

  protected function buildStatistics($list) {
    //debug(array_keys($list));
    $files = 0; $folders = 0; $total_size = 0;
    foreach($list as $key => $item) {
      if (in_array($key, ['.', '..'])) {
      }
      else {
        if ($item->fileData->type == 'file') {
          $files++;
          $total_size = $total_size + $item->fileData->size;
        }
        else {
          $folders++;
        }
      }
    }
    return [
      'files' => $files,
      'folders' => $folders,
      'size' => $total_size,
    ];
  }

}