<?php

namespace Drupal\drd\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Script Type entity.
 *
 * @ConfigEntityType(
 *   id = "drd_script_type",
 *   label = @Translation("Script Type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\drd\Entity\ListBuilder\ScriptType",
 *
 *     "form" = {
 *       "default" = "Drupal\drd\Entity\Form\ScriptType",
 *       "add" = "Drupal\drd\Entity\Form\ScriptType",
 *       "edit" = "Drupal\drd\Entity\Form\ScriptType",
 *       "delete" = "Drupal\drd\Entity\Form\ScriptTypeDelete"
 *     },
 *   },
 *   config_prefix = "script_type",
 *   admin_permission = "drd.administer script entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/drd/settings/script-types/script-type/{drd_script_type}",
 *     "add-form" = "/drd/settings/script-types/add",
 *     "edit-form" = "/drd/settings/script-types/script-type/{drd_script_type}/edit",
 *     "delete-form" = "/drd/settings/script-types/script-type/{drd_script_type}/delete",
 *     "collection" = "/drd/settings/script-types"
 *   }
 * )
 */
class ScriptType extends ConfigEntityBase implements ScriptTypeInterface {

  private static $selectList;

  /**
   * Get a list of script types for a form API select element.
   */
  public static function getSelectList() {
    if (!isset(self::$selectList)) {
      self::$selectList = [
        '' => '-',
      ];
      $config = \Drupal::config('drd.script_type');
      foreach ($config->getStorage()->listAll('drd.script_type') as $key) {
        $script = $config->getStorage()->read($key);
        self::$selectList[$script['id']] = $script['label'];
      }
    }
    return self::$selectList;
  }

  /**
   * Script type id.
   *
   * @var string
   */
  protected $id;

  /**
   * Script type label.
   *
   * @var string
   */
  protected $label;

  /**
   * Script type intepreter.
   *
   * @var string
   */
  protected $interpreter;

  /**
   * Script type file extension.
   *
   * @var string
   */
  protected $extension;

  /**
   * Script type prefix.
   *
   * @var string
   */
  protected $prefix;

  /**
   * Script type suffix.
   *
   * @var string
   */
  protected $suffix;

  /**
   * Script type line prefix.
   *
   * @var string
   */
  protected $lineprefix;

  /**
   * {@inheritdoc}
   */
  public function interpreter() {
    return $this->interpreter;
  }

  /**
   * {@inheritdoc}
   */
  public function extension() {
    return $this->extension;
  }

  /**
   * {@inheritdoc}
   */
  public function prefix() {
    return $this->prefix;
  }

  /**
   * {@inheritdoc}
   */
  public function suffix() {
    return $this->suffix;
  }

  /**
   * {@inheritdoc}
   */
  public function lineprefix() {
    return $this->lineprefix;
  }

}
