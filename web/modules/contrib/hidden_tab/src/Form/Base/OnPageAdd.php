<?php
/**
 * Created by PhpStorm.
 * User: milan
 * Date: 2/5/19
 * Time: 11:35 AM
 */

namespace Drupal\hidden_tab\Form\Base;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hidden_tab\Controller\XPageRenderController;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\FUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class OnPageAdd extends FormBase {

  /**
   * Form id.
   *
   * @var string
   */
  protected $ID;

  /**
   * Form element items prefix.
   *
   * @var string
   */
  protected $prefix;

  /**
   * Entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * Entity type for the form.
   *
   * @var string
   */
  protected static $type;

  /**
   * Entity storage of entity type of the form.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * UUID!
   *
   * @var \Drupal\Component\Uuid\Php
   */
  protected $uuid;

  /**
   * OnPageAdd constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   See $this->entityStorage.
   * @param \Drupal\Component\Uuid\Php $uuid
   *   See $this->uuid.
   */
  public function __construct(EntityStorageInterface $entity_storage, Php $uuid) {
    $this->entityStorage = $entity_storage;
    $this->uuid = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('entity_type.manager')->getStorage(static::$type),
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public final function getFormId(): string {
    return $this->ID;
  }

  /**
   * {@inheritdoc}
   */
  public final function buildForm(array $form,
                                  FormStateInterface $form_state,
                                  EntityInterface $target_entity = NULL,
                                  HiddenTabPageInterface $page = NULL) {
    if ($page === NULL) {
      throw new \LogicException('illegal state, page entity not given');
    }
    if ($target_entity === NULL) {
      throw new \LogicException('illegal state, target entity not given');
    }

    $f = [];
    foreach ($this->getFormElements($target_entity, $page) as $key => $item) {
      $f[$this->prefix . $key] = $item;
    }

    $f[$this->prefix . 'target_entity'] = [
      '#type' => 'value',
      '#value' => $target_entity->id(),
    ];

    $f[$this->prefix . 'target_entity_type'] = [
      '#type' => 'value',
      '#value' => $target_entity->getEntityTypeId(),
    ];

    $f[$this->prefix . 'target_hidden_tab_page'] = [
      '#type' => 'value',
      '#value' => $page->id(),
    ];

    $f[$this->prefix . 'actions'][$this->prefix . 'save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
    ];

    $form[$this->prefix . 'add_new_fieldset'] = [
      '#type' => 'details',
      '#open' => count($form_state->getErrors()),
      '#title' => $this->t('New @label', ['@label' => $this->label]),
    ];
    $form[$this->prefix . 'add_new_fieldset'][$this->prefix . 'add_new'] = $f;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public final function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entityStorage
      ->create(FUtility::extractRefrencerValues($form_state, $this->prefix)
        + $this->getValues($form_state))
      ->save();

    $this->messenger()
      ->addStatus($this->t('@label added.', ['@label' => $this->label]));

    $_SESSION[XPageRenderController::ADMIN_FS_OPEN] = FALSE;

    $form_state->disableRedirect();
  }

  protected abstract function getFormElements(EntityInterface $target_entity,
                                              HiddenTabPageInterface $page): array;

  /**
   * Entity values to create the entity.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   To extract entity values from.
   *
   * @return array
   *   Entity values.
   */
  protected abstract function getValues(FormStateInterface $form_state): array;

}
