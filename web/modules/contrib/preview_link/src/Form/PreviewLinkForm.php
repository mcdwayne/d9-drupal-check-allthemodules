<?php

namespace Drupal\preview_link\Form;


use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Preview link form.
 */
class PreviewLinkForm extends ContentEntityForm {

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'preview_link_entity_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    /** @var \Drupal\preview_link\PreviewLinkStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('preview_link');
    $related_entity = $this->getRelatedEntity();
    if (!$preview_link = $storage->getPreviewLink($related_entity)) {
      $preview_link = $storage->createPreviewLinkForEntity($related_entity);
    }
    return $preview_link;
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['preview_link'] = [
      '#theme' => 'preview_link',
      '#title' => $this->t('Preview link'),
      '#link' => $this->entity
        ->getUrl()
        ->setAbsolute()
        ->toString(),
    ];

    $form['actions']['submit']['#value'] = $this->t('Re-generate preview link');

    return $form;
  }

  /**
   * Attempts to load the entity this preview link will be related to.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The content entity interface.
   *
   * @throws \InvalidArgumentException
   *   Only thrown if we cannot detect the related entity.
   */
  protected function getRelatedEntity() {
    $entity = NULL;
    $entity_type_ids = array_keys($this->entityTypeManager->getDefinitions());

    foreach ($entity_type_ids as $entity_type_id) {
      if ($entity = \Drupal::request()->attributes->get($entity_type_id)) {
        break;
      }
    }

    if (!$entity) {
      throw new \InvalidArgumentException('Something went very wrong');
    }

    return $entity;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->regenerateToken(TRUE);
    drupal_set_message($this->t('The token has been re-generated.'));

  }
}
