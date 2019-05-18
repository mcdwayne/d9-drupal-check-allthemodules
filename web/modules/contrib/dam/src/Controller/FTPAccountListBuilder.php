<?php

namespace Drupal\dam\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
* Provides a list controller for the FTPAccount entity.
*
* @ingroup ftp_account,
*/
class FTPAccountListBuilder extends EntityListBuilder {

  /**
  * The url generator.
  * @var \Drupal\Core\Routing\UrlGeneratorInterface
  */
  protected $urlGenerator;

  /**
  * {@inheritdoc}
  */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
        $entity_type,
        $container->get('entity.manager')->getStorage($entity_type->id()),
        $container->get('url_generator')
      );
  }

  /**
  * Constructs a new FTPAccountListBuilder object.
  *
  * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
  *   The entity type definition.
  * @param \Drupal\Core\Entity\EntityStorageInterface $storage
  *   The entity storage class.
  * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
  *
  */
  public function render() {
    $build['description'] = [
      '#markup' =>  $this->t('Current FTP Accounts in the system.'),
    ];
    $build['table'] = parent::render();
    return $build;
  }

  /**
  * {@inheritdoc}
  */
  public function buildHeader() {
    $header['name'] = $this->t('Username');
    $header['uid'] = $this->t('Owner');
    $header['status'] = $this->t('Status');
    $header['home'] = $this->t('Home Directory');
    return $header + parent::buildHeader();
  }

  /**
  * {@inheritdoc}
  */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    $row['name'] = $entity->get('username')->value;
    $row['uid'] = ($entity->get('uid')->first()) ?
      $entity->get('uid')->first()->entity
        ->toLink($entity->get('uid')->first()->entity->get('name')->value) :
      '';
    $row['status'] = ($entity->get('status')->value) ? t('Active') : t('Blocked');
    $row['home'] = $entity->get('home')->value;
    return $row + parent::buildRow($entity);
  }

}
