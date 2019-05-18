<?php

namespace Drupal\advanced_update\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\advanced_update\AdvancedUpdateEntityInterface;

/**
 * Defines the Advanced update entity entity.
 *
 * @ConfigEntityType(
 *   id = "advanced_update_entity",
 *   label = @Translation("Advanced update entity"),
 *   handlers = {
 *     "list_builder" = "Drupal\advanced_update\AdvancedUpdateEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\advanced_update\Form\AdvancedUpdateEntityForm",
 *       "edit" = "Drupal\advanced_update\Form\AdvancedUpdateEntityForm",
 *       "delete" = "Drupal\advanced_update\Form\AdvancedUpdateEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\advanced_update\AdvancedUpdateEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "advanced_update_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "date" = "date",
 *     "module_name" = "module_name",
 *     "class_name" = "class_name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/advanced_update_entity/{advanced_update_entity}",
 *     "add-form" = "/admin/structure/advanced_update_entity/add",
 *     "edit-form" = "/admin/structure/advanced_update_entity/{advanced_update_entity}/edit",
 *     "delete-form" = "/admin/structure/advanced_update_entity/{advanced_update_entity}/delete",
 *     "collection" = "/admin/structure/advanced_update_entity"
 *   }
 * )
 */
class AdvancedUpdateEntity extends ConfigEntityBase implements AdvancedUpdateEntityInterface {
  /**
   * The Advanced update entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Advanced update entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Advanced update entity creation date.
   *
   * @var integer
   */
  protected $date;

  /**
   * The Advanced update entity module name.
   *
   * @var string
   */
  protected $module_name;

  /**
   * The Advanced update entity class name.
   *
   * @var string
   */
  protected $class_name;

  /**
   * Get the date.
   *
   * @return int
   *    Timestamp of the date.
   */
  public function date() {
    return $this->date;
  }

  /**
   * Get the module name.
   *
   * @return string
   *    The module name.
   */
  public function moduleName() {
    return $this->module_name;
  }

  /**
   * Get the class name.
   *
   * @return string
   *    Class name.
   */
  public function className() {
    return $this->class_name;
  }

  /**
   * Generate a unique class name.
   *
   * @return string
   *    The class name generated.
   */
  public static function generateClassName() {
    return uniqid('AdvancedUpdate');
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $rendered = TRUE;
    $saved = FALSE;
    if ($this->isNew()) {
      $parameters = [
        'module' => $this->moduleName(),
        'update_class' => $this->className(),
        'description' => $this->label(),
      ];

      // Creating the update class.
      $new_class_path = drupal_get_path('module', $this->moduleName()) . '/src/AdvancedUpdate/' . $this->className() . '.php';
      if (!file_exists($new_class_path)) {
        $rendered = $this->renderFile(
          drupal_get_path('module', 'advanced_update') . '/templates/advanced-update.php.twig',
          drupal_get_path('module', $this->moduleName()) . '/src/AdvancedUpdate/' . $this->className() . '.php',
          $parameters
        );
      }
    }

    if (!$rendered) {
      drupal_set_message($this->t('Update file generation failed.'), 'error');
    }

    if ($rendered) {
      $new = $this->isNew();
      $saved = parent::save();
      if (!$saved && $new) {
        $this->unlinkClass();
      }
    }
    return $saved;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->unlinkClass();
    parent::delete();
  }

  /**
   * Remove the PHP file linked to the entity.
   *
   * @return bool
   *    Return TRUE if the file exists.
   */
  protected function unlinkClass() {
    $file_unlinked = FALSE;
    $class_path = $this->getUpdateClassPath();
    if (file_exists($class_path)) {
      \Drupal::service('file_system')->unlink($class_path);
      $file_unlinked = TRUE;
    }
    else {
      drupal_set_message($this->t('The file linked to this entity do not exists.'), 'warning');
    }
    return $file_unlinked;
  }

  /**
   * Get the path of the Advanced Update class linked to this entity.
   *
   * @return string
   *    The class path.
   */
  public function getUpdateClassPath() {
    return drupal_get_path('module', $this->moduleName()) . '/src/AdvancedUpdate/' . $this->className() . '.php';
  }

  /**
   * Render class file AdvancedUpdate.
   *
   * @param string $template
   *    Template path.
   * @param string $target
   *    File target.
   * @param array $parameters
   *    Parameters variables to send for render.
   * @param int $flag
   *    The value of flag can be any combination of file_put_contents flags.
   *
   * @return bool
   *    True if the file has been writed.
   */
  protected function renderFile($template, $target, $parameters, $flag = NULL) {
    if (!is_dir(dirname($target))) {
      mkdir(dirname($target), 0777, TRUE);
    }

    if (file_put_contents($target, \Drupal::service('twig')
      ->render($template, $parameters), $flag)) {
      $this->files[] = str_replace(DRUPAL_ROOT . '/', '', $target);
      return TRUE;
    }
    return FALSE;
  }

}
