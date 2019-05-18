<?php

namespace Drupal\academic_applications\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the workflow entity.
 *
 * @ConfigEntityType(
 *   id = "academic_applications_workflow",
 *   label = @Translation("Academic applications workflows"),
 *   handlers = {
 *     "list_builder" = "Drupal\academic_applications\WorkflowListBuilder",
 *     "form" = {
 *       "add" = "Drupal\academic_applications\Form\WorkflowForm",
 *       "edit" = "Drupal\academic_applications\Form\WorkflowForm",
 *       "delete" = "Drupal\academic_applications\Form\WorkflowDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\academic_applications\WorkflowHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "workflow",
 *   admin_permission = "administer academic applications",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/academic-applications-workflows/{academic_applications_workflow}",
 *     "add-form" = "/admin/structure/academic-applications-workflows/add",
 *     "edit-form" = "/admin/structure/academic-applications-workflows/{academic_applications_workflow}/edit",
 *     "delete-form" = "/admin/structure/academic-applications-workflows/{academic_applications_workflow}/delete",
 *     "collection" = "/admin/structure/academic-applications-workflows"
 *   }
 * )
 */
class Workflow extends ConfigEntityBase implements WorkflowInterface {

  /**
   * The workflow ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The workflow label.
   *
   * @var string
   */
  protected $label;

  /**
   * The application form ID.
   *
   * @var string
   */
  protected $application;

  /**
   * The upload form ID.
   *
   * @var string
   */
  protected $upload;

  /**
   * {@inheritdoc}
   */
  public function getApplication() {
    return $this->application;
  }

  /**
   * {@inheritdoc}
   */
  public function getUpload() {
    return $this->upload;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $this->addDependency('config', $this->application);
    $this->addDependency('config', $this->upload);
    return $this;
  }

}
