<?php

namespace Drupal\global_gateway_language\Plugin\global_gateway\mapper;

use Drupal\Core\Url;
use Drupal\global_gateway\Mapper\MapperPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a language mapper.
 *
 * @GlobalGatewayMapper(
 *   id = "region_languages",
 *   label = @Translation("Languages"),
 *   description = @Translation("Maps languages to regions."),
 *   entity_type_id = "global_gateway_language_mapping",
 * )
 */
class LanguageMapper extends MapperPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperationsLinks() {
    $operations['change'] = [
      'title' => 'Add',
      'url' => new Url(
        'global_gateway_language_mapping.edit_form',
        ['region_code' => $this->region]
      ),
    ];

    $entity = $this->getEntity();

    if (!empty($entity)) {
      $operations['change']['title'] = 'Edit';
      $operations['delete'] = [
        'title' => 'Reset',
        'url' => new Url(
          'global_gateway_language_mapping.delete_form',
          ['region_code' => $this->region]
        ),
      ];
    }

    return [
      'data' => [
        '#type' => 'operations',
        '#links' => $operations,
      ],
    ];
  }

  /**
   * Build associated array with language names by language codes.
   */
  public function getAllLanguageNames() {
    $names = [];
    $languages = \Drupal::languageManager()->getLanguages();
    foreach ($languages as $language) {
      $names[$language->getId()] = $language->getName();
    }
    return $names;
  }

  /**
   * Build language table row.
   */
  public static function buildLanguageCell($all_lang_names, $lang_codes) {
    $items = array_values(array_intersect_key($all_lang_names, $lang_codes));
    return implode(', ', $items);
  }

  /**
   * {@inheritdoc}
   */
  public function getOverviewByRegion() {
    $mapping = $this->getEntity();
    if (!$mapping) {
      return '—';
    }

    return self::buildLanguageCell(
      $this->getAllLanguageNames(),
      array_flip(array_column($mapping->getLanguages(), 'code'))
    );
  }

}
