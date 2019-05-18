<?php

namespace Drupal\flexfield\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flexfield\Plugin\FlexFieldTypeManager;
use Drupal\flexfield\Plugin\FlexFieldTypeManagerInterface;
use Drupal\flexfield\Plugin\Field\FieldFormatter\FlexFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'flex_template' formatter.
 *
 * Render the flexfield using a custom template with token replacement.
 *
 * @FieldFormatter(
 *   id = "flex_template",
 *   label = @Translation("Custom Template"),
 *   weight = 4,
 *   field_types = {
 *     "flex"
 *   }
 * )
 */
class FlexTemplateFormatter extends FlexFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'template' => ''
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $form = parent::settingsForm($form, $form_state);

    $tokens = [
      '#theme' => 'item_list',
    ];
    foreach ($this->getFlexFieldItems() as $name => $item) {
      $label = $item->getLabel();
      $tokens['#items'][] = "[$name]: $label value";
      $tokens['#items'][] = "[$name:label]: $label label";
    }

    $form['template'] = [
      '#type' => 'textarea',
      '#ttile' => t('Template'),
      '#description' => t('Output flexfield items using a custom template. Newlines will be converted to <br>. The following tokens are available for replacement: <br>') . drupal_render($tokens),
      '#rows' => 2,
      '#default_value' => $this->getSetting('template'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Template: @template', ['@template' => $this->getSetting('template')]);

    return $summary;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $output = '';
    $replacements = [];
    foreach ($this->getFlexFieldItems() as $name => $flexitem) {
      $replacements["[$name]"] = $flexitem->value($item);
      $replacements["[$name:label]"] = $flexitem->getLabel();
    }
    $output = nl2br(strtr($this->getSetting('template'), $replacements));
    return ['#markup' => $output];
  }

}
