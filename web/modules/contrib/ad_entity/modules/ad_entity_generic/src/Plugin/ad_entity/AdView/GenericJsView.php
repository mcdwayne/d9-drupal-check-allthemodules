<?php

namespace Drupal\ad_entity_generic\Plugin\ad_entity\AdView;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ad_entity\Entity\AdEntityInterface;
use Drupal\ad_entity\Plugin\AdViewBase;

/**
 * JavaScript view handler plugin for generic advertisement.
 *
 * @AdView(
 *   id = "generic",
 *   label = "Generic ads via JavaScript",
 *   library = "ad_entity_generic/view",
 *   requiresDomready = false,
 *   container = "html",
 *   allowedTypes = {
 *     "generic"
 *   }
 * )
 */
class GenericJsView extends AdViewBase {

  /**
   * {@inheritdoc}
   */
  public function build(AdEntityInterface $entity) {
    return [
      '#theme' => 'ad_entity_generic_js',
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
