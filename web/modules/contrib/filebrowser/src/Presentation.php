<?php

namespace Drupal\filebrowser;

use Drupal\Core\Controller\ControllerBase;
use Drupal\filebrowser\Entity\FilebrowserMetadataEntity;
use Drupal\filebrowser\Events\MetadataInfo;
use Drupal\filebrowser\File\DisplayFile;
use Drupal\filebrowser\Grid\Grid;
use Drupal\filebrowser\Services\Common;
use Drupal\node\NodeInterface;

class Presentation extends ControllerBase{

  /**
   * @var \Drupal\filebrowser\Filebrowser;
   */
  protected $filebrowser;

  /**
   * @var array List of the DB contents
   */
  protected $dbFileList;

  /**
   * list of files on the file server
   * @var
   */
  protected $fsFileList;

  /**
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * @var \Drupal\filebrowser\Services\Common $common
   */
  protected $common;

  /**
   * @var array
   * Contains the form actions that current user has on the current node
   */
  protected $formActions;

  protected $listOfFids;

  /**
   * Presentation constructor.
   * @param NodeInterface $node
   * @param array $list
   */
  public function __construct(NodeInterface $node, array $list) {
    /** @var \Drupal\filebrowser\Filebrowser $filebrowser */
    $this->common = \Drupal::service('filebrowser.common');
    $this->node = $node;
    $this->filebrowser = $node->filebrowser;
    $this->dbFileList = $list;
    $this->listOfFids = array_column($list, 'fid');
    $this->init();
  }

  /**
   * Provides settings and actions that apply for all views
   */
  public function init() {
    // Actions allowed on the form
    $this->formActions = $this->common->userAllowedActions($this->node);
  }

  public function listView() {
    /** @var MetadataInfo $event
     * @var FilebrowserMetadataEntity $metadata */

    foreach($this->dbFileList['files'] as $key => $display_file_obj) {
      $fid = $display_file_obj->fid;

      // Collect the metadata now
      $metadatas = $this->retrieveMetadata($fid);
      foreach($metadatas as $metadata) {
        $contents = $metadata->getContent();
        foreach ($contents as $content) {
        }
      }
    }

    $default_sort = $this->filebrowser->defaultSort;
    $sort_order = $this->filebrowser->defaultSortOrder;
    $directory_empty = true;
    $column_names = [];

    $dispatcher = \Drupal::service('event_dispatcher');

    // Visible columns
    $visible_columns = [];
    $e = new MetadataInfo($column_names);
    $event = $dispatcher->dispatch('filebrowser.metadata_info', $e);
    $column_names = $event->getMetaDataInfo();

    // columns provided by Filebrowser:
    // icon, name, created, size, modified, mime_type, description

    // Create the $unsorted_rows array
    $this->createUnsortedRowsAndColumns($unsorted_rows, $visible_columns, $column_names, $directory_empty);

    $header = [];
    // Builder header and clean up unused columns
    $this->buildHeader($header, $unsorted_rows, $visible_columns, $column_names, $default_sort, $sort_order);

    // Split files in two heaps to preserve folders and files
    $result =  $this->splitFiles($this->dbFileList['files']);
    $just_files = isset($result['files']) ? $result['files'] : null;
    $just_folders = isset($result['folders']) ? $result['folders'] : null;
    $table_sort = tablesort_init($header);
    // Sort files according to correct column.
    if (isset($table_sort['sql'])) {
      $field = $table_sort['sql'];
      if (isset($column_names[$field]) && isset($column_names[$field]['sortable']) && $column_names[$field]['sortable']) {
        $sorter = null;
        switch ($column_names[$field]['type']) {
          case 'integer' :
            $sorter = function ($a, $b) use ($field) {
              $a = isset($a->$field) ? $a->$field : 0;
              $b = isset($b->$field) ? $b->$field : 0;
              return $a-$b;
            };
            break;

          case 'string' :
            $sorter = function ($a, $b) use ($field) {
              $a = isset($a->$field) ? $a->$field : '';
              $b = isset($b->$field) ? $b->$field : '';
              return -strcmp(strtolower($a), strtolower($b));
            };
            break;
        }

        if (!empty($just_folders)) {
          usort($just_folders, $sorter);
          if ($table_sort['sort'] == 'asc') {
            $just_folders = array_reverse($just_folders, true);
          }
        }
        if (!empty($just_files)) {
          usort($just_files, $sorter);
          if ($table_sort['sort'] == 'asc') {
            $just_files = array_reverse($just_files, true);
          }
        }
      }
    }

    $rows = [];
    if (!is_null($just_folders)) {
      foreach ($just_folders as $data) {
        $rows[$data->fid] = $unsorted_rows[$data->displayName];
      }
    }
    if (!is_null($just_files)) {
      foreach ($just_files as $data) {
        $rows[$data->fid] = $unsorted_rows[$data->displayName];
      }
    }
    // $this->dbFileList['.']['full_path'] contains the sub folder (relative path of this listing.
    // maybe we need a separate objects to bundle the per-listing data

    if (count($this->formActions) > 0 ) {
      $params = [
        'header' => $header,
        'rows' => $rows,
        'actions' => $this->formActions,
        'node' => $this->node,
        'dbFileList' => $this->dbFileList,
        ];
      return  \Drupal::formBuilder()->getForm('Drupal\filebrowser\Form\ActionForm', $params);
    }

    // align the rows to the proper column in the header
    $rows_aligned = [];
    foreach($rows as $row) {
      $rows_aligned[] = array_replace(array_flip(array_keys($header)), $row);
    }

    \Drupal::service('page_cache_kill_switch')->trigger();

    return [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows_aligned,
      '#sticky' => true,
      '#empty' => $this->t('This directory is empty'),
      '#attributes' => ['id' => 'filebrowser-table',],
    ];
  }

  public function iconView() {
    /**
     * @var \Drupal\Core\Image\Image $thumbnail
     * @var \Drupal\filebrowser\Grid\Grid  $grid_table
     * @var DisplayFile $data
     */
    $list_data = $this->dbFileList['data'];
    $grids = [];
    $this->sortIconViewFiles($this->dbFileList['files']);
    $height = $this->filebrowser->gridHeight;
    $width = $this->filebrowser->gridWidth;
    foreach ($this->dbFileList['files'] as $file_name => $data) {
      // Skip dot folder
      if ($file_name == ".") {
        continue;
      }

      // File extension case
      if ($file_name != ".." && $data->fileData->type == 'file' && $this->filebrowser->hideExtension) {
        $pos = strrpos($data->displayName, ".");
        $data->name = substr($data->displayName, 0, $pos);
      }
      // Check if we can create an image
      if (empty($data->fileData->uri) || (!\Drupal::service('image.factory')->get($data->fileData->uri)->isValid())) {
        // create thumbnail from icon file
        if ($data->fileData->type !== 'dir') {
          $this->messenger()->addMessage('can not create img for ' . $data->fileData->uri);
        }
        $thumbnail = $this->common->iconGenerate($data->fileData->type, $data->fileData->mimetype, $height, $width);
      }
      else {
        // create the styled thumbnail
        $thumbnail = [
          '#theme' => 'image_style',
          '#style_name' => $this->filebrowser->gridImageStyle,
          '#uri' => $data->fileData->uri,
          '#alt' => 'image style',
          '#title' => NULL,
          '#width:' => null,
          '#height:' => null,
          '#attributes' => [],
        ];
      }

      $download_link = null;

      $grids[] = [
        'grid' => [
          '#theme' => 'filebrowser_grid_item',
          '#data' => [
            'title' => $data->displayName == '..' ? $this->t('Go up') : $data->displayName,
            'type' => $data->fileData->type,
            'thumbnail' => $thumbnail,
            'download_link' => $download_link,
            'new' => [
              '#theme' => 'mark', '#type' => $data->status,
            ],
            'href' => $data->href,
            'hide_title' => $this->filebrowser->gridHideTitle,
          ],
        ],
        'file' => $data,
      ];
    }

    $options = [
      'alignment' => $this->filebrowser->gridAlignment,
      'columns' => $this->filebrowser->gridColumns,
      'automatic_width' => $this->filebrowser->gridAutoWidth,
    ];

    $grid_table = new Grid($grids, $options);
    $items['grid_items'] = $grid_table->get();
    $items['options'] = $options;

    $params = [
      'actions' => (!empty($this->formActions) ? $this->formActions : null),
      'node' => $this->node,
      'data' => $list_data,
    ];
    return \Drupal::formBuilder()->getForm('\Drupal\filebrowser\Form\GridActionForm', $items, $params);
  }

  function theme_dir_listing_statistics($statistics) {
    // $white_list is used to restrict indexes to filebrowser indexes.
    // Issue #2616738 indicates addition of 'index' to the array

    $output = "<div class='dir-listing-status'>";
    if ($statistics['empty']) {
      $output .= $statistics['empty'];
    }
    else {
      $white_list = ['files', 'total_size', 'empty', 'folders'];
      $white_listed_statistics = [];

      foreach ($statistics as $key => $statistic) {
        if (in_array($key, $white_list) && !empty($statistic)) {
          $white_listed_statistics[] = $statistic;
        }
      }

      $output .= implode(" - ", $white_listed_statistics);
    }
    $output .= "</div>";
    return $output;
  }

  private function createUnsortedRowsAndColumns(&$unsorted_rows, &$visible_columns, $column_names, &$directory_empty) {
    $hide_extension = $this->filebrowser->hideExtension;
    $selected_columns = $this->filebrowser->visibleColumns;
    $files = $this->dbFileList['files'];
    foreach ($files as $file_name => $file) {
      /** @var DisplayFile $file */

      // Skip dot folder
      if ($file_name == ".") {
        continue;
      }

      // At least one file
      $directory_empty = false;

      // File extension case
      if ($file_name != ".." && $file->fileData->type == 'file' && $hide_extension) {

        $pos = strrpos($file->displayName, ".");
        $data[Common::NAME] = substr($file->displayName, 0, $pos);
      }

      // if the list has actions we will add the file id for creation of the select boxes
      if (!empty($this->formActions)) {
        $unsorted_rows[$file_name]['fid'] = ($file->fileData->mimetype != 'folder/parent' ? "{$file->fid}" : '');
      }

      if (!empty($selected_columns[Common::ICON])) {
        // ##### ICON COLUMN #####
        $visible_columns[Common::ICON] = true;
        $icon = $this->common->iconGenerate($file->fileData->type, $file->fileData->mimetype, 24, 24);
        $unsorted_rows[$file_name][Common::ICON] = render($icon);
      }

      // ##### NAME COLUMN - we will always set the name column
      // fixme: delete not needed
      $unsorted_rows[$file_name][Common::NAME] = render($file->link);
      $visible_columns[Common::NAME] = true;

      // ##### SET OTHER METADATA
      // for each file we will loop trough the column names.
      // if it is selected ($selected_columns) and contains data we will
      // add them to the visible array in $unsorted_rows

      $query = \Drupal::entityQuery('filebrowser_metadata_entity')
        ->condition('fid', $file->fid);
      $ids = $query->execute();
      $metadata_all = \Drupal::entityTypeManager()->getStorage('filebrowser_metadata_entity')->loadMultiple($ids);
      /** @var FilebrowserMetadataEntity $metadata */

      foreach ($metadata_all as $metadata) {
        $name = $metadata->getName();
        if ($selected_columns[$name]) {
          $visible_columns[$name] = true;
          $content = unserialize($metadata->content->value);
          $theme = $metadata->theme->value;
           if (is_null($theme)) {
              $unsorted_rows[$file_name][$name] = $content;
          }
          else {
            $render = [];
            $render['#theme'] = $theme;
            $render['#data'] = $content;
            $unsorted_rows[$file_name][$name] = render($render);
          }
        }
      }
    }
  }

  private function buildHeader(&$header, &$unsorted_rows, $visible_columns, $column_names, $default_sort, $sort_order) {
    /**
     * @var \Drupal\Core\StringTranslation\TranslatableMarkup  $specs
     */

    foreach ($column_names as $column_name => $column_spec) {
      if (isset($visible_columns[$column_name]) && $visible_columns[$column_name]) {
        if ($column_name == Common::ICON) {
          $header[$column_name] = [];
        }
        else {
          if (!empty($column_spec['sortable'])) {
            $header[$column_name] = [
              'data' => $column_spec['title'],
              'field' => $column_name,
            ];
          }
          else {
            $header[$column_name] = $column_spec['title'];
          }

        }
      }
      else {
        // If unused data then clean up !
        if ($unsorted_rows) {
          foreach ($unsorted_rows as & $row) {
            unset($row[$column_name]);
          }
        }
      }
    }
    $header[$default_sort]['sort'] = $sort_order;

  }

  private function splitFiles($files) {
    // TODO : take data from $unsorted_rows and not $data
    $result = [];
    foreach ($files as $name => $data) {
      if ($name == '.') {
        continue;
      }
      elseif ($data->fileData->type == 'file') {
        $result['files'][] = $data;
      }
      else {
       // Do not retain the '.' folder
      $result['folders'][] = $data;
      }
    }
    return $result;
  }

  private function sortIconViewFiles(&$source) {
    /** @var DisplayFile $data */
    $files = [];
    $folders = [];
    foreach ($source as $name => $data) {
      if ($name != '.') {
        if ($data->fileData->type == 'file') {
          $files[$name] = $data;
        }
        else {
          $folders[$name] = $data;
        }
      }
    }
    $source = $folders + $files;
  }

  // fixme: this method should belong to FileDisplayList
//  public function generateDescription($data) {
//    if(!empty($this->dbFileList['data']['fid'])) {
//      //this is a subfolder
//      $p = ['nid' => $this->node->id(), 'query_fid' => $this->dbFileList['data']['fid'], 'fids' => $data['fid'],];
//    }
//    else {
//      $p = ['nid' => $this->node->id(), 'fids' => $data['fid'],];
//    }
//    $data['url'] = Url::fromRoute('filebrowser.inline_description_form', $p);
//    $data['attributes'] = [
//      'class' => ['use-ajax'],
//      'data-dialog-type' => 'modal',
//      'data-dialog-options' => Json::encode(['width' => 700]),
//    ];
//    $data['image_title'] = $this->t('Edit description');
//    $description = [
//      '#theme' => 'filebrowser_description',
//      '#data' => $data,
//    ];
//    return render($description);
//  }

  protected function retrieveMetadata($fid) {
    $storage = \Drupal::entityTypeManager()
      ->getStorage('filebrowser_metadata_entity');
    $query = \Drupal::entityQuery('filebrowser_metadata_entity');
    $e_ids = $query->condition('fid', $fid, '=')->execute();
    $entities = $storage->loadMultiple($e_ids);
    return $entities;
  }

}
