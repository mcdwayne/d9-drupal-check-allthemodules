<?php
namespace Drupal\cloudwords;

/**
 * Represents the metadata around a file stored in Cloudwords. The type of file could be the source
 * content for a project, the reference material for a project, etc.
 *
 * @author Douglas Kim <doug@cloudwords.com>
 * @since 1.0
 */
class CloudwordsFile {

  protected $id;
  protected $filename;
  protected $lang;
  protected $contentPath;
  protected $sizeInKilobytes;
  protected $fileContents;
  protected $createdDate;
  protected $path;

  /**
   * Constructor used to create a Cloudwords file resource
   *
   * - id: int The file resource id
   * - filename: string The filename of the file resource
   * - lang: array The language of the file resource that contains a display name and language code
   * - contentPath: string The content path to the file resource
   * - sizeInKilobytes: int The file resource size in kilobytes
   * - fileContents: string The file contents containing within the file resource
   * - createdDate: string The file resource created date
   * - path: string The api url to retrieve file resource metadata
   *
   * @param array $params
   *   The parameters used to initialize a project instance
   */
  public function __construct($params) {
    if (isset($params['id'])) {
      $this->id = $params['id'];
    }
    if (isset($params['filename'])) {
      $this->filename = $params['filename'];
    }
    if (isset($params['lang'])) {
      $this->lang = $this->transformLanguage($params['lang']);
    }
    if (isset($params['contentPath'])) {
      $this->contentPath = $params['contentPath'];
    }
    if (isset($params['sizeInKilobytes'])) {
      $this->sizeInKilobytes = $params['sizeInKilobytes'];
    }
    if (isset($params['fileContents'])) {
      $this->fileContents = $params['fileContents'];
    }
    if (isset($params['createdDate'])) {
      $this->createdDate = $params['createdDate'];
    }
    if (isset($params['path'])) {
      $this->path = $params['path'];
    }
    if (isset($params['status'])) {
      $this->status = new CloudwordsProjectStatus($params['status']);
    }
  }

  public function getId() {
    return $this->id;
  }

  public function setId($id) {
    $this->id = $id;
  }

  public function getFilename() {
    return $this->filename;
  }

  public function setFilename($filename) {
    $this->filename = $filename;
  }

  public function getLang() {
    return $this->lang;
  }

  public function setLang(CloudwordsLanguage $lang) {
    $this->lang = $lang;
  }

  public function getContentPath() {
    return $this->contentPath;
  }

  public function setContentPath($contentPath) {
    $this->contentPath = $contentPath;
  }

  public function getSizeInKilobytes() {
    return $this->sizeInKilobytes;
  }

  public function setSizeInKilobytes($sizeInKilobytes) {
    $this->sizeInKilobytes = $sizeInKilobytes;
  }

  public function getFileContents() {
    return $this->fileContents;
  }

  public function setFileContents($fileContents) {
    $this->fileContents = $fileContents;
  }

  public function getCreatedDate() {
    return $this->createdDate;
  }

  public function setCreatedDate($createdDate) {
    $this->createdDate = $createdDate;
  }

  public function getPath() {
    return $this->path;
  }

  public function setPath($path) {
    $this->path = $path;
  }

  protected function transformLanguage($language) {
    $params = ['display' => $language['display'],
                    'languageCode' => $language['languageCode']];
    return new CloudwordsLanguage($params);
  }

  public function getStatus() {
    return $this->status;
  }

  public function setStatus(CloudwordsProjectStatus $status) {
    $this->status = $status;
  }

}
