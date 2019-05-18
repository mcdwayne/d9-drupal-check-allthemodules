<?php

namespace Drupal\broken_link\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a brokenLink deletion confirmation form.
 */
class BrokenLinkDeleteMultipleForm extends ConfirmFormBase {

  /**
   * The array of brokenLinks to delete.
   *
   * @var string[][]
   */
  protected $brokenLinkInfo = [];

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The brokenLink storage.
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
    $this->storage = $manager->getStorage('broken_link');
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
    return 'broken_link_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->brokenLinkInfo), 'Are you sure you want to delete this item?', 'Are you sure you want to delete these items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.broken_link.collection');
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
    $this->brokenLinkInfo = $this->tempStoreFactory->get('broken_link_multiple_delete_confirm')->get(\Drupal::currentUser()->id());
    if (empty($this->brokenLinkInfo)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }
    /** @var \Drupal\brokenLink\NodeInterface[] $brokenLinks */
    $brokenLinks = $this->storage->loadMultiple(array_keys($this->brokenLinkInfo));

    $items = [];
    foreach ($this->brokenLinkInfo as $id => $langcodes) {
      foreach ($langcodes as $langcode) {
        $brokenLink = $brokenLinks[$id]->getTranslation($langcode);
        $key = $id . ':' . $langcode;
        $default_key = $id . ':' . $brokenLink->getUntranslated()->language()->getId();

        // If we have a translated entity we build a nested list of translations
        // that will be deleted.
        $languages = $brokenLink->getTranslationLanguages();
        if (count($languages) > 1 && $brokenLink->isDefaultTranslation()) {
          $names = [];
          foreach ($languages as $translation_langcode => $language) {
            $names[] = $language->getName();
            unset($items[$id . ':' . $translation_langcode]);
          }
          $items[$default_key] = [
            'label' => [
              '#markup' => $this->t('@label (Original translation) - <em>The following content translations will be deleted:</em>', ['@label' => $brokenLink->get('link')->value]),
            ],
            'deleted_translations' => [
              '#theme' => 'item_list',
              '#items' => $names,
            ],
          ];
        }
        elseif (!isset($items[$default_key])) {
          $items[$key] = $brokenLink->get('link')->value;
        }
      }
    }

    $form['brokenLinks'] = [
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
    if ($form_state->getValue('confirm') && !empty($this->brokenLinkInfo)) {
      $total_count = 0;
      $delete_brokenLinks = [];
      /** @var \Drupal\Core\Entity\ContentEntityInterface[][] $delete_translations */
      $delete_translations = [];
      /** @var \Drupal\brokenLink\NodeInterface[] $brokenLinks */
      $brokenLinks = $this->storage->loadMultiple(array_keys($this->brokenLinkInfo));

      foreach ($this->brokenLinkInfo as $id => $langcodes) {
        foreach ($langcodes as $langcode) {
          $brokenLink = $brokenLinks[$id]->getTranslation($langcode);
          if ($brokenLink->isDefaultTranslation()) {
            $delete_brokenLinks[$id] = $brokenLink;
            unset($delete_translations[$id]);
            $total_count += count($brokenLink->getTranslationLanguages());
          }
          elseif (!isset($delete_brokenLinks[$id])) {
            $delete_translations[$id][] = $brokenLink;
          }
        }
      }

      if ($delete_brokenLinks) {
        $this->storage->delete($delete_brokenLinks);
        $this->logger('content')->notice('Deleted @count posts.', ['@count' => count($delete_brokenLinks)]);
      }

      if ($delete_translations) {
        $count = 0;
        foreach ($delete_translations as $id => $translations) {
          $brokenLink = $brokenLinks[$id]->getUntranslated();
          foreach ($translations as $translation) {
            $brokenLink->removeTranslation($translation->language()->getId());
          }
          $brokenLink->save();
          $count += count($translations);
        }
        if ($count) {
          $total_count += $count;
          $this->logger('content')->notice('Deleted @count content translations.', ['@count' => $count]);
        }
      }

      if ($total_count) {
        drupal_set_message($this->formatPlural($total_count, 'Deleted 1 post.', 'Deleted @count posts.'));
      }

      $this->tempStoreFactory->get('brokenLink_multiple_delete_confirm')->delete(\Drupal::currentUser()->id());
    }

    $form_state->setRedirect('system.admin_content');
  }

}
