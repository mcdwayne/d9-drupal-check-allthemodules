<?php

namespace Drupal\entity_gallery\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides an entity gallery deletion confirmation form.
 */
class DeleteMultiple extends ConfirmFormBase {

  /**
   * The array of entity galleries to delete.
   *
   * @var string[][]
   */
  protected $entityGalleryInfo = array();

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The entity gallery storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $manager;

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityManagerInterface $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('entity_gallery');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_gallery_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->entityGalleryInfo), 'Are you sure you want to delete this item?', 'Are you sure you want to delete these items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('system.admin_content');
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
    $this->entityGalleryInfo = $this->tempStoreFactory->get('entity_gallery_multiple_delete_confirm')->get(\Drupal::currentUser()->id());
    if (empty($this->entityGalleryInfo)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }
    /** @var \Drupal\entity_gallery\EntityGalleryInterface[] $entity_galleries */
    $entity_galleries = $this->storage->loadMultiple(array_keys($this->entityGalleryInfo));

    $items = [];
    foreach ($this->entityGalleryInfo as $id => $langcodes) {
      foreach ($langcodes as $langcode) {
        $entity_gallery = $entity_galleries[$id]->getTranslation($langcode);
        $key = $id . ':' . $langcode;
        $default_key = $id . ':' . $entity_gallery->getUntranslated()->language()->getId();

        // If we have a translated entity we build a nested list of translations
        // that will be deleted.
        $languages = $entity_gallery->getTranslationLanguages();
        if (count($languages) > 1 && $entity_gallery->isDefaultTranslation()) {
          $names = [];
          foreach ($languages as $translation_langcode => $language) {
            $names[] = $language->getName();
            unset($items[$id . ':' . $translation_langcode]);
          }
          $items[$default_key] = [
            'label' => [
              '#markup' => $this->t('@label (Original translation) - <em>The following content translations will be deleted:</em>', ['@label' => $entity_gallery->label()]),
            ],
            'deleted_translations' => [
              '#theme' => 'item_list',
              '#items' => $names,
            ],
          ];
        }
        elseif (!isset($items[$default_key])) {
          $items[$key] = $entity_gallery->label();
        }
      }
    }

    $form['entity_galleries'] = array(
      '#theme' => 'item_list',
      '#items' => $items,
    );
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->entityGalleryInfo)) {
      $total_count = 0;
      $delete_entity_galleries = [];
      /** @var \Drupal\Core\Entity\ContentEntityInterface[][] $delete_translations */
      $delete_translations = [];
      /** @var \Drupal\entity_gallery\EntityGalleryInterface[] $entity_galleries */
      $entity_galleries = $this->storage->loadMultiple(array_keys($this->entityGalleryInfo));

      foreach ($this->entityGalleryInfo as $id => $langcodes) {
        foreach ($langcodes as $langcode) {
          $entity_gallery = $entity_galleries[$id]->getTranslation($langcode);
          if ($entity_gallery->isDefaultTranslation()) {
            $delete_entity_galleries[$id] = $entity_gallery;
            unset($delete_translations[$id]);
            $total_count += count($entity_gallery->getTranslationLanguages());
          }
          elseif (!isset($delete_entity_galleries[$id])) {
            $delete_translations[$id][] = $entity_gallery;
          }
        }
      }

      if ($delete_entity_galleries) {
        $this->storage->delete($delete_entity_galleries);
        $this->logger('content')->notice('Deleted @count posts.', array('@count' => count($delete_entity_galleries)));
      }

      if ($delete_translations) {
        $count = 0;
        foreach ($delete_translations as $id => $translations) {
          $entity_gallery = $entity_galleries[$id]->getUntranslated();
          foreach ($translations as $translation) {
            $entity_gallery->removeTranslation($translation->language()->getId());
          }
          $entity_gallery->save();
          $count += count($translations);
        }
        if ($count) {
          $total_count += $count;
          $this->logger('content')->notice('Deleted @count content translations.', array('@count' => $count));
        }
      }

      if ($total_count) {
        drupal_set_message($this->formatPlural($total_count, 'Deleted 1 post.', 'Deleted @count posts.'));
      }

      $this->tempStoreFactory->get('entity_gallery_multiple_delete_confirm')->delete(\Drupal::currentUser()->id());
    }

    $form_state->setRedirect('system.admin_content');
  }

}
