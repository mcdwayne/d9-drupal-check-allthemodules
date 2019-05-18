<?php

namespace Drupal\fragments\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Render\Element;
use Drupal\auto_entitylabel\EntityDecoratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for fragment edit forms.
 *
 * @ingroup fragments
 */
class FragmentForm extends ContentEntityForm {

  use MessengerTrait;

  /**
   * Auto Entity Label Manager object wrapping the current entity.
   *
   * If available.
   *
   * @var \Drupal\auto_entitylabel\AutoEntityLabelManagerInterface
   */
  private $autoEntityLabelManager;

  /**
   * Auto Entity Label Entity Decorator.
   *
   * If available.
   *
   * @var \Drupal\auto_entitylabel\EntityDecoratorInterface
   */
  private $autoLabelEntityDecorator;

  /**
   * Constructs a FragmentForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\auto_entitylabel\EntityDecoratorInterface $autoEntityLabelDecorator
   *   The auto entity label decorator service, if available.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, EntityDecoratorInterface $autoEntityLabelDecorator = NULL) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->autoLabelEntityDecorator = $autoEntityLabelDecorator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('auto_entitylabel.entity_decorator', ContainerInterface::NULL_ON_INVALID_REFERENCE)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\fragments\Entity\Fragment */
    $form = parent::buildForm($form, $form_state);

    // Start a new revision by default.
    $form['revision']['#default_value'] = TRUE;

    if ($this->hasAutoLabel()) {
      // We don't need to show the label input.
      $form['title']['#access'] = FALSE;
    }
    elseif ($this->hasOptionalAutoLabel()) {
      // The auto label is optional, so the title should not be required;
      // when empty, Auto Entity Label will create a title.
      foreach (Element::children($form['title']['widget']) as $child) {
        $form['title']['widget'][$child]['value']['#required'] = FALSE;
        $form['title']['widget'][$child]['value']['#description'] = $this->t('Leave empty to automatically generate a title.');
      }
    }

    // Put publishing status in a vertical tab.
    $form['publishing_status'] = [
      '#type' => 'details',
      '#title' => $this->t('Publishing status'),
      '#open' => FALSE,
      '#group' => 'advanced',
      '#weight' => 30,
      '#attached' => ['library' => ['fragments/form']],
      '#attributes' => [
        'class' => ['fragment-form-publishing-status'],
      ],
    ];
    $form['status']['#group'] = 'publishing_status';

    // Put author in a vertical tab.
    $form['authoring_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Authoring information'),
      '#open' => FALSE,
      '#group' => 'advanced',
      '#weight' => 40,
      '#attached' => ['library' => ['fragments/form']],
      '#attributes' => [
        'class' => ['fragment-form-authoring-information'],
      ],
    ];
    $form['user_id']['#group'] = 'authoring_information';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('revision') && $form_state->getValue('revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    $replacements = [
      '%label' => $entity->label(),
    ];
    switch ($status) {
      case SAVED_NEW:
        $message = $this->t('Created the %label fragment.', $replacements);
        break;

      default:
        $message = $this->t('Saved the %label fragment.', $replacements);
    }
    $this->messenger()->addMessage($message);

    $form_state->setRedirect('entity.fragment.collection');
  }

  /**
   * Lazily loads the auto entity label manager for this object, if it exists.
   */
  private function autoEntityLabelManager() {
    if (is_null($this->autoEntityLabelManager)) {
      if (!is_null($this->autoLabelEntityDecorator)) {
        $this->autoEntityLabelManager = $this->autoLabelEntityDecorator->decorate($this->entity);
      }
      else {
        $this->autoEntityLabelManager = FALSE;
      }
    }

    return $this->autoEntityLabelManager;
  }

  /**
   * Wraps AutoEntityLabelManager::hasAutoLabel().
   */
  private function hasAutoLabel() {
    $autoEntityLabelManager = $this->autoEntityLabelManager();

    if ($autoEntityLabelManager) {
      return $autoEntityLabelManager->hasAutoLabel();
    }

    return FALSE;
  }

  /**
   * Wraps AutoEntityLabelManager::hasOptionalAutoLabel().
   */
  private function hasOptionalAutoLabel() {
    $autoEntityLabelManager = $this->autoEntityLabelManager();

    if ($autoEntityLabelManager) {
      return $autoEntityLabelManager->hasOptionalAutoLabel();
    }

    return FALSE;
  }


}
