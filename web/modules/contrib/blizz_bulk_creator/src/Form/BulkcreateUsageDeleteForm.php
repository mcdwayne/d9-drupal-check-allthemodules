<?php

namespace Drupal\blizz_bulk_creator\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BulkcreateUsageDeleteForm.
 *
 * Provides a form that is displayed when the delete operation
 * is requested for bulkcreate_usage entities.
 *
 * @package Drupal\blizz_bulk_creator\Form
 */
class BulkcreateUsageDeleteForm extends EntityConfirmFormBase {

  /**
   * The custom logger channel for this module.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Custom service containing methods for handling bulkcreate configurations.
   *
   * @var \Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelperInterface
   */
  protected $adminHelper;

  /**
   * Drupal's cachetag invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagInvalidator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.channel.blizz_bulk_creator'),
      $container->get('current_user'),
      $container->get('blizz_bulk_creator.administration_helper'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * BulkcreateUsageDeleteForm constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The custom logger channel for this module.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The user accessing this form.
   * @param \Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelperInterface $admin_helper
   *   Custom service containing administrative methods.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tag_invalidator
   *   Drupal's cachetag invalidator service.
   */
  public function __construct(
    LoggerChannelInterface $logger,
    AccountInterface $current_user,
    BulkcreateAdministrationHelperInterface $admin_helper,
    CacheTagsInvalidatorInterface $cache_tag_invalidator
  ) {
    $this->logger = $logger;
    $this->currentUser = $current_user;
    $this->adminHelper = $admin_helper;
    $this->cacheTagInvalidator = $cache_tag_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t(
      'Are you sure you want to delete the bulkcreate usage "%name"?',
      [
        '%name' => $this->entity->label(),
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('blizz_bulk_creator.bulkcreate_usage.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete usage');
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {

    return [
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->getConfirmText(),
        '#submit' => [
          [$this, 'submitForm'],
        ],
      ],
      'cancel' => ConfirmFormHelper::buildCancelLink($this, $this->getRequest()),
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Delete the usage.
    $this->entity->delete();

    // Invalidate the caches containing base field information.
    $this->cacheTagInvalidator->invalidateTags(['entity_field_info']);

    // Log to watchdog (or whereever).
    $this->logger->notice(
      'Bulkcreate usage "%name" has been deleted by %user.',
      [
        '%name' => $this->entity->label(),
        '%user' => $this->currentUser->getDisplayName(),
      ]
    );

    // Set a notice on the frontend.
    drupal_set_message($this->t(
      'Bulkcreate usage "%name" has been deleted.',
      [
        '%name' => $this->entity->label(),
      ]
    ));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
