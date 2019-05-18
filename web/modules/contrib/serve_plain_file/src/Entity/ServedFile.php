<?php

namespace Drupal\serve_plain_file\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines the Served File entity.
 *
 * @ConfigEntityType(
 *   id = "served_file",
 *   label = @Translation("File"),
 *   handlers = {
 *     "list_builder" = "Drupal\serve_plain_file\Controller\ServedFileListBuilder",
 *     "form" = {
 *       "add" = "Drupal\serve_plain_file\Form\ServedFileForm",
 *       "edit" = "Drupal\serve_plain_file\Form\ServedFileForm",
 *       "delete" = "Drupal\serve_plain_file\Form\ServedFileDeleteForm",
 *     }
 *   },
 *   config_prefix = "served_file",
 *   admin_permission = "administer serve plain file",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/serve_plain_file/{served_file}",
 *     "delete-form" = "/admin/config/system/serve_plain_file/{served_file}/delete",
 *   }
 * )
 */
class ServedFile extends ConfigEntityBase implements ServedFileInterface {

  /**
   * Default mimetype sent in Content-type header.
   */
  const DEFAULT_MIME_TYPE = 'text/plain';

  /**
   * The entity ID.
   *
   * @var string
   */
  public $id;

  /**
   * The entity label.
   *
   * @var string
   */
  public $label;

  /**
   * The file path.
   *
   * @var string
   */
  public $path;

  /**
   * The file content.
   *
   * @var string
   */
  public $content;

  /**
   * Cache max age in seconds.
   *
   * @var int
   */
  public $max_age;

  /**
   * Mime type of the served file.
   *
   * @var string
   */
  public $mime_type;

  /**
   * {@inheritdoc}
   */
  public function getLinkToFile() {
    $options = ['attributes' => ['target' => '_blank']];
    $url = Url::fromUri('base:' . $this->path, $options);
    $url->setAbsolute();

    return Link::fromTextAndUrl($url->toString(), $url);
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function getContent() {
    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentHead() {
    $rows = explode("\n", $this->content);
    return substr(reset($rows), 0, 20) . " ... ";
  }

  /**
   * {@inheritdoc}
   */
  public function getFileMaxAge() {
    return (int) $this->max_age;
  }

  /**
   * {@inheritdoc}
   */
  public function getMimeType() {
    return $this->mime_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlsForCachePurging() {
    $paths = [];
    $urls = [];

    if (!empty($this->path)) {
      $paths[] = $this->path;
    }

    if (!empty($this->original->path) && $this->original->path != $this->path) {
      $paths[] = $this->original->path;
    }

    foreach ($paths as $path) {
      $url = Url::fromUri('base:' . $path);
      $url->setAbsolute();
      $urls[] = $url->toString();
    }

    return $urls;
  }

}
