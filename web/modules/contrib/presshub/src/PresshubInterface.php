<?php

namespace Drupal\presshub;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Presshub templates.
 */
interface PresshubInterface extends PluginInspectionInterface {

  /**
   * Return Presshub template name.
   *
   * @return string
   */
  public function getName();

  /**
   * Entity types supported by the template.
   *
   * @return array
   */
  public function getEntityTypes();

  /**
   * Presshub publishable.
   *
   * @return boolean
   */
  public function isPublishable($entity);

  /**
   * Presshub Preview.
   *
   * @return boolean
   */
  public function isPreview($entity);

  /**
   * Presshub service parameters.
   *
   * @return array
   */
  public function setServiceParams($entity);

  /**
   * Build Presshub template.
   *
   * @return ChapterThree\AppleNewsAPI\Document
   */
  public function template($entity);

}
