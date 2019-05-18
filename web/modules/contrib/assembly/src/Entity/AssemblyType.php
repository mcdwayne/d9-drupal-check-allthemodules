<?php

namespace Drupal\assembly\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Assembly type entity.
 *
 * @ConfigEntityType(
 *   id = "assembly_type",
 *   label = @Translation("Assembly type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\assembly\AssemblyTypeListBuilder",
 *     "access" = "Drupal\assembly\AssemblyTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\assembly\Form\AssemblyTypeForm",
 *       "edit" = "Drupal\assembly\Form\AssemblyTypeForm",
 *       "delete" = "Drupal\assembly\Form\AssemblyTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\assembly\AssemblyTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "assembly_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "assembly",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/assembly/add",
 *     "edit-form" = "/admin/structure/assembly/{assembly_type}",
 *     "delete-form" = "/admin/structure/assembly/{assembly_type}/delete",
 *     "collection" = "/admin/structure/assembly"
 *   },
  *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "visual_styles",
 *     "new_revision"
 *   }
 * )
 */
class AssemblyType extends ConfigEntityBundleBase implements AssemblyTypeInterface {

  /**
   * The Assembly type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Assembly type label.
   *
   * @var string
   */
  protected $label;

  /**
   * Default value of the 'Create new revision' checkbox of this node type.
   *
   * @var bool
   */
  protected $new_revision = TRUE;


  /**
   * A text description of the assembly type.
   * @var string
   */
  protected $visual_styles;

  /**
   * A text description of the assembly type.
   * @var string
   */
  public $description;

  /**
   * {@inheritdoc}
   */
  public function isNewRevision() {
    return $this->new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($new_revision) {
    $this->new_revision = $new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->isNewRevision();
  }

  public function getVisualStyles() {
    return $this->visual_styles;
  }

  public function setVisualStyles($styles) {
    $this->visual_styles = $styles;
  }

  public function getVisualStylesParsed() {
    return self::parseVisualStyles($this->visual_styles);
  }

  public function getVisualStylesAsOptions() {
    $options = [];
    foreach ($this->getVisualStylesParsed() as $key => $details) {
      $options[$key] = $details['label'];
    }

    return $options;
  }

  public function getVisualStylesHelp($assembly_uuid) {

    $help = [];
    foreach ($this->getVisualStylesParsed() as $key => $details) {
      $help[] = '<strong>' . t($details['label'])->render() . '</strong> — <span class="text-muted">' . t($details['description'])->render() . '</span>';
    }
    $help = '<div class="collapse" id="assembly-styles-help-' . $assembly_uuid . '"><ul><li>' . implode('</li><li>', $help) . '</li></ul></div>';

    $description = implode(' ', [
      t('Choose additional visual styles to apply to this content bar.')->render(),
      '<a class="btn btn-default btn-xs" href="#assembly-style-help-' . $assembly_uuid . '" data-toggle="collapse" aria-expanded="false" aria-controls="assembly-style-help-' . $assembly_uuid . '"><span class="glyphicon glyphicon-question-sign"></span>',
      t('Show styles help')->render(),
      '</a>',
      $help
    ]);


    foreach ($this->getVisualStylesParsed() as $key => $details) {
      $options[$key] = $details['label'];
    }
    return $help;
  }

  public static function parseVisualStyles($styles) {
    $styles = array_filter(explode(PHP_EOL, $styles));
    $parsed_styles = [];
    foreach ($styles as $style) {
      $parts = explode('|', $style);
      if (count($parts) < 2) {
        continue;
      }
      $parsed_styles[$parts[0]] = array(
        'label' => $parts[1],
        'description' => isset($parts[2]) ? $parts[2] : ''
      );
    }

    return $parsed_styles;
  }

}
