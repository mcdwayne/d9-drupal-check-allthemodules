<?php

namespace Drupal\blizz_bulk_creator\Form;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelperInterface;
use Drupal\blizz_bulk_creator\Services\EntityHelperInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigEntityMultistepFormBase.
 *
 * The base for the configuration entity add forms
 * providing for a multistep interface.
 *
 * @package Drupal\blizz_bulk_creator\Form
 */
abstract class ConfigEntityMultistepFormBase extends EntityForm {

  /**
   * The custom logger channel for this module.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Custom service to ease the handling of entities.
   *
   * @var \Drupal\blizz_bulk_creator\Services\EntityHelperInterface
   */
  protected $entityHelper;

  /**
   * Custom service to ease administrative tasks.
   *
   * @var \Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelperInterface
   */
  protected $administrationHelper;

  /**
   * Drupal's service to store session related data.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Drupal's UUID generator service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * Drupal's cachetag invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagInvalidator;

  /**
   * The data storage for the form steps.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $store;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.channel.blizz_bulk_creator'),
      $container->get('blizz_bulk_creator.entity_helper'),
      $container->get('blizz_bulk_creator.administration_helper'),
      $container->get('user.private_tempstore'),
      $container->get('uuid'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * ConfigEntityMultistepFormBase constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The custom logger channel for this module.
   * @param \Drupal\blizz_bulk_creator\Services\EntityHelperInterface $entity_helper
   *   Custom service to ease the handling of media entities.
   * @param \Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelperInterface $administration_helper
   *   Custom service to ease administrative tasks.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   Drupal's service to store session related data.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   Drupal's UUID generator service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tag_invalidator
   *   Drupal's cachetag invalidator service.
   */
  public function __construct(
    LoggerChannelInterface $logger,
    EntityHelperInterface $entity_helper,
    BulkcreateAdministrationHelperInterface $administration_helper,
    PrivateTempStoreFactory $temp_store_factory,
    UuidInterface $uuid_generator,
    CacheTagsInvalidatorInterface $cache_tag_invalidator
  ) {
    $this->logger = $logger;
    $this->entityHelper = $entity_helper;
    $this->administrationHelper = $administration_helper;
    $this->tempStoreFactory = $temp_store_factory;
    $this->uuidGenerator = $uuid_generator;
    $this->cacheTagInvalidator = $cache_tag_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    // Initialize the PrivateTempStore collection.
    $this->initializePrivateTempStoreCollection();

    // Get the actual step form.
    $form = $this->getStepForm($form, $form_state);

    // Pass this form to the parent.
    return parent::form($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    // Initialize the PrivateTempStore collection.
    $this->initializePrivateTempStoreCollection();

    // Call the step submit function.
    $this->submitStep($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    if (($backUrl = $this->getBackUrl()) !== FALSE) {
      $actions['back'] = [
        '#type' => 'link',
        '#title' => $this->t('Back'),
        '#attributes' => [
          'class' => ['button'],
        ],
        '#url' => $backUrl,
        '#cache' => [
          'contexts' => ['url.query_args:destination'],
        ],
      ];
    }
    if (!$this->hasSubmitAction()) {
      unset($actions['submit']);
    }
    elseif (!$this->isFinalStep()) {
      $actions['submit']['#value'] = $this->t('Next step');
    }
    $actions['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => [
        'class' => ['button'],
      ],
      '#url' => $this->getCancelUrl(),
      '#cache' => [
        'contexts' => ['url.query_args:destination'],
      ],
    ];
    return $actions;
  }

  /**
   * Saves data from a single step.
   */
  protected function saveData(array $data) {
    foreach ($data as $name => $value) {
      $this->store->set($name, $value);
    }
  }

  /**
   * Each single step has to implement the "getStepForm()" method.
   *
   * @param array $form
   *   The current form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The form potion of the current step.
   */
  abstract protected function getStepForm(array $form, FormStateInterface $form_state);

  /**
   * Each single step has to implement the "submitStep()" method.
   *
   * @param array $form
   *   The current form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  abstract protected function submitStep(array $form, FormStateInterface $form_state);

  /**
   * Each step has to return a bool indicating if it is the final step.
   *
   * @return bool
   *   TRUE if the current step is the last step.
   */
  abstract protected function isFinalStep();

  /**
   * Each step has to return a bool indicating if it is possible to submit.
   *
   * @return bool
   *   TRUE if a form submission is possible.
   */
  abstract protected function hasSubmitAction();

  /**
   * A step form may override this to provide a route to step back to.
   *
   * @return string
   *   The name of the route to return to.
   */
  protected function getBackUrl() {
    return FALSE;
  }

  /**
   * Each step must provide a URL to return to when the operation is cancelled.
   *
   * @return \Drupal\Core\Url
   *   The URL to which the user should get redirected
   *   when canceling the operation.
   */
  abstract protected function getCancelUrl();

  /**
   * Helper function to provide a container element.
   *
   * @param string $id
   *   The HTML ID the container should have.
   *
   * @return array
   *   An FAPI container element.
   */
  protected function getAjaxWrapperElement($id) {
    return [
      '#tree' => TRUE,
      '#type' => 'container',
      '#attributes' => [
        'id' => "{$id}-wrapper",
        'class' => [$id],
      ],
    ];
  }

  /**
   * Workaround for a PrivateTempStore bug.
   *
   * Somehow - please don't ask how, hours went into debugging - it is
   * seemingly impossible to correctly serialize instances of
   * PrivateTempStore. When the form gets cached, the fetched store
   * collection will get serialized. Upon retrieving this cache the
   * member variable "currentUser" in PrivateTempStore will be a string
   * instead of an AccountInterface. The easiest way of ommiting this
   * bug is to simply fetch the collection at the latest possible time.
   */
  private function initializePrivateTempStoreCollection() {
    $this->store = $this->tempStoreFactory->get('blizz_bulk_creator.stepdata');
  }

}
