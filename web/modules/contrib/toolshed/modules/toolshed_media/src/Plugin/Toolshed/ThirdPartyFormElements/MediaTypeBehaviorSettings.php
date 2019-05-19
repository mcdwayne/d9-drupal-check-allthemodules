<?php

namespace Drupal\toolshed_media\Plugin\Toolshed\ThirdPartyFormElements;

use Drupal\core\Form\FormStateInterface;
use Drupal\core\Config\Entity\ConfigEntityInterface;
use Drupal\toolshed\ThirdPartyFormElements;

/**
 * Third party settings form for settings menu posititioning of the entity.
 *
 * @ThirdPartyFormElements(
 *   id = "toolshed_media_type_display",
 *   name = "media_type_behaviors",
 *   label = @Translation("Display behaviors"),
 *   help = @Translation("Settings for hand."),
 *   entity_types = {
 *     "media_type",
 *     "media_bundle",
 *   },
 * );
 */
class MediaTypeBehaviorSettings extends ThirdPartyFormElements {

  /**
   * {@inheritdoc}
   */
  protected function defaultSettings() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(ConfigEntityInterface $entity, array $parents, FormStateInterface $state) {
    return [
      '#type' => 'details',
      '#title' => $this->pluginDefinition['label'],
      '#group' => 'additional_settings',
      '#weight' => 10,

      'redirect_to_file' => [
        '#type' => 'checkbox',
        '#title' => t('Redirect non-admins to file'),
        '#default_value' => $this->getSettings($entity),
      ],
    ];
  }

}
