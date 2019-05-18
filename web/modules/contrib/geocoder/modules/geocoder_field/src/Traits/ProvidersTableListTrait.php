<?php

declare(strict_types = 1);

namespace Drupal\geocoder_field\Traits;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Reusable functionality for rendering lists of providers in a table.
 */
trait ProvidersTableListTrait {

  use StringTranslationTrait;

  /**
   * Generates the Draggable Table of Selectable Geocoder Plugins.
   *
   * @param array $enabled_provider_ids
   *   The IDs of the enabled Geocoder providers.
   *
   * @return array
   *   The plugins table list.
   */
  public function providersTableList(array $enabled_provider_ids): array {
    $providers_link = $this->link->generate(t('Geocoder providers configuration page'), Url::fromRoute('entity.geocoder_provider.collection', [], [
      'attributes' => ['target' => '_blank'],
    ]));

    $options_field_description = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('Object literals in YAML format. Edit options in the @providers_link.', [
        '@providers_link' => $providers_link ,
      ]),
      '#attributes' => [
        'class' => [
          'options-field-description',
        ],
      ],
    ];

    $caption = [
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'label',
        '#value' => $this->t('Geocoder providers'),
      ],
      'caption' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Select and reorder the Geocoder providers to use. The first one returning a valid value will be used.<br>If the provider of your choice does not appear here, you have to create it first in the @providers_link.', [
          '@providers_link' => $providers_link,
        ]),
      ],
    ];

    $element['plugins'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Weight'),
        $this->t('Options<br>@options_field_description', [
          '@options_field_description' => $this->renderer->renderRoot($options_field_description),
        ]),
      ],
      '#tabledrag' => [[
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'plugins-order-weight',
      ],
      ],
      '#caption' => $this->renderer->renderRoot($caption),
      // We need this class for #states to hide the entire table.
      '#attributes' => ['class' => ['js-form-item', 'geocode-plugins-list']],
    ];

    // Reorder the plugins promoting the default ones in the proper order. By
    // initializing the enabled providers first they will appear at the top of
    // the list.
    $providers = [];
    foreach ($enabled_provider_ids as $enabled_provider_id) {
      $providers[$enabled_provider_id] = NULL;
    }

    foreach ($this->entityTypeManager->getStorage('geocoder_provider')->loadMultiple() as $provider_entity) {
      // Non-default values are appended at the end.
      $providers[$provider_entity->id()]['entity'] = $provider_entity;
    }

    // Check if there are orphaned providers being configured. This might happen
    // if a provider is deleted if it is still in use.
    $orphaned_provider_ids = array_keys($providers, NULL, TRUE);
    if (!empty($orphaned_provider_ids)) {
      // Remove the orphaned providers.
      $providers = array_filter($providers);

      // Show a warning to the user.
      $warning = new PluralTranslatableMarkup(count($orphaned_provider_ids), 'The @providers Geocoder provider was not found and has been removed.', 'The following Geocoder providers were not found and have been removed: @providers', [
        '@providers' => implode(', ', $orphaned_provider_ids),
      ]);
      $this->messenger()->addWarning($warning);
    }

    if (empty($providers)) {
      $message = $this->t('No Geocoding providers have been configured yet. Please create one in the @providers_link.', [
        '@providers_link' => $providers_link,
      ]);
      return [
        '#theme' => 'status_messages',
        '#message_list' => ['warning' => [$message]],
        '#status_headings' => ['warning' => $this->t('Warning message')],
      ];
    }

    $providers = array_map(function ($provider, $weight) use ($enabled_provider_ids): array {
      /** @var \Drupal\geocoder\Entity\GeocoderProvider $provider_entity */
      $provider_entity = $provider['entity'];
      $checked = \in_array($provider_entity->id(), $enabled_provider_ids, TRUE);

      return array_merge($provider, [
        'checked' => $checked,
        'weight' => $checked ? $weight : 0,
        'arguments' => $provider_entity->isConfigurable() ? Yaml::encode($provider_entity->get('configuration')) : (string) $this->t("This plugin doesn't accept arguments."),
      ]);
    }, $providers, range(0, count($providers) - 1));

    uasort($providers, function ($providerA, $providerB): int {
      $order = $providerB['checked'] <=> $providerA['checked'];

      if (0 === $order) {
        $order = $providerA['weight'] - $providerB['weight'];

        if (0 === $order) {
          $order = strcmp($providerA['entity']->label(), $providerB['entity']->label());
        }
      }

      return $order;
    });

    foreach ($providers as $provider) {
      /** @var \Drupal\geocoder\Entity\GeocoderProvider $provider_entity */
      $provider_entity = $provider['entity'];
      $element['plugins'][$provider_entity->id()] = [
        'checked' => [
          '#type' => 'checkbox',
          '#title' => $provider_entity->label(),
          '#default_value' => $provider['checked'],
        ],
        'weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', ['@title' => $provider_entity->label()]),
          '#title_display' => 'invisible',
          '#default_value' => $provider['weight'],
          '#delta' => 20,
          '#attributes' => ['class' => ['plugins-order-weight']],
        ],
        'arguments' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => $provider['arguments'],
        ],
        '#attributes' => ['class' => ['draggable']],
      ];
    }

    return $element['plugins'];
  }

}
