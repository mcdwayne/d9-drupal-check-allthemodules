<?php
namespace Drupal\cloudwords;

/**
 * Represents a vendor resource in Cloudwords. A vendor is assigned to a Cloudwords
 * project in order to provide the necessary translation services.
 *
 * @author Douglas Kim <doug@cloudwords.com>
 * @since 1.0
 */
class CloudwordsVendor {

  protected $id;
  protected $name;
  protected $path;

  /**
   * Constructor used to create a Cloudwords vendor
   *
   * - id: int The vendor id
   * - name: string The vendor name
   * - path: string The api url to retrieve vendor metadata
   *
   * @param array $params The parameters used to initialize a vendor instance
   */
  public function __construct($params) {
    if (isset($params['id'])) {
      $this->id = $params['id'];
    }
    if (isset($params['name'])) {
      $this->name = $params['name'];
    }
    if (isset($params['path'])) {
      $this->path = $params['path'];
    }
  }

  public function getId() {
    return $this->id;
  }

  public function setId($id) {
    $this->id = $id;
  }

  public function getName() {
    return $this->name;
  }

  public function setName($name) {
    $this->name = $name;
  }

  public function getPath() {
    return $this->path;
  }

  public function setPath($path) {
    $this->path = $path;
  }

}
