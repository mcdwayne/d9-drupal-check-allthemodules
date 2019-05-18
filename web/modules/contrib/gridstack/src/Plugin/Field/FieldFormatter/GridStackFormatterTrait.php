<?php

namespace Drupal\gridstack\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * A Trait common for gridstack formatters.
 */
trait GridStackFormatterTrait {

  /**
   * The gridstack field formatter manager.
   *
   * @var \Drupal\gridstack\GridStackFormatterInterface
   */
  protected $formatter;

  /**
   * The gridstack field formatter manager.
   *
   * @var \Drupal\gridstack\GridStackManagerInterface
   */
  protected $manager;

  /**
   * Returns the gridstack field formatter service.
   */
  public function formatter() {
    return $this->formatter;
  }

  /**
   * Returns the gridstack service.
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * Returns the gridstack admin service shortcut.
   */
  public function admin() {
    return \Drupal::service('gridstack.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return $this->admin()->getSettingsSummary($this->getScopedFormElements());
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()->isMultiple();
  }

}
