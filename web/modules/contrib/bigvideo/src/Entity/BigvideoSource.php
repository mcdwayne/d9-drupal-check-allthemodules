<?php

namespace Drupal\bigvideo\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\file\Entity\File;

/**
 * Defines the BigVideo Source entity.
 *
 * @ConfigEntityType(
 *   id = "bigvideo_source",
 *   label = @Translation("BigVideo Source"),
 *   handlers = {
 *     "storage" = "Drupal\bigvideo\Entity\BigvideoSourceStorage",
 *     "list_builder" = "Drupal\bigvideo\BigvideoSourceListBuilder",
 *     "form" = {
 *       "add" = "Drupal\bigvideo\Form\BigvideoSourceForm",
 *       "edit" = "Drupal\bigvideo\Form\BigvideoSourceForm",
 *       "delete" = "Drupal\bigvideo\Form\BigvideoSourceDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\bigvideo\BigvideoSourceHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "bigvideo_source",
 *   admin_permission = "administer bigvideo",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/user-interface/bigvideo/sources/{bigvideo_source}",
 *     "add-form" = "/admin/config/user-interface/bigvideo/sources/add",
 *     "edit-form" = "/admin/config/user-interface/bigvideo/sources/{bigvideo_source}/edit",
 *     "delete-form" = "/admin/config/user-interface/bigvideo/sources/{bigvideo_source}/delete",
 *     "collection" = "/admin/config/user-interface/bigvideo/source"
 *   }
 * )
 */
class BigvideoSource extends ConfigEntityBase implements BigvideoSourceInterface {
  /**
   * The BigVideo Source ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The BigVideo Source label.
   *
   * @var string
   */
  protected $label;

  protected $type = self::TYPE_FILE;

  protected $mp4;

  protected $webm;

  public function getType() {
    return $this->type;
  }

  public function setMp4($mp4) {
    $this->mp4 = $mp4;
    return $this;
  }

  public function setWebM($webm) {
    $this->webm = $webm;
    return $this;
  }

  public function getMp4() {
    return $this->mp4;
  }

  public function getWebM() {
    return $this->webm;
  }

  public function createVideoLinks() {
    $links = [];

    $mp4 = $this->getMp4();
    $webm = $this->getWebM();

    if ($this->getType() == static::TYPE_FILE) {
      if (intval($mp4) && $mp4_file = File::load($mp4)) {
        $links['mp4'] = file_create_url($mp4_file->getFileUri());
      }
      if (intval($webm) &&  $webm_file = File::load($webm)) {
        $links['webm'] = file_create_url($webm_file->getFileUri());
      }
    }
    else {
      $links['mp4'] = $mp4;
      $links['webm'] = $webm;
    }

    return $links;
  }

}
