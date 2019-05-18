<?php

namespace Drupal\domain_traversal\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DomainTraversalMenuLink extends DeriverBase implements ContainerDeriverInterface {
  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $domain_storage;

  /**
   * DomainTraversalMenuLink constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $domain_storage
   */
  public function __construct(EntityStorageInterface $domain_storage) {
    $this->domain_storage = $domain_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')->getStorage('domain')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    /** @var \Drupal\domain\Entity\Domain[] $domains */
    $domains = $this->domain_storage->loadMultiple();
    foreach ($domains as $domain) {
      if (!$domain->status()) {
        continue;
      }

      $args = [
        '@domain_name' => Markup::create($domain->get('name')),
        '@domain_hostname' => $domain->getPath(),
      ];

      $this->derivatives['domain_traversal.traverse.' . $domain->id()] = [
          'route_name' => 'domain_traversal.traverse',
          'route_parameters' => [
            'domain' => $domain->id(),
          ],
          'title' => Markup::create($domain->get('name')),
          'description' => $this->t('Traverse to this domain: @domain_hostname', $args),
          'parent' => 'domain_traversal',
          'menu_name' => 'admin',
          'weight' => $domain->getWeight(),
        ] + $base_plugin_definition;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
