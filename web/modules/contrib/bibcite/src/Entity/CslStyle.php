<?php

namespace Drupal\bibcite\Entity;

use Drupal\bibcite\Csl;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the CSL style entity.
 *
 * @ConfigEntityType(
 *   id = "bibcite_csl_style",
 *   label = @Translation("CSL style"),
 *   handlers = {
 *     "list_builder" = "Drupal\bibcite\CslStyleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\bibcite\Form\CslStyleForm",
 *       "add-file" = "Drupal\bibcite\Form\CslStyleFileForm",
 *       "edit" = "Drupal\bibcite\Form\CslStyleForm",
 *       "delete" = "Drupal\bibcite\Form\CslStyleDeleteForm"
 *     },
 *   },
 *   config_prefix = "bibcite_csl_style",
 *   admin_permission = "administer bibcite",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/bibcite/settings/csl_style/add",
 *     "add-form-file" = "/admin/config/bibcite/settings/csl_style/add-file",
 *     "edit-form" = "/admin/config/bibcite/settings/csl_style/{bibcite_csl_style}",
 *     "delete-form" = "/admin/config/bibcite/settings/csl_style/{bibcite_csl_style}/delete",
 *     "collection" = "/admin/config/bibcite/settings/csl_style"
 *   }
 * )
 */
class CslStyle extends ConfigEntityBase implements CslStyleInterface {

  /**
   * The CSL style ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The parent style ID.
   *
   * @var string
   */
  protected $parent = NULL;

  /**
   * The CSL style label.
   *
   * @var string
   */
  protected $label;

  /**
   * The text of CSL.
   *
   * @var string
   */
  protected $csl;

  /**
   * The time of latest update.
   *
   * @var int
   */
  protected $updated;

  /**
   * Indicated that style installed by user from text or file.
   *
   * @var bool
   */
  protected $custom = TRUE;

  /**
   * The URL of the style used as identifier in CSL ecosystem.
   *
   * @var string
   */
  protected $url_id;

  /**
   * {@inheritdoc}
   */
  public function getCslText() {
    return $this->csl;
  }

  /**
   * {@inheritdoc}
   */
  public function setCslText($csl_text) {
    $this->csl = $csl_text;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdatedTime() {
    return $this->updated;
  }

  /**
   * {@inheritdoc}
   */
  public function setUpdatedTime($timestamp) {
    $this->updated = $timestamp;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateHash() {
    $xml = simplexml_load_string($this->csl);
    return hash('sha256', $xml->asXML());
  }

  /**
   * {@inheritdoc}
   */
  public function isCustom() {
    return $this->custom;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustom($custom) {
    $this->custom = (bool) $custom;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasParent() {
    return !empty($this->parent);
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    return $this->parent ? static::load($this->parent) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setParent($parent = NULL) {
    $this->parent = ($parent instanceof CslStyleInterface)
      ? $this->parent = $parent->id()
      : $this->parent = $parent;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlId() {
    return $this->url_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setUrlId($url_id) {
    $this->url_id = $url_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    $csl = new Csl($this->csl);

    if ($url_id = $csl->getId()) {
      $this->setUrlId($url_id);
    }

    if ($parent_id = $csl->getParent()) {
      $storage = $this->entityTypeManager()->getStorage($this->getEntityTypeId());

      $result = $storage->getQuery()
        ->condition('url_id', $parent_id)
        ->execute();

      if (!$result) {
        throw new \Exception('You can not save style without installed parent.');
      }

      $parent_internal_id = reset($result);
      $this->setParent($parent_internal_id);
    }
  }

}
