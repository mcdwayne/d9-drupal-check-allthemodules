<?php

namespace Drupal\dam\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\dam\Entity\FileDirectory;
use Drupal\Core\Form\ConfigFormBase;

class DamController extends ControllerBase {

  /**
  * Callback for assets display.
  */
  public function assets() {


    $config = \Drupal::config('dam.ftp_settings');
    FileDirectory::updateDirectory( $config->get('dam_root_folder') );
    $path = $config->get('dam_root_folder');
    // Create the links for switching displays from file or collection view, and links for settings and collapse.
    $control_menu = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => []
    ];

    $action_menu = [];
    $display_menu = [];
    $tree = FileDirectory::getTree( $path, $path, TRUE  );
    return [
      '#cache' => ['max-age' => 0,],  
      '#attached' => [
        'library' =>[
          'dam/global_style',
          'dam/assets_page'
        ],
        'drupalSettings' => [
          'dam' =>  [
            'folders' => FileDirectory::getTree( $path, $path, FALSE  ),
            'tree' => $tree,
            'activeTree' => $tree
            ]
          ]
      ],
      'dam_wrapper' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'dam-wrapper'
        ],
        'content' => [
          'dam_view_mode' => [
            '#type' => 'container',
            '#attributes' => [
              'id' => 'assets-mode-select'
            ],
            'modes' => [
              '#theme' => 'item_list',
              '#items' => [
                  [
                    '#wrapper_attributes' => [
                      'class' => [ 'asset-mode-icons', 'asset-mode', 'active' ]
                    ],
                    '#markup' => '<a href="#thumbnail"><span class="glyphicon glyphicon-th"></span></a>'
                  ],
                  [
                    '#wrapper_attributes' => [
                      'class' => [ 'asset-mode-list', 'asset-mode' ]
                    ],
                    '#markup' => '<a href="#list"><span class="glyphicon glyphicon-list"></span></a>'
                  ],
              ]
            ]
          ],
          'dam_assets_explorer' => [
            '#type' => 'container',
            '#attributes' => [
              'id' => 'assets-explorer'
            ],
          ],
          'dam_asssets_viewer' => [
            '#type' => 'container',
            '#attributes' => [
              'id' => 'assets-viewer',
              'title' => t('Select a folder to view files.'),
              // 'class' => ['thumbnail']
            ],
            'content' => [
              '#markup' => t('')
            ]
          ],
          'dam_preview_view_mode' => [
            '#type' => 'container',
            '#attributes' => [
              'id' => 'assets-preview-view-mode',
            ],
            'modes' => [
              '#theme' => 'item_list',
              '#items' => [
                [ '#markup' => '<a href="#collapse"><span class="glyphicon glyphicon-forward"></span></a>' ],
                [ '#markup' => '<a href="#folder"><span class="glyphicon glyphicon-info-sign"></span></a>' ],
                [ '#markup' => '<a href="#assets"><span class="glyphicon glyphicon-comment"></span></a>' ]
              ]
            ]
          ],
          'dam_assets_info_wrapper' => [
            '#type' => 'container',
            '#attributes' => [
              'id' => 'assets-info-wrapper'
            ],
            'content' => [
              'dam_assets_preview' => [
                '#type' => 'container',
                '#attributes' => [
                  'id' => 'dam-assets-preview'
                ],
                'content' => [
                  '#markup' => '<h4><a href="#" class="collapse-preview"><span class="glyphicon glyphicon-triangle-bottom">' . t('Preview') . '</a></h4>' .
                                  '<div class="container preview-pane"><div id="preview-thumbnail"></div>' .
                                    '<div class="preview-details">' .
                                      '<a href="#dam-assets-info">Info</a>' .
                                      '<a href="#dam-assets-share">Download</a>' .
                                    '</div>' .
                                  '</div>',
                ]
              ],
              'dam_assets_info' => [
                '#type' => 'container',
                '#attributes' => [
                  'id' => 'dam-assets-info',
                ],
                'content' => [
                  '#markup' => '<div id="info" class="container"></div>',
                ]
              ],
              'dam_assets_share' => [
                '#type' => 'container',
                '#attributes' => [
                  'id' => 'dam-assets-share'
                ],
                'content' => [
                  '#markup' => '<div id="download" class="container"></div>',
                ]
              ],
              'dam_assets_comments' => [
                '#type' => 'container',
                '#attributes' => [
                  'id' => 'dam-assets-comments'
                ],
                'content' => [
                  '#markup' => '<div id="comments" class="container"></div>',
                ]
              ],
            ]
          ]
        ]
      ],
    ];
  }


  /**
  * Callback for rendering the collection display
  */
  public function collections() {

    return [
      '#markup' => 'collection'
    ];
  }


  /**
  * Returns the FTP log.
  */
  public function log() {
    $config = $this->config('dam.ftp_settings');
    $logFilePath = $config->get('log');
    $header = [
      'sno.' => $this->t('SNo'),
      'log' => $this->t('Log'),
    ];
    $rows = [];
    if (!empty($logFilePath)) {
      $file = file_get_contents($logFilePath);
      if (!empty($file)) {
        $file = explode("\n", $file);
        foreach ($file as $key => $value) {
          $rows[$key] = [
            'sno' => $key + 1,
            'log' => $value,
          ];
        }
      }
    }

    if (count($rows) > 0) {
      $build = [
        '#markup' => $this->t('<a class="button" href="@adminlink">Download FTP logs</a>', array(
          '@adminlink' => \Drupal::urlGenerator()
            ->generateFromRoute('dam.ftp_log_zip'),
        )),
      ];
    }

    $build['log_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No logs found'),
    ];
    // For pagination
    $build['pager'] = array(
        '#type' => 'pager'
    );

    return $build;
  }

  /**
  * Centralizes the operation of moving files from one directory do another.
  * Additionally, updates all of the related entities.
  */
  public static function moveFiles($old_directory, $new_directory) { }

  /**
  * Centralizes the operation that updates the DAM system based on the physical files.
  */
  public static function refreshSystem() { }

  /**
  * Callback to render the folders for the jqueryFileTree plugin.
  * - @TODO: this is just a placeholder function.
  * Parameters:
  * - dir
  * - multiSelect
  * - onlyFolders
  * - onlyFiles
  * -
  */
  public function filetree(Request $request) {
    $path = $request->query->get('path');
    $data = dam_generate_file_tree($path);
    $response = new Response();
    $response->setContent($data);
    //$response->headers->set('Content-Type', 'text/xml');
    return $response;
  }


  /**
   * Create FTP log file Zip.
   */
  public function createFTPLogZip() {
    if (!class_exists('ZipArchive')) {
      throw new \Exception('Requires the "zip" PHP extension to be installed and enabled in order to create ZipArchive of FTP logs.');
      return FALSE;
    }

    $config = $this->config('dam.ftp_settings');
    $logFilePath = $config->get('log');
    $fileName = pathinfo($logFilePath)['basename'];

    // Get real path of file.
    $rootPath = \Drupal::service('file_system')->realpath($logFilePath);

    $filesPath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $logFile = $filesPath . '/ftplog' . '.zip';
    $zipFileName = 'ftplog.zip';
    // Initialize archive object.
    $zip = new \ZipArchive();
    // Create the file and throw the error if unsuccessful
    if ($zip->open($logFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)!==TRUE) {
        exit("cannot open <$logFile>\n");
    }
    $zip->addFile($rootPath, $fileName);
    $zip->close();
    // Send the headers to force download the zip file
    header("Content-type: application/zip");
    header("Content-Disposition: attachment; filename=$zipFileName");
    header("Pragma: no-cache");
    header("Expires: 0");
    readfile("$logFile");
    exit;
  }

  /**
   * Returns Server disk space.
   */
  public function damServerSpace() {
    $dsTotal = disk_total_space("/");
    $dsTotalsBytes = $this->dataSize($dsTotal);
    $dsFree = disk_free_space("/");
    $dsFreeBytes = $this->dataSize($dsFree);
    $dsUsed = $dsTotal - $dsFree;
    $dsUsedBytes = $this->dataSize($dsUsed);
    return [
      '#markup' => $this->t('Total Disk Space: @ds <br/> Remaining Space: @dsfree <br/> Used Space: @dsused', ['@ds' => $dsTotalsBytes, '@dsfree' => $dsFreeBytes, '@dsused' => $dsUsedBytes]),
    ];
  }

  /**
   * Returns the size in readable format.
   */
  public function dataSize($bytes) {
    $Type=array("", "kilo", "mega", "giga", "tera");
    $counter=0;
    while($bytes>=1024)
    {
      $bytes/=1024;
      $counter++;
    }
    return("" . $bytes . " ". $Type[$counter] . "bytes");
  }

  /**
   * Get all junk files.
   */
  public function getDamJunkFiles() {
    $query = \Drupal::database()->select('file_managed', 'fm')
      ->fields('fm', ['fid', 'uri', 'filename']);
    $query->condition('fm.junk', 1, '=');
    $result = $query->execute()->fetchAll();

    $header = [
      'fid' => 'File ID',
      'filename' => 'File Name',
      'file' => $this->t('File'),
    ];
    $rows = [];
    foreach ($result as $key => $value) {
      $rows[$key] = [
        'fid' => $value->fid,
        'filename' => $value->filename,
        'file' => \Drupal::service('file_system')->realpath($value->uri),
      ];
    }
    $build['junk_files'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No files found'),
    ];
    // For pagination
    $build['pager'] = array(
        '#type' => 'pager'
    );

    return $build;
  }
}
