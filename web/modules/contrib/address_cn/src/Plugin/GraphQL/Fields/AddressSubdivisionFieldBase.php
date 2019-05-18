<?php

namespace Drupal\address_cn\Plugin\GraphQL\Fields;

use CommerceGuys\Addressing\LocaleHelper;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\address_cn\AddressCnManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @see \Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter::getValues()#286
 */
abstract class AddressSubdivisionFieldBase extends FieldPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * The address cn manager.
   *
   * @var \Drupal\address_cn\AddressCnManagerInterface
   */
  protected $addressCnManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, SubdivisionRepositoryInterface $subdivision_repository, AddressCnManagerInterface $address_cn_manager) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->subdivisionRepository = $subdivision_repository;
    $this->addressCnManager = $address_cn_manager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('address.subdivision_repository'),
      $container->get('address_cn.manager')
    );
  }

  /**
   * Resolves subdivision.
   *
   * @param string $code
   *   The subdivision code
   * @param array $parents
   *   The parents (country code, subdivision codes).
   * @param string $locale
   *   The locale.
   *
   * @return array
   *   An array of properties of subdivision.
   */
  protected function resolveSubdivision($code, array $parents, $locale) {
    $subdivision = $this->subdivisionRepository->get($code, $parents);
    if ($subdivision) {
      $use_local_name = LocaleHelper::match($locale, $subdivision->getLocale());
      // We use local_name/name instead of local_code/code, since the former has shorter value.
      // For example:
      // "Guangxi Zhuangzuzizhiqu": {
      //   "local_code": "广西壮族自治区",
      // 	 "local_name": "广西",
      // 	 "iso_code": "CN-45",
      // 	 "has_children": true
      // },
      $name = $use_local_name ? $subdivision->getLocalName() : $subdivision->getName();
      $has_children = $this->addressCnManager->hasChildren($code, $parents);
      return [
        'code' => $code,
        'name' => $name,
        'has_children' => $has_children,
      ];
    }
    // Fall back to code.
    return [
      'code' => $code,
      'name' => $code,
      // The unknown children maybe due invalid code or parents.
      'has_children' => FALSE,
    ];
  }

}
