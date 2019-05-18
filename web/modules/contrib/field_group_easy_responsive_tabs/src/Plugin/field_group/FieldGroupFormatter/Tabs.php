<?php

namespace Drupal\field_group_easy_responsive_tabs\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Plugin implementation of the 'ertta_tabs' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "ertta_tabs",
 *   label = @Translation("Easy Responsive Tabs to Accordion - Tabs"),
 *   description = @Translation("This fieldgroup renders child groups in its own tabs wrapper."),
 *   supported_contexts = {
 *     "form",
 *     "view",
 *   }
 * )
 */
class Tabs extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    parent::preRender($element, $rendering_object);

    if (!empty($this->getSetting('id'))) {
      $id = $this->getSetting('id');
    }
    else {
      $class = implode('-', $this->getClasses());
      $id = md5($class);
    }

    $label = $this->t($this->getLabel());

    $element += [
      '#type'           => 'field_group_easy_responsive_tabs',
      // By default tabs don't have titles but you can override it in the theme.
      '#title'          => !empty($label) ? Html::escape($label) : '',
      '#id'             => Html::getId($id),
      '#theme_wrappers' => ['field_group_easy_responsive_tabs'],
      '#parents'        => [$this->group->parent_name],
      '#group_name'     => !empty($element['#group_name']) ? $element['#group_name'] : $this->group->group_name,
      '#attributes'     => [
        'id'    => Html::getId($id),
        'class' => $this->getClasses(),
      ],
      '#is_child'       => TRUE,
    ];

    // Top level group.
    if (empty($element['#parents']) || $element['#parents'][0] == "") {
      $element['#is_child'] = FALSE;
    }

    $on_form = $this->context == 'form';

    // Add required JavaScript and Stylesheet.
    $element['#attached']['library'][] = 'field_group_easy_responsive_tabs/easy-responsive-tabs';
    $element['#attached']['library'][] = 'field_group_easy_responsive_tabs/easy-responsive-tabs-init';

    // Only add forms library on forms.
    if ($on_form) {

    }

    $settings = [
      'identifier'                  => Html::getId($id),
      'type'                        => $this->getSetting('type'),
      'width'                       => $this->getSetting('width'),
      'fit'                         => (bool) $this->getSetting('fit'),
      'closed'                      => (bool) $this->getSetting('closed'),
      'tabidentify'                 => Html::getId($id),
      'activetab_bg'                => $this->getSetting('active_bg'),
      'inactive_bg'                 => $this->getSetting('inactive_bg'),
      'active_border_color'         => $this->getSetting('active_border_color'),
      'active_content_border_color' => $this->getSetting('active_content_border_color'),
    ];

    foreach ($settings as $name => $value) {
      $element['#attributes']['data-' . $name] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = parent::settingsForm();

    $form['type'] = [
      '#title'         => $this->t('Type'),
      '#type'          => 'select',
      '#options'       => [
        'default'   => $this->t('Horizontal'),
        'vertical'  => $this->t('Vertical'),
        'accordion' => $this->t('Accordion'),
      ],
      '#default_value' => $this->getSetting('type'),
    ];

    $form['width'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Width'),
      '#description'   => $this->t('auto or any custom width.'),
      '#default_value' => $this->getSetting('width'),
      '#size'          => 10,
    ];

    $form['fit'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Fit'),
      '#description'   => $this->t('100% fits in a container'),
      '#default_value' => $this->getSetting('fit'),
      '#options'       => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
    ];

    $form['closed'] = [
      '#title'         => $this->t('Closed'),
      '#description'   => $this->t('Close the panels on start, the options "accordion" and "tabs" keep them closed in there respective view types.'),
      '#type'          => 'select',
      '#options'       => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $this->getSetting('closed'),
    ];

    $form['active_bg'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Active tab bg'),
      '#description'   => $this->t('Background color for active tabs in this group.'),
      '#default_value' => $this->getSetting('active_bg'),
      '#size'          => 10,
    ];

    $form['inactive_bg'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Inactive tab bg'),
      '#description'   => $this->t('Background color for inactive tabs in this group.'),
      '#default_value' => $this->getSetting('inactive_bg'),
      '#size'          => 10,
    ];

    $form['active_border_color'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Active border color'),
      '#description'   => $this->t('Border color for active tabs heads in this group.'),
      '#default_value' => $this->getSetting('active_border_color'),
      '#size'          => 10,
    ];

    $form['active_content_border_color'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('IActive content border color'),
      '#description'   => $this->t('Border color for active tabs contect in this group so that it matches the tab head border.'),
      '#default_value' => $this->getSetting('active_content_border_color'),
      '#size'          => 10,
    ];

    $form['id'] = [
      '#type'             => 'textfield',
      '#title'            => $this->t('The tab groups ID'),
      '#description'      => $this->t('The tab groups identifier *This should be a unique name for each tab group and should not be defined in any styling or css file.'),
      '#default_value'    => $this->getSetting('id'),
      '#weight'           => 10,
      '#element_validate' => [
        'field_group_validate_id',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('Type: @type', [
      '@type' => $this->getSetting('type'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    return [
      'type'                        => 'default',
      'width'                       => 'auto',
      'fit'                         => TRUE,
      'closed'                      => FALSE,
      'active_bg'                   => '',
      'inactive_bg'                 => '',
      'active_border_color'         => '',
      'active_content_border_color' => '',
    ] + parent::defaultContextSettings($context);
  }

  /**
   * {@inheritdoc}
   */
  public function getClasses() {
    $classes = parent::getClasses();

    $classes[] = 'field-group-easy-responsive-tabs';
    $classes[] = 'field-group-easy-responsive-tabs-' . $this->getSetting('type');

    $classes[] = 'field-group-' . $this->group->format_type . '-wrapper';

    return $classes;
  }

}
