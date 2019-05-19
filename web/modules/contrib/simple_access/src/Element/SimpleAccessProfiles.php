<?php

namespace Drupal\simple_access\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\simple_access\Entity\SimpleAccessProfile;

/**
 * Class SimpleAccessProfiles.
 *
 * @FormElement("simple_access_profiles")
 */
class SimpleAccessProfiles extends Checkboxes {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();

    $info += [
      '#node_type' => '',
    ];

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form) {
    $profiles = SimpleAccessProfile::loadMultiple();
    uasort($profiles, [SimpleAccessProfile::class, 'sort']);

    $element['#options'] = array_map(function (SimpleAccessProfile $a) {
      return $a->label();
    }, $profiles);
    $element['#access'] = !empty($element['#options']) && (
      \Drupal::currentUser()->hasPermission('assign profiles to nodes') ||
      \Drupal::currentUser()->hasPermission("assign profiles to {$element['#node_type']} nodes") ||
      \Drupal::currentUser()->hasPermission('administer nodes')
    );

    return parent::processCheckboxes($element, $form_state, $complete_form);
  }

}
