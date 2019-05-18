<?php

namespace Drupal\field_group_easy_responsive_tabs\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Plugin implementation of the 'ertta_tab' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "ertta_tab",
 *   label = @Translation("Easy Responsive Tabs to Accordion - Tab"),
 *   description = @Translation("This fieldgroup renders the content as a tab."),
 *   supported_contexts = {
 *     "form",
 *     "view",
 *   },
 * )
 */
class Tab extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    parent::preRender($element, $rendering_object);

    $label = $this->t($this->getLabel());

    $add = [
      '#type'           => 'field_group_easy_responsive_tab',
      '#title'          => !empty($label) ? Html::escape($label) : '',
      '#description'    => $this->getSetting('description'),
      '#theme_wrappers' => ['field_group_easy_responsive_tab'],
      '#attributes'     => [
        'class' => $this->getClasses(),
      ],
    ];

    // Front-end and back-end on configuration will lead
    // to vertical tabs nested in a separate vertical group.
    if (!empty($this->group->parent_name)) {
      $add['#parents'] = [$this->group->parent_name];
    }

    if ($this->getSetting('id')) {
      $add['#id'] = Html::getId($this->getSetting('id'));
    }
    else {
      $add['#id'] = Html::getId('edit-' . $this->group->group_name);
    }

    $element += $add;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = parent::settingsForm();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = [
      ] + parent::defaultContextSettings($context);

    return $defaults;
  }

}
