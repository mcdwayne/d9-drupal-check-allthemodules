<?php

namespace Drupal\onehub;

use Drupal\onehub\OneHub;
use GuzzleHttp\Psr7\MultipartStream;
use Drupal\Component\Utility\NestedArray;
use Drupal\file\Entity\File;

/**
 * Class OneHubApi.
 *
 * @package Drupal\onehub
 */
class OneHubApi extends OneHub {

  /**
   * Array of items we are passing.
   *
   * @var array
   */
  protected $items;

  /**
   * Sets the typical headers needed for an api call.
   *
   * @return array
   *   the headers for the api call.
   */
  protected function setApiHeaders() {
    // Grab the access token.
    $token = parent::getToken('access');

    // Return the set headers for an API call.
    return [
      'headers' => [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $token,
      ],
    ];
  }

  /**
   * Mechanism to call the API.
   *
   * @param string $endpoint
   *   The endpoint url without the ONEHUB_BASE_URL.
   * @param string $type
   *   The type of request (ie GET, POST, etc).
   * @param array $params
   *   Additional paramaters to send.
   *
   * @return null|array
   *   The API response or NULL.
   */
  public function callApi($endpoint, $type = 'GET', array $params = []) {
    $options = !empty($params) ? $params : $this->setApiHeaders();
    parent::setUrl($this->baseUrl . $endpoint);
    parent::requestResponse($type, $options);

    // Exit if Empty Response.
    if (is_null($this->request)) {
      return NULL;
    }

    // Grab the Body and return as needed.
    $contents = json_decode($this->request->getBody()->getContents(), TRUE);
    return !empty($contents) ? $contents : NULL;
  }


  /**
   * Pings a simple api call to test if the token is valid.
   *
   * @return bool
   *   If we are able to ping the API or not.
   */
  public function checkToken() {
    $check = $this->callApi('/workspaces');
    return $check !== NULL ? TRUE : FALSE;
  }

  public function createWorkspace() {

  }

  /**
   * Get a Workspace's information.
   *
   * @param string $workspace_id
   *   The workspace id we are checking.
   *
   * @return array|NULL
   *   The Workspace data or NULL.
   */
  public function getWorkspace($workspace_id) {
    $path = '/workspaces/' . $workspace_id;
    $ws_call = $this->callApi($path);
    return isset($ws_call['workspace']) ? $ws_call['workspace'] : NULL;
  }

  public function updateWorkspace() {

  }

  public function deleteWorkspace() {

  }

  /**
   * List all workspaces
   *
   * @return array
   *   Any array of workspaces keyed by id.
   */
  public function listWorkspaces() {

    $ws_call = $this->callApi('/workspaces');
    $workspaces = [];

    foreach ($ws_call['items'] as $key => $ws) {
      foreach ($ws as $space) {
        $workspaces[$space['id']] = $space['name'];
      }
    }

    return $workspaces;
  }

  public function createFolder() {

  }

  /**
   * Get a Folder's information.
   *
   * @param string $workspace_id
   *   The workspace id we are checking for folders.
   * @param string $folder_id
   *   The folder id we are checking.
   *
   * @return array|NULL
   *   The folder data or NULL.
   */
  public function getFolder($workspace_id, $folder_id) {
    $path = '/workspaces/' . $workspace_id . '/folders/' . $folder_id;
    $f_call = $this->callApi($path);

    // Match up the folder.
    if (isset($f_call['folder'])) {
      if ($f_call['folder']['id'] == $folder_id) {
        $folder = $f_call['folder'];
      }
    }
    else {
      if (isset($f_call['items'])) {
        foreach ($f_call['items'] as $key => $items) {
          if ($items['folder']['id'] == $folder_id) {
            $folder = $items['folder'];
            break;
          }
        }
      }
    }

    return isset($folder) ? $folder : NULL;
  }

  public function updateFolder() {

  }

  public function deleteFolder() {

  }

  /**
   * List all the folders of a certain workspace.
   *
   * @param string $workspace_id
   *   The workspace id we are checking for folders.
   *
   * @return array
   *   Any array of folders keyed by id.
   */
  public function listFolders($workspace_id) {
    $path = '/workspaces/' . $workspace_id . '/folders';
    $f_call = $this->callApi($path);

    if ($f_call === NULL) {
      return [];
    }

    // Grab the id.
    $id = NestedArray::getValue($f_call, ['items', 0, 'folder', 'id']);

    // Call the API again to get the folders.
    $path = $path . '/' . $id;
    $fs_call = $this->callApi($path);

    // Grab the folders array.
    $this->items = [];
    $this->getItems($fs_call);

    return $this->items;
  }

  /**
   * Creates a file on OneHub.
   * @param  string $workspace_id
   *   The workspace we are uploading to.
   * @param  string $folder_id
   *   The folder we are uploading to.
   * @param  string $fid
   *   The file fid number.
   *
   * @return array
   *   The file object info from OneHub.
   */
  public function createFile($workspace_id, $folder_id, $fid) {
    // Set the boundary to the current time.
    $boundary = REQUEST_TIME;

    // File related loads.
    $file = File::load($fid);
    $file_path = $file->url();

    // Helps eliminate issues with self-signed certs.
    $opts = [
      'ssl' => [
        'verify_peer' => FALSE,
        'verify_peer_name' => FALSE,
      ],
    ];

    // Set up the upload file portion of this.
    $multipart = [
      [
        'name' => 'upload_file',
        'contents' => fopen($file_path, 'r', false, stream_context_create($opts)),
        'filename' => basename(urldecode($file_path)),
      ],
    ];

    // Set up the params to pass through to the API.
    $params = $this->setApiHeaders();
    $params['headers']['Content-Type'] = 'multipart/form-data; boundary='. $boundary;
    $params['headers']['Content-Length'] = $file->getSize();
    $params['body'] = new MultipartStream($multipart, $boundary);

    // Set up the endpoint and call the API.
    $path = '/workspaces/' . $workspace_id . '/folders/' . $folder_id . '/files';
    $send = $this->callApi($path, 'POST', $params);

    return $send;
  }

  /**
   * Downloads a file from OneHub.
   *
   * @param  string $filename
   *   The filename.
   * @param  string $fid
   *   The file fid number.
   *
   * @return file
   *   The file from OneHub.
   */
  public function getFile($filename, $fid) {
    // Set the path to grab the all the files.
    $path = '/download/' . $fid;

    // We are not using the API directly for a file download.
    // The logix is different than any other call we are making.
    $options = $this->setApiHeaders();
    parent::setUrl($this->baseUrl . $path);
    parent::requestResponse('GET', $options);

    // Exit if Empty Response.
    if (is_null($this->request)) {
      return NULL;
    }

    // This returns the file contents themselves.
    $body = $this->request->getBody()->getContents();

    // Write the file.
    $tmpfile = file_directory_temp() . '/' . $filename;
    $handle = fopen($tmpfile, "w");
    fwrite($handle, $body);
    fclose($handle);

    // Download the file.
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header('Content-Type: ' . mime_content_type($tmpfile));
    header('Content-Disposition: attachment; filename='. basename($tmpfile));
    header('Content-Length: ' . filesize($tmpfile));
    header("Content-Transfer-Encoding: binary");
    while (ob_get_level()) {
      ob_end_clean();
    }
    readfile($tmpfile);
    exit;
  }

  public function updateFile() {

  }

  public function deleteFile() {

  }

  /**
   * List all the files of a certain workspace / folder.
   *
   * @param string $workspace_id
   *   The workspace id we are checking for folders.
   * @param  string $folder_id
   *   The folder we are uploading to.
   *
   * @return array
   *   Any array of folders keyed by id.
   */
  public function listFiles($workspace_id, $folder_id) {
    // Set the path to grab the all the files.
    $path = '/workspaces/' . $workspace_id . '/folders/' . $folder_id;
    $f_call = $this->callApi($path);

    // Grab the files array.
    $this->items = [];
    $this->getItems($f_call);

    return $this->items;
  }

  /**
   * Utility function to grab items in a OneHub Call.
   *
   * @param array $call
   *   The called array of items.
   *
   * @return array
   *   The list of items in an array keyed id:filename.
   */
  private function getItems(array $call) {
    foreach ($call['items'] as $i) {
      // Skip over files.
      if (isset($i['file'])) {
        continue;
      }
      foreach ($i as $item) {
        // If this item has children, then grab those.
        if (isset($item['folders_count']) && $item['folders_count'] > 0) {
          $this->items[$item['id']] = $item['filename'];
          $path = '/workspaces/' . $item['workspace_id'] . '/folders/' . $item['id'];
          $folders = $this->callApi($path);
          $this->getItems($folders);
        }
        else {
          $this->items[$item['id']] = $item['filename'];
        }
      }
    }
  }
}
