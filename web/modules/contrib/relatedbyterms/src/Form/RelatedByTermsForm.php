<?php

namespace Drupal\relatedbyterms\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\relatedbyterms\RelatedByTermsServiceInterface;

/**
 * Related by terms configuration form class.
 */
class RelatedByTermsForm extends ConfigFormBase {

  /**
   * The Related By Terms service.
   *
   * @var \Drupal\relatedbyterms\RelatedByTermsServiceInterface
   */
  protected $relatedbytermsManager;

  /**
   * The Entity Display repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The Cache Tags Invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Class cronstuctor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    RelatedByTermsServiceInterface $relatedbyterms_manager,
    EntityDisplayRepositoryInterface $entity_display_repository,
    CacheTagsInvalidatorInterface $cache_tags_invalidator
  ) {

    $this->relatedbytermsManager = $relatedbyterms_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;

    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('relatedbyterms.manager'),
      $container->get('entity_display.repository'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'relatedbyterms_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['elements_displayed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of nodes'),
      '#default_value' => $this->relatedbytermsManager->getElementsDisplayed(),
      '#description' => $this->t('Number of nodes to show in the block. Use 0 for no limit.'),
    ];

    $form['display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Display type'),
      '#options' => $this->getViewModes(),
      '#required' => TRUE,
      '#default_value' => $this->relatedbytermsManager->getDisplayMode(),
      '#description' => $this->t('This is the view mode that will be used to render the elements in the block.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save data.
    $this->relatedbytermsManager->setElementsDisplayed($form_state->getValue('elements_displayed'));
    $this->relatedbytermsManager->setDisplayMode($form_state->getValue('display_mode'));

    // After a settings change, invalidate the cache for this block.
    $this->cacheTagsInvalidator->invalidateTags(['config:block.block.relatedbytermsblock']);

    return parent::submitForm($form, $form_state);
  }

  /**
   * Get a list of view modes.
   */
  protected function getViewModes() {
    $node_view_modes = $this->entityDisplayRepository->getViewModes('node');
    $view_modes = [];

    foreach ($node_view_modes as $key => $node_view) {
      $view_modes[$key] = $node_view['label'];
    }

    return $view_modes;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'relatedbyterms.settings',
    ];
  }

}
