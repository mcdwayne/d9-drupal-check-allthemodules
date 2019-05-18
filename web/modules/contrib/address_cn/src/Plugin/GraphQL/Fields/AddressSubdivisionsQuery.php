<?php

namespace Drupal\address_cn\Plugin\GraphQL\Fields;

use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\address_cn\AddressCnManagerInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * Address subdivisions.
 *
 * @GraphQLField(
 *   id = "address_subdivisions_query",
 *   name = "addressSubdivisions",
 *   type = "[AddressSubdivision!]!",
 *   secure = true,
 *   arguments = {
 *     "parents" = {
 *       "type" = "[String!]",
 *       "default" = { "CN" },
 *     },
 *   },
 * )
 *
 * The 'parents' List Type is always nullable, even though we explicitly set
 * parents.nullable to false:
 * @see \Drupal\graphql\Plugin\GraphQL\Traits\TypedPluginTrait::decorateType()
 *
 * For 'parents' argument and 'locale', see:
 * vendor/commerceguys/addressing/resources/subdivision/
 *
 * The 'locale' may consist of two parts (en-US, zh-Hans):
 * 1. language code, e.g. en, zh
 * 2. country or variant code, e.g. US, Hans
 * see:
 * vendor/commerceguys/intl/resources/country/ or ../language
 * @see \CommerceGuys\Addressing\LocaleHelper::canonicalize()
 * @see \CommerceGuys\Addressing\LocaleHelper::getVariants()
 *
 * To get subdivisions:
 * @see \Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget::formElement()
 * @see \Drupal\address\Element\Address::processSubdivisionElements()
 */
class AddressSubdivisionsQuery extends FieldPluginBase implements ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  /**
   * The address subdivision repository.
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

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('address.subdivision_repository'),
      $container->get('address_cn.manager')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\address\Element\Address::processSubdivisionElements()
   */
  public function resolveValues($value, array $args, ResolveInfo $info) {
    // @todo: locale
    // $locale = \Drupal::languageManager()->getConfigOverrideLanguage()->getId();

    // @todo: Remove '省'?
    $parents = $args['parents'];
    $locale = 'zh-Hans';
    $subdivisions = $this->subdivisionRepository->getList($parents, $locale);
    $parents_depth = count($parents);
    if ($parents_depth == 1) {
      // We are going to get provinces, sort them.
      $this->addressCnManager->sortProvinces($subdivisions);
    }
    foreach ($subdivisions as $code => $name) {
      yield [
        'code' => $code,
        'name' => $name,
        'has_children' => $this->addressCnManager->hasChildren($code, $parents),
      ];
    }
  }

}
