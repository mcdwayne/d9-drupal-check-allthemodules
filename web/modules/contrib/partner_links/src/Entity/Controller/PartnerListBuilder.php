<?php

namespace Drupal\partner_links\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for partner entity.
 *
 * @ingroup partner_links
 */
class PartnerListBuilder extends EntityListBuilder {

  /**
   * The url generator.
   *
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
   * Constructs a new ContactListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, UrlGeneratorInterface $url_generator) {
    parent::__construct($entity_type, $storage);
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('You can manage the fields on the <a href="@adminlink">Partners admin page</a>.', [
        '@adminlink' => $this->urlGenerator->generateFromRoute('partner_links.partner_settings'),
      ]),
    ];
    $build['table'] = parent::render();
    $build['help_text'] = [
      '#markup' => '
        <legend><strong>Partner Statuses:</strong></legend>
        <ul>
          <li>NOT_CHECKED = 1</li>
          <li>NOT_AVAILABLE = 2</li>
          <li>NOT_OK = 3</li>
          <li>OK = 4</li>
        </ul>
      ',
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Partner ID');
    $header['name'] = $this->t('Partner Name');
    $header['url'] = $this->t('Partner URL');
    $header['status'] = $this->t('Partner Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\partner_links\Entity\Partner */
    $row['id'] = $entity->id();
    $row['name'] = $entity->link();
    $row['url'] = $entity->url->value;
    $row['status'] = $entity->status->value;
    return $row + parent::buildRow($entity);
  }

}
