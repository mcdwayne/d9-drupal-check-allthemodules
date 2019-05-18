<?php

namespace Drupal\cacheflush_ui\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a cacheflush deletion confirmation form.
 */
class CacheflushDeleteMultiple extends ConfirmFormBase {

  /**
   * The array of cacheflush entities to delete.
   *
   * @var string[][]
   */
  protected $cacheflushInfo = [];

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The cacheflush storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $manager;

  /**
   * Constructs a CacheflushDeleteMultiple form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('cacheflush');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'), $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cacheflush_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->cacheflushInfo), 'Are you sure you want to delete this item?', 'Are you sure you want to delete these items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.cacheflush.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $this->cacheflushInfo = $this->tempStoreFactory->get('cacheflush_multiple_delete_confirm')
      ->get(\Drupal::currentUser()->id());

    if (empty($this->cacheflushInfo)) {
      return new RedirectResponse(
        $this->getCancelUrl()
          ->setAbsolute()
          ->toString()
      );
    }

    $entities = $this->storage->loadMultiple(array_keys($this->cacheflushInfo));

    $items = [];
    foreach ($this->cacheflushInfo as $id => $langcodes) {
      foreach ($langcodes as $langcode) {
        $entity = $entities[$id]->getTranslation($langcode);
        $key = $id . ':' . $langcode;
        $default_key = $id . ':' . $entity->getUntranslated()->language()
          ->getId();

        // If we have a translated entity we build a nested list of translations
        // that will be deleted.
        $languages = $entity->getTranslationLanguages();
        if (count($languages) > 1 && $entity->isDefaultTranslation()) {
          $names = [];
          foreach ($languages as $translation_langcode => $language) {
            $names[] = $language->getName();
            unset($items[$id . ':' . $translation_langcode]);
          }
          $items[$default_key] = [
            'label' => [
              '#markup' => $this->t('@label (Original translation) - <em>The following content translations will be deleted:</em>', ['@label' => $entity->label()]),
            ],
            'deleted_translations' => [
              '#theme' => 'item_list',
              '#items' => $names,
            ],
          ];
        }
        elseif (!isset($items[$default_key])) {
          $items[$key] = $entity->label();
        }
      }
    }

    $form['cacheflush_items'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->cacheflushInfo)) {
      $total_count = 0;
      $delete_entities = [];

      $delete_translations = [];
      $entities = $this->storage->loadMultiple(array_keys($this->cacheflushInfo));

      foreach ($this->cacheflushInfo as $id => $langcodes) {
        foreach ($langcodes as $langcode) {
          $entity = $entities[$id]->getTranslation($langcode);
          if ($entity->isDefaultTranslation()) {
            $delete_entities[$id] = $entity;
            unset($delete_translations[$id]);
            $total_count += count($entity->getTranslationLanguages());
          }
          elseif (!isset($delete_entities[$id])) {
            $delete_translations[$id][] = $entity;
          }
        }
      }

      if ($delete_entities) {
        $this->storage->delete($delete_entities);
        $this->logger('cacheflush')
          ->notice('Deleted @count cacheflush entities.', ['@count' => count($delete_entities)]);
      }

      if ($delete_translations) {
        $count = 0;
        foreach ($delete_translations as $id => $translations) {
          $entity = $entities[$id]->getUntranslated();
          foreach ($translations as $translation) {
            $entity->removeTranslation($translation->language()->getId());
          }
          $entity->save();
          $count += count($translations);
        }
        if ($count) {
          $total_count += $count;
          $this->logger('cacheflush')
            ->notice('Deleted @count cacheflush translations.', ['@count' => $count]);
        }
      }

      if ($total_count) {
        drupal_set_message($this->formatPlural($total_count, 'Deleted 1 cacheflush entity.', 'Deleted @count cacheflush entities.'));
      }

      $this->tempStoreFactory->get('cacheflush_multiple_delete_confirm')
        ->delete(\Drupal::currentUser()->id());
    }

    $form_state->setRedirect('entity.cacheflush.collection');
  }

}
