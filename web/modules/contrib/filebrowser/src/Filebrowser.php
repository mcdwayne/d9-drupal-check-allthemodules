<?php

namespace Drupal\filebrowser;

class Filebrowser {

  public $nid;
  /**
   * @var string
   */
  public $folderPath;
 // public $folderPathEncoded;
  public $fileSystem;
  public $exploreSubdirs;
  public $downloadArchive;
  public $createFolders;
  public $downloadManager;
  public $forceDownload;
  public $forbiddenFiles;
  public $whitelist;
  public $enabled;
  public $allowOverwrite;
  /**
   * @var string
   * List of file patterns accepted for upload. Empty means anything
   */
  public $accepted;
  public $defaultView;
  public $encoding;
  public $hideExtension;
  public $visibleColumns;
  public $defaultSort;
  public $defaultSortOrder;
  public $gridAlignment;
  public $gridColumns;
  public $gridImageStyle;
  public $gridAutoWidth;
  public $gridHeight;
  public $gridWidth;
  public $gridHideTitle;

  /**
   * Filebrowser constructor.
   * Create the object from the node form values
   * @param mixed $settings
   */
  public function __construct($settings) {
    if (is_numeric($settings)) {
      // $settings is a nid and we will create a new Filebrowser object
      $node_settings = \Drupal::service('filebrowser.storage')->loadNodeRecord($settings);
      $this->nid = $node_settings['nid'];
      $this->folderPath = $node_settings['folder_path'];
     // $this->folderPathEncoded = $node_settings['folder_path_encoded'];
      $properties = unserialize($node_settings['properties']);
      foreach ($properties as $property => $value) {
        $this->$property = $value;
      };
    }
    else {
      if (isset($settings['nid'])) {
        $this->nid = $settings['nid'];
      }
      $this->folderPath = $settings['folder_path'];
      $this->exploreSubdirs = $settings['rights']['explore_subdirs'];
      $this->downloadArchive = $settings['rights']['download_archive'];
      $this->createFolders = $settings['rights']['create_folders'];
      $this->downloadManager = $settings['rights']['download_manager'];
      $this->forceDownload = $settings['rights']['force_download'];
      $this->forbiddenFiles = $settings['rights']['forbidden_files'];
      $this->whitelist = $settings['rights']['whitelist'];
      $this->enabled = $settings['uploads']['enabled'];
      $this->allowOverwrite = $settings['uploads']['allow_overwrite'];
      $this->accepted = $settings['uploads']['accepted'];
      $this->defaultView = $settings['presentation']['default_view'];
      $this->encoding = $settings['presentation']['encoding'];
      $this->hideExtension = $settings['presentation']['hide_extension'];
      $this->visibleColumns = $settings['presentation']['visible_columns'];
      $this->defaultSort = $settings['presentation']['default_sort'];
      $this->defaultSortOrder = $settings['presentation']['default_sort_order'];
      $this->gridAlignment = $settings['presentation']['grid_settings']['alignment'];
      $this->gridColumns = $settings['presentation']['grid_settings']['columns'];
      $this->gridImageStyle = $settings['presentation']['grid_settings']['image_style'];
      $this->gridAutoWidth = $settings['presentation']['grid_settings']['auto_width'];
      $this->gridHeight = $settings['presentation']['grid_settings']['grid_height'];
      $this->gridWidth = $settings['presentation']['grid_settings']['grid_width'];
      $this->gridHideTitle = $settings['presentation']['grid_settings']['grid_hide_title'];

      if (isset($settings['handlers'])) {
        $this->handlers = $settings['handlers'];
      }
    }

  }

}