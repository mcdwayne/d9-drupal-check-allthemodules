<?php

namespace Drupal\domain_route_meta_tags\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a domain_route_meta_tags entity.
 *
 * @ingroup domain_route_meta_tags
 */
class DomainRouteMetaTagDeleteForm extends ContentEntityConfirmFormBase {

  protected $cache;
  protected $currentPath;

  /**
   * {@inheritdoc}
   *
   *   The database connection.
   */
  public function __construct($cache, $currentPath) {
    $this->cache = $cache;
    $this->currentPath = $currentPath;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('cache.default'),
        $container->get('path.current')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete entity %name?', ['%name' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the contact list.
   */
  public function getCancelUrl() {
    return new Url('entity.domain_route_meta_tags.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the entity and log the event. log() replaces the watchdog.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $path = $this->getEntity()->get('route_link')->value;
    $cacheKey = str_replace('/', '_', $path);
    $cache = $this->cache->get($cacheKey);
    if ($cache) {
      $this->cache->delete($cacheKey);
    }
    $entity = $this->getEntity();
    $entity->delete();

    \Drupal::logger('domain_route_meta_tags')->notice('@type: deleted %title.',
      [
        '@type' => $this->entity->bundle(),
        '%title' => $this->entity->id(),
      ]);
    $form_state->setRedirect('entity.domain_route_meta_tags.collection');
  }

}
