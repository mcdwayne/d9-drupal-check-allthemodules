<?php

namespace Drupal\filebrowser\Services;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Class Common
 * @package Drupal\filebrowser\Services
 */
class Common extends ControllerBase{

  // Column identifiers definition.
  const ICON = 'icon';
  const NAME = 'name';
  const SIZE = 'size';
  const CREATED = 'created';
  const MIME_TYPE = 'mimetype';
  const DESCRIPTION = 'description';

  // Permissions
  const CREATE_LISTING = 'create listings';
  const DELETE_OWN_LISTINGS = 'delete listings';
  const DELETE_ANY_LISTINGS = 'delete any listings';
  const EDIT_OWN_LISTINGS = 'edit own listings';
  const EDIT_ANY_LISTINGS = 'edit any listings';
  const EDIT_DESCRIPTION = 'edit description';
  const VIEW_LISTINGS = 'view listings';
  const FILE_UPLOAD = 'upload files';
  const CREATE_FOLDER = 'create folders';
  const DOWNLOAD_ARCHIVE = 'download archive';
  const DOWNLOAD = 'download files';
  const DELETE_FILES = 'delete files';
  const RENAME_FILES = 'rename files';

  // id of the container that holds the grid of icon view
  const FILEBROWSER_GRID_CONTAINER_CLASS = 'filebrowser-grid-container';
  const FILEBROWSER_GRID_ROW_CLASS = 'filebrowser-grid-row';
  const FILEBROWSER_GRID_ITEM_CLASS = 'filebrowser-grid-item';
  const FILEBROWSER_GRID_CONTAINER_COLUMN_CLASS = 'filebrowser-grid-container-column';

  protected $storage;

  /**
   * Common constructor.
   * @param \Drupal\filebrowser\Services\FilebrowserStorage $storage
   */
  public function __construct(FilebrowserStorage $storage) {
    $this->storage = $storage;
  }

  /**
   * @return array
   */
  // todo: 'theme' is hard-coded?
  public function getFolderViewOptions() {
    return [
      'list-view' => [
        'title' => $this->t('Table - List of files in a table'),
        'theme' => 'dir_listing_list_view'
      ],
      'icon-view' => [
        'title' => $this->t('Grid - Thumbnail (or icon) of the files in a grid'),
        'theme' => 'dir_listing_icon_view',
      ],
    ];
  }

  public function getDownloadManagerOptions() {
    return [
      'private' => [
        'title' => $this->t('Private - Files are served by PHP/Drupal'),
      ],
      'public' => [
        'title' => $this->t('Public - Files are served by the web server and should be accessible by it'),
      ],
    ];
  }

  /**
   * Converts array of properties for use as checkboxes
   *
   * @param $properties
   * @return array
   */
  public function toCheckboxes($properties) {
    $result = [];
    if ($properties) {
      foreach ($properties as $key => $arr) {
        if ($arr) {
          if (isset($arr['title'])) {
            $result[$key] = $arr['title'];
          }
          else {
            $result[$key] = $key;
          }
        }
      }
    }
    return $result;
  }

  /**
   * Check if user can download ZIP archives.
   * @param NodeInterface $node Node containing the filebrowser
   * @return bool
   */
  function canDownloadArchive(NodeInterface $node) {
    $download_archive = $node->filebrowser->downloadArchive;
    return ($node->access('view') && $download_archive && \Drupal::currentUser()
        ->hasPermission(Common::DOWNLOAD_ARCHIVE));
  }

  /**
   * You can override the icons used by providing your own in
   * theme/active_theme/filebrowser
   *
   * Create a thumbnail and the associated XHTML code for a specific file.
   *
   * @param string $file_type. directory, file.
   * @param string $file_mimetype. File mimetype.
   * @param int $height
   * @param int $width
   * @param boolean $return_image. True if you want the function to return
   * a themed image. If false the function will return only the uri of the .svg file. This is mostly
   * for use in the icon_view display where we need to scale the thumbnails to match the image (grid) dimensions.
   * @return mixed array|string
   */
  public function iconGenerate($file_type, $file_mimetype, $height, $width, $return_image = true) {
    // todo: We can delete the png logic because we use svg
    // todo: abstract this function to be independent of supplied array

    $ext = '.svg';

    $mime_type = $this->mimeIcon($file_mimetype);
    $main_type = dirname($file_type);

    if ($file_type == 'dir' && $mime_type != 'folder-parent') {
      $mime_type = 'directory';
    }
    $theme_path = \Drupal::theme()->getActiveTheme()->getPath() . "/filebrowser/icons/";

    $icons = [
      // search first in active theme
      $theme_path . $mime_type . $ext,
      $theme_path . $main_type . $ext,
      // use default filebrowser icons
      'filebrowser://icons/' . $mime_type . $ext,
      'filebrowser://icons/'  . $main_type . $ext,
    ];

    $eligible = 'filebrowser://icons/' . 'unknown' . $ext;
    foreach ($icons as $icon) {
      if (file_exists($icon)) {
        $eligible = $icon;
        break;
      }
    }

    // todo:
    // We are adding the CSS classes to Twig using variable data.class
    // The normal way, using #attributes is not working: investigate & correct
    if ($return_image) {
      $markup = file_get_contents($eligible, \FILE_TEXT);
      return  [
        '#theme' => 'filebrowser_icon_svg',
        '#html' => $markup,
        '#data' => [
          'height' => $height,
          'width' => $width,
          'class' => ['filebrowser-svg', $mime_type . '-icon'],
        ],
        '#test' => 'dir is een test',
      ];
    }
    else {
      return $eligible;
    }

  }

  /**
   * Check if user can explore sub-folders.
   * @param NodeInterface $node
   */
  function canExploreSubFolders(NodeInterface $node) {
    return $node->filebrowser->exploreSubdirs;
  }

  /**
   * Load a specific node content.
   *
   * @param int $fid content fid
   * @return mixed record
   */
  function nodeContentLoad($fid) {
    // todo: combine with nodeContentLoadMultiple
    static $contents = [];
    if (isset($contents[$fid])) {
      return $contents[$fid];
    }

    $contents[$fid] = $this->storage->loadRecord($fid);
    if ($contents[$fid]) {
      return $contents[$fid];
    }
    else {
      return FALSE;
    }
  }

  /**
   * @param $fids array
   * @return mixed
   */
  function nodeContentLoadMultiple(array $fids) {
    $files = $this->storage->nodeContentLoadMultiple($fids);
    return $files;
}

  /**
   * remove content from filebrowser DB when deleting $node.
   *
   * @param int $nid Node id of node being deleted.
   */
  public function nodeDelete($nid) {
    $this->storage->deleteContent($nid);
    $this->storage->deleteNode($nid);
  }

  /**
   * UTF8 bullet-proof basename replacement.
   * @param string $path
   * @return string
   */
  function safeBasename($path) {
    $path = rtrim($path, '/');
    $path = explode('/', $path);
    return end($path);
  }

  /**
   * UTF8 bullet-proof directory name replacement.
   * @param string $path
   * @return string
   */
  function safeDirname($path) {
    $path = rtrim($path, '/');
    $path = explode('/', $path);
    array_pop($path);
    $result = implode("/", $path);
    if ($result == '') {
      return '/';
    }
    return $result;
  }

  /**
   * @func
   * Helper function to create the parameters when calling a route within filebrowser
   * in case of a sub directory $fid is query_fid (node/18?fid=xx) to return to.
   * @var int $nid
   * @var int $query_fid
   * @return string
   */
  public function routeParam($nid, $query_fid = NULL) {
    $p = empty($query_fid) ? ['nid' => $nid, 'query_fid' => 0]
      : ['nid' => $nid, 'query_fid' => $query_fid];
    return $p;
  }

  /**
   * Helper function to create the route to redirect a form to after submission
   * @param $query_fid
   * @param $nid
   * @return mixed
   */
  public function redirectRoute($query_fid, $nid) {
    $route['name'] = 'entity.node.canonical';
    $route['node'] = ['node' => $nid];
    $route['query'] = !empty($query_fid) ? ['query' => ['fid' => $query_fid]] : [];
    return $route;
  }

  /**
   * Returns an array containing the allowed actions for logged in user.
   * Array is used to complete building the form ActionForm.php
   * @param $node
   * @var array $actions
   * array with the following keys:
   * 'operation': the form action id that this element will trigger
   * 'title': title for the form element
   * 'type': 'link' will create a link that opens in a slide-down window
   *         'button' will create a button that opens in a slide-down window
   *         'default' creates a normal submit button
   * 'needs_item': this element needs items selected on the form
   * @return array
   */
  public function userAllowedActions($node) {
    /** @var \Drupal\filebrowser\Filebrowser $filebrowser */
    $actions = [];
    $account = \Drupal::currentUser();
    $filebrowser = $node->filebrowser;

    // needs_item indicates this button needs items selected on the form
    // Upload button
    if ($filebrowser->enabled && $account->hasPermission(Common::FILE_UPLOAD)) {
      $actions[] = [
        'operation' => 'upload',
        'title' =>$this->t('Upload'),
        'type' => 'link',
        'needs_item' => FALSE,
        'route' => 'filebrowser.action',
      ];
    }
    //Create folder
    if ($filebrowser->createFolders && $account->hasPermission(Common::CREATE_FOLDER)) {
      $actions[] = [
        'operation' => 'folder',
        'title' =>$this->t('Add folder'),
        'needs_item' => FALSE,
        'type' => 'link',
      ];
    }
    // Delete items button
    if ($account->hasPermission(Common::DELETE_FILES)) {
      $actions[] = [
        'operation' => 'delete',
        'title' => $this->t('Delete'),
        'needs_item' => TRUE,
        'type' => 'button',
      ];
    }
    // Rename items button
    if ($filebrowser->enabled && $account->hasPermission(Common::RENAME_FILES)) {
      $actions[] = [
        'operation' => 'rename',
        'title' => $this->t('Rename items'),
        'needs_item' => TRUE,
        'type' => 'button',
      ];
    }
    // Edit description button
    if ($filebrowser->enabled && $account->hasPermission(Common::EDIT_DESCRIPTION)) {
      $actions[] = [
        'operation' => 'description',
        'title' => $this->t('Edit description'),
        'needs_item' => TRUE,
        'type' => 'button',
      ];
    }
    if ($this->canDownloadArchive($node) && function_exists('zip_open')) {
      $actions[] = [
        'operation' => 'archive',
        'title' => $this->t('Download archive'),
        'needs_item' => TRUE,
        'type' => 'default',
      ];
    }
    return $actions;
  }

  public function closeButtonMarkup() {
    return [
      '#markup' => Link::fromTextAndUrl($this->t('Close'), Url::fromUserInput('#', [
        'attributes' => [
          'class' => [
            'filebrowser-close-window-link',
          ],
        ],
      ]))->toString(),
    ];
  }

  /**
   * Checks if uri is public or private
   * @param string $uri file uri to check
   * @return bool
   */
  public function isLocal($uri) {
    $scheme = \Drupal::service('file_system')->uriScheme($uri);
    return ($scheme == 'public' || $scheme == 'private');
  }

  /**
   * Gets the path of a parent folder.
   * @param integer $fid Id of the folder to look-up
   * @return string
   */
  public function relativePath($fid) {
    return $this->storage->loadRecord($fid)['path'];
  }

  /**
   * Returns a string containing the mime type of the file. This will be used
   * to identify the file icon. Returns 'unknown' for mime types that have no icon.
   * @param string $mime_type
   * @return string
   */
  private function mimeIcon($mime_type) {
    if ($mime_type == 'folder/parent') {
      return 'folder-parent';
    }

    $parts = explode('/', $mime_type);
    switch ($parts[0]) {
      case 'video':
      case 'image':
      case 'audio':
      case 'text':
       $mime = $parts[0];
       break;

      case 'application':
        $mime = $this->applicationMimeIcon($parts[1]);
        break;

      default:
        $mime = 'unknown';
    }
    return $mime;
  }

  /**
   * Helper function to located the mime type in the application part of the
   * mimetype
   * @param string $application_mime
   * @return string
   */
  private function applicationMimeIcon($application_mime) {
    $parts = explode('.', $application_mime);
    switch ($parts[0]) {
      case 'pdf':
      case 'xml':
      //case 'zip':
      case 'msword':
      case 'xhtml+xml':
        $mime = $parts[0];
        break;
      case 'vnd':
        $mime = $this->vndMimeIcon($parts[1]);
        break;
      default:
        $mime = 'unknown';
    }
    return $mime;
  }

  /**
   * Helper function to located the mime type in the vnd part of the
   * mimetype
   * @param string $vnd_mime
   * @return string
   */
  private function vndMimeIcon($vnd_mime) {
    $parts = explode('.', $vnd_mime);
    switch ($parts[0]) {
      case 'ms-excel':
      case 'ms-powerpoint':
      case 'ms-word':
      // case 'openxmlformats-officedocument':
        $mime = $parts[0];
        break;
      case 'vnd':
        $mime = $this->vndMimeIcon($parts[1]);
        break;
      default:
        $mime = 'unknown';
    }
    return $mime;
  }

  /**
   * Returns node object from path (if any), or NULL.
   *
   * @param RouteMatchInterface $route_match
   * @return Node|Null
   */
  public function getNodeFromPath($route_match = NULL) {
    $route_match = $route_match ?: \Drupal::routeMatch();
    if ($node = $route_match->getParameter('node')) {
      if (!is_object($node)) {
        // The parameter is node ID.
        $node = Node::load($node);
      }
      return $node;
    }
    return NULL;
  }

}
