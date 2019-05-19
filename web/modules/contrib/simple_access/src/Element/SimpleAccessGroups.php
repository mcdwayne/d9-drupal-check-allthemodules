<?php

namespace Drupal\simple_access\Element;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\simple_access\Entity\SimpleAccessGroup;

/**
 * Simple access groups element.
 *
 * @FormElement("simple_access_groups")
 */
class SimpleAccessGroups extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class_name = get_class($this);
    $element = [
      'wrapper' => [
        '#input' => FALSE,
      ],
      '#default_value' => [],
      '#tree' => TRUE,
      '#process' => [
        [$class_name, 'processSimpleAccessGroups'],
      ],
      '#pre_render' => [
        [$class_name, 'preRenderSimpleAccessGroups'],
      ],
      '#override_privilege' => FALSE,
      '#node_type' => '',
    ];

    return $element;
  }

  /**
   * Build the simple_aqccess_groups element.
   */
  public static function processSimpleAccessGroups(&$element, FormStateInterface $form_state, &$complete_form) {
    $groups = SimpleAccessGroup::loadMultiple();
    uasort($groups, [SimpleAccessGroup::class, 'sort']);

    $config = \Drupal::config('simple_access.settings');

    $element['#default_value'] = is_array($element['#default_value']) ? $element['#default_value'] : [];

    $element['wrapper'] = isset($element['wrapper']) ? $element['wrapper'] : [];
    $element['wrapper'] += [
      '#type' => 'table',
    ];
    $element['wrapper']['#header'] = [
      t('Groups'),
    ];

    foreach (array_filter($config->get('display')) as $item) {
      $element['wrapper']['#header'][] = Unicode::ucfirst($item);
    }

    /** @var \Drupal\simple_access\Entity\SimpleAccessGroup $group */
    foreach ($groups as $group) {
      $element['wrapper'][$group->id()] = isset($element['wrapper'][$group->id()]) ? $element['wrapper'][$group->id()] : [];
      $element['wrapper'][$group->id()] += [
        '#parents' => array_merge($element['#parents'], [$group->id()]),
      ];

      $privilege = $element['#override_privilege'] || $group->canManageAccess($element['#node_type']);

      $element['#default_value'][$group->id()] = is_array($element['#default_value'][$group->id()]) ? $element['#default_value'][$group->id()] : [];
      $element['#default_value'][$group->id()] += [
        'view' => 0,
        'update' => 0,
        'delete' => 0,
      ];
      $element['wrapper'][$group->id()]['#access'] = $privilege;
      $element['wrapper'][$group->id()]['name'] = [
        '#markup' => $group->label(),
        '#access' => $privilege,
      ];
      $element['wrapper'][$group->id()]['view'] = [
        '#type' => 'checkbox',
        '#default_value' => $element['#default_value'][$group->id()]['view'],
        '#access' => $privilege && $config->get('display.view'),
      ];
      $element['wrapper'][$group->id()]['update'] = [
        '#type' => 'checkbox',
        '#default_value' => $element['#default_value'][$group->id()]['update'],
        '#access' => $privilege && $config->get('display.update'),
      ];
      $element['wrapper'][$group->id()]['delete'] = [
        '#type' => 'checkbox',
        '#default_value' => $element['#default_value'][$group->id()]['delete'],
        '#access' => $privilege && $config->get('display.delete'),
      ];
    }

    return $element;
  }

  /**
   * Move all values aside which are not accessible so not to be included.
   */
  public static function preRenderSimpleAccessGroups($element) {
    foreach (Element::children($element['wrapper']) as $group) {
      if (isset($element['wrapper'][$group]['#access']) && !$element['wrapper'][$group]['#access']) {
        $element['no_access'][$group] = $element['wrapper'][$group];
        unset($element['wrapper'][$group]);
      }
      else {
        foreach (Element::children($element['wrapper'][$group]) as $access_type) {
          if (isset($element['wrapper'][$group][$access_type]['#access']) && !$element['wrapper'][$group][$access_type]['#access']) {
            $element['no_access'][$group][$access_type] = $element['wrapper'][$group][$access_type];
            unset($element['wrapper'][$group][$access_type]);
          }
        }
      }
    }

    return $element;
  }

}
