<?php

namespace Drupal\httpbl\Entity\Controller;

use Drupal\httpbl\HttpblManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for a host entity.
 *
 * @ingroup httpbl
 */
class HostListBuilder extends EntityListBuilder {

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
    $build['table'] = parent::render();
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
    $header['id'] = $this->t('hid');
    $header['name'] = $this->t('Host');
    $header['host_status'] = $this->t('Host Status');
    $header['source'] = $this->t('Source');
    $header['expire'] = $this->t('Expires');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Changed');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\httpbl\Entity\Host */
    $row['id'] = $entity->id();
    
    //$row['name'] = $entity->link();
    // We don't really need to "view" the entity as content; there is nothing
    // interesting to see there. So override the default link with a link to a
    // profile on Project Honeypot.
    $host_ip = $entity->host_ip->value;
    $url = \Drupal\Core\Url::fromUri('http://www.projecthoneypot.org/search_ip.php?ip=' . $host_ip);
    $url_options = [
      'attributes' => [
        'target' => '_blank',
        'title' => 'View this host\'s profile on Project Honeypot.',
      ]];
    $url->setOptions($url_options);
    $project_link = \Drupal\Core\Link::fromTextAndUrl(t($host_ip), $url )->toString();
    $row['name'] = $project_link;
    
    // Status with humanized conversion.
    $httpblManager = \Drupal::service('httpbl.evaluator');
    $human = $httpblManager->getHumanStatus($entity->host_status->value);
    $row['host_status'] = t($entity->host_status->value . ' - <em style="color: lightgrey;">' . $human . '</em>');

    // If this entity is blacklisted && Ban module exists...
    if (($entity->host_status->value == HTTPBL_LIST_BLACK) && (\Drupal::moduleHandler()->moduleExists('ban'))) {
      // Also check if it has been banned.
      $ip = $entity->host_ip->value;
      $banManager = \Drupal::service('ban.ip_manager');

      // If this host is also found in ban_ip table...
      if ($banManager->isBanned($ip)) {
        // Report as banned on the list, in addition to being blacklisted.
        $row['host_status'] = t($entity->host_status->value . ' - <em style="color: lightgrey;">' . $human . ' and Banned!</em>');
      }
    }
    
    // Source of this evaluation.
    // If this is original, un-altered (no admin management decisions) Project
    // Honeypot source, then also provide the project link here.
    if ($entity->source->value == t(HTTPBL_ORIGINAL_SOURCE)) {
      // recycle the url from above...
      $url->setOptions($url_options);
      $project_link = \Drupal\Core\Link::fromTextAndUrl(t($entity->source->value), $url )->toString();
      $row['source'] = $project_link;
    }
    else {
      $row['source'] = $entity->source->value;
    }

    // Expiration...
    // If expire has zero time left, show  as "next cron".
    // Otherwise, show expire timestamp formatted as "time until."
    if ($entity->expire->value < \Drupal::time()->getRequestTime()) {
      $row['expire'] = t('(next cron)');
    }
    else {
      $row['expire'] = \Drupal::service('date.formatter')->formatTimeDiffUntil($entity->expire->value);
    }
    
    // Created and Changed
    $row['created'] = \Drupal::service('date.formatter')->format($entity->created->value, 'html_date');
    $row['changed'] = \Drupal::service('date.formatter')->format($entity->changed->value, 'html_datetime');
    return $row + parent::buildRow($entity);
  }

}
