<?php

namespace Drupal\competition\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a entity deletion confirmation form.
 */
class CompetitionEntryDeleteMultiple extends ConfirmFormBase {

  /**
   * The array of entities to delete.
   *
   * @var string[][]
   */
  protected $entityInfo = array();

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $manager;

  /**
   * Constructs a CompetitionEntryDeleteMultiple form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityManagerInterface $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('competition_entry');
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
    return 'competition_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->entityInfo), 'Are you sure you want to delete this item?', 'Are you sure you want to delete these items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.competition.collection');
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
    $this->entityInfo = $this->tempStoreFactory->get('competition_multiple_delete_confirm')->get(\Drupal::currentUser()->id());

    if (empty($this->entityInfo)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    /* @var \Drupal\core\Entity\EntityInterface[] $entities */
    $entities = $this->storage->loadMultiple(array_keys($this->entityInfo));

    $items = [];
    foreach ($this->entityInfo as $id => $langcodes) {
      foreach ($langcodes as $langcode) {
        $entity = $entities[$id]->getTranslation($langcode);
        $items[$id] = $this->t('Entry @label', ['@label' => $entity->label()]); ;
      }
    }

    $form['entities'] = array(
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
    if ($form_state->getValue('confirm') && !empty($this->entityInfo)) {
      $delete_entities = [];
      /* @var \Drupal\core\Entity\EntityInterface[] $entities */
      $entities = $this->storage->loadMultiple(array_keys($this->entityInfo));

      foreach ($this->entityInfo as $id => $langcodes) {
        foreach ($langcodes as $langcode) {

          $delete_entities[$id] = $entities[$id]->getTranslation($langcode);

        }
      }

      if ($delete_entities) {
        $this->storage->delete($delete_entities);
        $this->logger('content')->notice('Deleted @count competition entries.', array('@count' => count($delete_entities)));
        drupal_set_message($this->formatPlural(count($delete_entities), 'Deleted 1 competition entry.', 'Deleted @count competition entries.'));
      }

      $this->tempStoreFactory->get('competition_multiple_delete_confirm')->delete(\Drupal::currentUser()->id());
    }

    $form_state->setRedirect('entity.competition.collection');
  }

}
