<?php
/**
 * Created by PhpStorm.
 * User: buyle
 * Date: 8/29/16
 * Time: 5:18 PM
 */

namespace Drupal\lightspeed_ecom\Plugin\Menu\LocalTask;


use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\lightspeed_ecom\ShopInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WebhookAdminLocalTasks extends DeriverBase implements ContainerDeriverInterface {
  use StringTranslationTrait;

  protected $entityManager;

  /**
   * WebhookAdminLocalTasks constructor.
   *
   * @param $entityManager
   * @param $stringTranslation;
   */
  public function __construct(EntityTypeManagerInterface $entityManager, TranslationInterface $stringTranslation) {
    $this->entityManager = $entityManager;
    $this->stringTranslation = $stringTranslation;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    /** @var EntityStorageInterface $entityStorage */
    $entityStorage = $this->entityManager->getStorage('lightspeed_ecom_shop');

    $entityType = $this->entityManager->getDefinition('lightspeed_ecom_shop');

    /** @var ShopInterface $shop */
    foreach ($entityStorage->loadMultiple() as $shop) {
      $this->derivatives["lightspeed_ecom.settings.webhooks_list.{$shop->id()}"] = [
        'title' => $shop->label(),
        'route_name' => 'lightspeed_ecom.settings.webhooks_list',
        'route_parameters' => [
          'shop' => $shop->id(),
        ],
      ];
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
      $entry += ['cache_tags' => []];
      $entry['cache_tags'] += $entityType->getListCacheTags();
    }

    return $this->derivatives;
  }


}
