<?php

namespace Drupal\elastic_search\Mapping;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\elastic_search\Entity\FieldableEntityMap;
use Drupal\elastic_search\Exception\MapNotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElasticMappingDslGenerator
 *
 * @package Drupal\elastic_search\Elastic
 */
class ElasticMappingDslGenerator implements ContainerInjectionInterface {

  /**
   * @var EntityTypeManager
   */
  protected $manager;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\elastic_search\Mapping\Cartographer
   */
  protected $cartographer;

  /**
   * @var Token
   */
  protected $token;

  /**
   * @var \Throwable[]
   */
  private $errors = [];

  /**
   * ElasticMappingDslGenerator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager          $manager
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   * @param \Drupal\elastic_search\Mapping\Cartographer    $cartographer
   * @param \Drupal\Core\Utility\Token                     $token
   */
  public function __construct(EntityTypeManager $manager,
                              LanguageManagerInterface $languageManager,
                              Cartographer $cartographer,
                              Token $token) {
    $this->manager = $manager;
    $this->languageManager = $languageManager;
    $this->cartographer = $cartographer;
    $this->token = $token;
  }

  /**
   * @inheritDoc
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'),
                      $container->get('language_manager'),
                      $container->get('elastic_search.mapping.cartographer'),
                      $container->get('token'));
  }

  /**
   * @param array $mapIds
   *
   * @return array
   * @throws \Drupal\elastic_search\Exception\MapNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function generate(array $mapIds = []) {

    $this->resetErrors();

    //If indices is empty then we do everything
    if (empty($mapIds)) {
      $mapIds = NULL;
    }
    $maps = $this->manager->getStorage('fieldable_entity_map')
                          ->loadMultipleOverrideFree($mapIds);

    if (!$maps) {
      throw new MapNotFoundException();
    }

    //Foreach entity, build the mapping and create the indices
    $output = [];
    /** @var FieldableEntityMap $map */
    foreach ($maps as $map) {
      try {
        $output[] = $this->cartographer->makeElasticMapping($map);
      } catch (\Throwable $t) {
        $this->errors[$map->id()] = $t;
        continue;
      }
    }

    return $output;
  }

  /**
   * @param array           $data
   * @param string|Language $lang
   *
   * @return array
   */
  public function triggerTokenReplacement(array $data, $lang): array {

    $params = [$this->token, $lang];
    $function = function (&$item, $key, array $params) {
      $item = $params[0]->replace($item,
                                  [
                                    'lang' => $params[1] instanceof
                                              Language ?
                                      $params[1]->getId() : $params[1],
                                  ]);
    };

    array_walk_recursive($data, $function, $params);
    return $data;
  }

  /**
   * @return bool
   */
  public function hadErrors(): bool {
    return !empty($this->errors);
  }

  /**
   * @return array
   */
  public function getErrors(): array {
    return $this->errors;
  }

  /**
   * @return array
   */
  public function getErrorsAsStrings(): array {
    $output = [];
    foreach ($this->errors as $key => $error) {
      $output[$key] = $error->getMessage();
    }
    return $output;
  }

  /**
   * Empty the error array
   */
  private function resetErrors() {
    $this->errors = [];
  }

}