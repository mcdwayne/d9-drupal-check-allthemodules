<?php

namespace Drupal\global_gateway_language\Plugin\global_gateway\switcher_data;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\global_gateway\Mapper\MapperPluginManager;
use Drupal\global_gateway\SwitcherData\SwitcherDataPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a language mapper.
 *
 * @GlobalGatewaySwitcherData(
 *   id = "global_gateway_language_switcher_data",
 *   label = @Translation("GlobalGateway Language Switcher Data"),
 * )
 */
class Languages extends SwitcherDataPluginBase {

  protected $languageManager;

  protected $mapper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('plugin.manager.global_gateway.mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LanguageManagerInterface $languageManager,
    MapperPluginManager $mapperManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $languageManager;
    $this->mapper = $mapperManager->createInstance('region_languages');
  }

  /**
   * Build language switcher links.
   *
   * @var string $region_code
   *   The region code.
   *
   * @return array
   *   Renderable array.
   */
  public function getLanguageLinks($region_code) {
    $languages = $this->getAvailableLanguages($region_code);

    $links = $this->languageManager->getLanguageSwitchLinks(
      'language_interface',
      Url::fromRoute('<current>')
    );
    if (is_object($links)) {
      if (!empty($languages)) {
        $links->links = array_intersect_key($links->links, $languages);
      }
      elseif ('none' != $region_code) {
        $links->links = [];
      }
      foreach ($links->links as &$link) {
        if (!empty($link['query']['ajax_form'])) {
          unset($link['query']);
        }
      }

      return [
        '#theme'            => 'links__language_block',
        '#links'            => $links->links,
        '#attributes'       => [
          'class' => [
            "language-switcher-{$links->method_id}",
          ],
        ],
        '#set_active_class' => TRUE,
      ];
    }

    return ['#links' => []];
  }

  /**
   * Build available languages array based on region.
   *
   * @return array
   *   Available languages array.
   */
  protected function getAvailableLanguages($region_code) {
    $languages   = [];
    $region_code = strtolower($region_code);

    $mapping = $this->mapper
      ->setRegion($region_code)
      ->getEntity();

    if ($region_code != 'none' && !empty($mapping)) {
      $langcodes = array_column($mapping->getLanguages(), 'code');
      $languages = array_combine($langcodes, $langcodes);
    }

    return $languages;
  }

  /**
   * Returns a renderable array.
   *
   * @return array
   *   A renderable array.
   */
  public function getOutput($region_code) {
    return $this->getLanguageLinks($region_code);
  }

}
