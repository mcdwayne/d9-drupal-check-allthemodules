<?php

namespace Drupal\ad_entity_dfp\Plugin\ad_entity\AdView;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ad_entity\Entity\AdEntityInterface;
use Drupal\ad_entity\Plugin\AdViewBase;

/**
 * Default HTML view handler plugin for DFP advertisement.
 *
 * @AdView(
 *   id = "dfp_default",
 *   label = "Default HTML view for a DFP tag",
 *   library = "ad_entity_dfp/default_view",
 *   requiresDomready = false,
 *   container = "html",
 *   allowedTypes = {
 *     "dfp"
 *   }
 * )
 */
class DFPView extends AdViewBase {

  /**
   * {@inheritdoc}
   */
  public function build(AdEntityInterface $entity) {
    return [
      '#theme' => 'dfp_default',
      '#ad_entity' => $entity,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function entityConfigForm(array $form, FormStateInterface $form_state, AdEntityInterface $ad_entity) {
    $element = [];

    $element['disable_initialization'] = [
      '#type' => 'checkbox',
      '#title' => $this->stringTranslation->translate('Disable automatic initialization'),
      '#description' => $this->stringTranslation->translate('In case your ad must be initialized only on custom conditions, you can turn this off. See the README for how to manually initialize ads.'),
      '#default_value' => $ad_entity->get('disable_initialization'),
      '#parents' => ['disable_initialization'],
    ];

    return $element;
  }

}
