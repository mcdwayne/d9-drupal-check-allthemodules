<?php

namespace Drupal\box\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a box deletion confirmation form.
 */
class BoxDeleteMultipleForm extends ConfirmFormBase {

  // @todo: use EntityDeleteMultipleForm with Drupal 8.6

  /**
   * The array of boxes to delete.
   *
   * @var string[][]
   */
  protected $boxInfo = [];

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The box storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a BoxDeleteMultipleForm form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityManagerInterface $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('box');
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
    return 'box_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->boxInfo), 'Are you sure you want to delete this box?', 'Are you sure you want to delete these boxes?');
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
    $this->boxInfo = $this->tempStoreFactory->get('box_multiple_delete_confirm')->get(\Drupal::currentUser()->id());
    if (empty($this->boxInfo)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }
    /** @var \Drupal\box\Entity\Box[] $boxes */
    $boxes = $this->storage->loadMultiple(array_keys($this->boxInfo));

    $items = [];
    foreach ($this->boxInfo as $id => $langcodes) {
      foreach ($langcodes as $langcode) {
        $box = $boxes[$id]->getTranslation($langcode);
        $key = $id . ':' . $langcode;
        $default_key = $id . ':' . $box->getUntranslated()->language()->getId();

        // If we have a translated entity we build a nested list of translations
        // that will be deleted.
        $languages = $box->getTranslationLanguages();
        if (count($languages) > 1 && $box->isDefaultTranslation()) {
          $names = [];
          foreach ($languages as $translation_langcode => $language) {
            $names[] = $language->getName();
            unset($items[$id . ':' . $translation_langcode]);
          }
          $items[$default_key] = [
            'label' => [
              '#markup' => $this->t('@label (Original translation) - <em>The following box translations will be deleted:</em>', ['@label' => $box->label()]),
            ],
            'deleted_translations' => [
              '#theme' => 'item_list',
              '#items' => $names,
            ],
          ];
        }
        elseif (!isset($items[$default_key])) {
          $items[$key] = $box->label();
        }
      }
    }

    $form['boxes'] = [
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
    if ($form_state->getValue('confirm') && !empty($this->boxInfo)) {
      $total_count = 0;
      $delete_boxes = [];
      /** @var \Drupal\Core\Entity\ContentEntityInterface[][] $delete_translations */
      $delete_translations = [];
      /** @var \Drupal\box\Entity\Box[] $boxes */
      $boxes = $this->storage->loadMultiple(array_keys($this->boxInfo));

      foreach ($this->boxInfo as $id => $langcodes) {
        foreach ($langcodes as $langcode) {
          $box = $boxes[$id]->getTranslation($langcode);
          if ($box->isDefaultTranslation()) {
            $delete_boxes[$id] = $box;
            unset($delete_translations[$id]);
            $total_count += count($box->getTranslationLanguages());
          }
          elseif (!isset($delete_boxes[$id])) {
            $delete_translations[$id][] = $box;
          }
        }
      }

      if ($delete_boxes) {
        $this->storage->delete($delete_boxes);
        $this->logger('box')->notice('Deleted @count boxes.', ['@count' => count($delete_boxes)]);
      }

      if ($delete_translations) {
        $count = 0;
        foreach ($delete_translations as $id => $translations) {
          $box = $boxes[$id]->getUntranslated();
          foreach ($translations as $translation) {
            $box->removeTranslation($translation->language()->getId());
          }
          $box->save();
          $count += count($translations);
        }
        if ($count) {
          $total_count += $count;
          $this->logger('box')->notice('Deleted @count box translations.', ['@count' => $count]);
        }
      }

      if ($total_count) {
        drupal_set_message($this->formatPlural($total_count, 'Deleted 1 box.', 'Deleted @count boxes.'));
      }

      $this->tempStoreFactory->get('box_multiple_delete_confirm')->delete(\Drupal::currentUser()->id());
    }

    $form_state->setRedirect('system.admin_content');
  }

}
