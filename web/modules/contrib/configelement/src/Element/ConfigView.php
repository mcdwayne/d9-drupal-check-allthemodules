<?php

namespace Drupal\configelement\Element;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Plugin\DataType\BooleanData;
use Drupal\Core\TypedData\Plugin\DataType\Email;
use Drupal\Core\TypedData\Plugin\DataType\FloatData;
use Drupal\Core\TypedData\Plugin\DataType\IntegerData;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\configelement\EditableConfig\EditableConfigItemFactory;
use Drupal\configelement\EditableConfig\EditableConfigItemInterface;

/**
 * Class ConfigView
 *
 * @RenderElement("configelement_view")
 *
 * @package Drupal\configelement
 *
 * Adds a config element showing un-overridden config, #type: configelement_view
 * Array keys:
 *  - #config_name The config name, like "system.site". Required.
 *  - #config_key The configkey, like "name". Defaults to "".
 *  - #language The config language overide if applicable. Defaults to none.
 */
class ConfigView extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processConfigView'],
      ],
      '#config_name' => NULL,
      '#config_key' => '',
      '#language' => NULL,
    ];
  }

  /**
   * #process callback for the config view element.
   *
   * @param array $element
   *   The form element to process. Properties used:
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   * @param array $completeForm
   *   The complete form structure.
   *
   * @return array
   *   The form element.
   */
  public static function processConfigView(&$element, FormStateInterface $formState, &$completeForm) {
    /** @var EditableConfigItemFactory $editableConfigItemFactory */
    $editableConfigItemFactory = \Drupal::service('configelement.editable_config_item_factory');
    $editableConfigItem = $editableConfigItemFactory->get($element['#config_name'], $element['#config_key'], $element['#language']);
    self::buildElement($element, $editableConfigItem);
    $editableConfigItem->addCachableDependencyTo($element);
    return $element;

  }

  /**
   * Element builder.
   *
   * @param $element
   *   The form element to process.
   * @param EditableConfigItemInterface $editableConfigItem
   */
  protected static function buildElement(&$element, EditableConfigItemInterface $editableConfigItem) {
    $name = $editableConfigItem->getName();
    $label = $editableConfigItem->getLabel();
    $schemaClass = $editableConfigItem->getSchemaClass();
    if ($name == '_core') {
      $element = [];
    }
    elseif ($editableConfigItem->isList()) {
      $element = [
        '#type' => 'fieldset',
        '#title' => $label,
      ];
      foreach ($editableConfigItem->getElements() as $childItem) {
        static::buildElement($element[$name], $childItem);
      }
    }
    elseif ($schemaClass === BooleanData::class) {
      $element = [
        '#markup' => $editableConfigItem->getValue() ? new TranslatableMarkup('True') : new TranslatableMarkup('False'),
      ];
    }
    elseif (in_array($schemaClass, [StringData::class, IntegerData::class, FloatData::class, Email::class])) {
      $element = [
        '#markup' => new FormattableMarkup('@k: @v', [
          '@k' => $label,
          '@v' => $editableConfigItem->getValue()
        ])
      ];
    }
    else {
      $element = ['#markup' => t('Can\'t yet display @s', ['@s' => $schemaClass])];
    }
  }

}
