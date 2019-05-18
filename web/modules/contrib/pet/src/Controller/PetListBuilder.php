<?php
namespace Drupal\pet\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for pet listing page.
 */
class PetListBuilder extends EntityListBuilder {

  /**
   * Proxy for the current user account.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, AccountProxyInterface $current_user) {
    parent::__construct($entity_type, $storage);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('PET ID');
    $header['title'] = $this->t('Title');
    $header['subject'] = $this->t('Subject');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['description'] = array(
      '#markup' => $this->t('You can manage the settings on the <a href="@adminlink">admin page</a>.', array(
        '@adminlink' => Url::fromRoute('pet.settings')->toString(),
      )),
      '#access' => $this->currentUser->hasPermission('add PET entity') ? TRUE : FALSE,
    );
    $build['add_pet'] = array(
      '#markup' => t('<p><a href="@addpet">Add previewable email template</a></p>', array(
          '@addpet' => Url::fromRoute('pet.add')->toString(),
      )),
      '#access' => $this->currentUser->hasPermission('add PET entity') ? TRUE : FALSE,
    );
    $build['table'] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $pid = $entity->id();
    $row['id'] = $pid;
    $url = Url::fromRoute('pet.preview', array('pet' => $pid));
    $row['label']['data'] = array(
      '#type' => 'link',
      '#title' => $entity->getTitle(),
      '#url' => $url,
    );
    $row['subject'] = $entity->getSubject();
    $row['status'] = $entity->getStatus() == 0 ? t('Custom') : $entity->getStatus();
    return $row + parent::buildRow($entity);
  }

}
