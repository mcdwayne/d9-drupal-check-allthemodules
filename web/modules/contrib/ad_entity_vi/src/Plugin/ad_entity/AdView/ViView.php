<?php

namespace Drupal\ad_entity_vi\Plugin\ad_entity\AdView;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ad_entity\Entity\AdEntityInterface;
use Drupal\ad_entity\Plugin\AdViewBase;

/**
 * Default HTML view handler plugin for Video Intelligence advertisement.
 *
 * @AdView(
 *   id = "vi_default",
 *   label = "Video Intelligence default view",
 *   library = "ad_entity_vi/default_view",
 *   requiresDomready = false,
 *   container = "html",
 *   allowedTypes = {
 *     "vi"
 *   }
 * )
 */
class ViView extends AdViewBase {

  /**
   * {@inheritdoc}
   */
  public function build(AdEntityInterface $entity) {
    return [
      '#theme' => 'vi_default',
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
