<?php

namespace Drupal\bueditor\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the BUEditor Button entity.
 *
 * @ConfigEntityType(
 *   id = "bueditor_button",
 *   label = @Translation("BUEditor Button"),
 *   handlers = {
 *     "list_builder" = "Drupal\bueditor\BUEditorButtonListBuilder",
 *     "form" = {
 *       "add" = "Drupal\bueditor\Form\BUEditorButtonForm",
 *       "edit" = "Drupal\bueditor\Form\BUEditorButtonForm",
 *       "delete" = "Drupal\bueditor\Form\BUEditorButtonDeleteForm",
 *       "duplicate" = "Drupal\bueditor\Form\BUEditorButtonForm"
 *     }
 *   },
 *   admin_permission = "administer bueditor",
 *   config_prefix = "button",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/content/bueditor/buttons/{bueditor_button}",
 *     "delete-form" = "/admin/config/content/bueditor/buttons/{bueditor_button}/delete",
 *     "duplicate-form" = "/admin/config/content/bueditor/buttons/{bueditor_button}/duplicate"
 *   }
 * )
 */
class BUEditorButton extends ConfigEntityBase {

  /**
   * Button ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Button label.
   *
   * @var string
   */
  protected $label;

  /**
   * Button tooltip.
   *
   * @var string
   */
  protected $tooltip;

  /**
   * Button text.
   *
   * @var string
   */
  protected $text;

  /**
   * Class name.
   *
   * @var string
   */
  protected $cname;

  /**
   * Shortcut.
   *
   * @var string
   */
  protected $shortcut;

  /**
   * Code to insert into text area.
   *
   * @var string
   */
  protected $code;

  /**
   * Template html to insert into editor UI.
   *
   * @var string
   */
  protected $template;

  /**
   * Required libraries.
   *
   * @var array
   */
  protected $libraries = [];

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    // Add id prefix.
    $id = $this->id();
    if (isset($id) && strpos($id, 'custom_') !== 0) {
      $this->set('id', 'custom_' . $id);
    }
  }

  /**
   * Returns an array of button properties for JS.
   */
  public function jsProperties() {
    $props = ['id', 'label', 'tooltip', 'text', 'cname', 'shortcut', 'code', 'template'];
    $data = [];
    foreach ($props as $prop) {
      if ($value = $this->get($prop)) {
        $data[$prop] = $value;
      }
    }
    return $data;
  }

}
