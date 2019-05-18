<?php
namespace Drupal\cloudwords;

/**
 * Represents a project's intended use use in Cloudwords. An intended use represents what
 * purpose a given project's content is intended for (e.g. Website, Product, Legal, etc.).
 *
 * @author Douglas Kim <doug@cloudwords.com>
 * @since 1.0
 */
class CloudwordsIntendedUse {

  protected $id;
  protected $name;

  /**
   * Constructor used to create a Cloudwords intended use
   *
   * - id: int The intended use id
   * - name: string The intended use name
   *
   * @param array $params The parameters used to initialize an intended use instance
   */
  public function __construct($params) {
    if (isset($params['id'])) {
      $this->id = $params['id'];
    }
    if (isset($params['name'])) {
      $this->name = $params['name'];
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

}
