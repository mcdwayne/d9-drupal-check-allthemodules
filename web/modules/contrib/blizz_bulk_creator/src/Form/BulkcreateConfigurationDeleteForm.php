<?php

namespace Drupal\blizz_bulk_creator\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\blizz_bulk_creator\Entity\BulkcreateUsageInterface;
use Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BulkcreateConfigurationDeleteForm.
 *
 * Provides a form that is displayed when the delete operation
 * is requested for bulkcreate_configuration entities.
 *
 * @package Drupal\blizz_bulk_creator\Form
 */
class BulkcreateConfigurationDeleteForm extends EntityConfirmFormBase {

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
   * Custom service containing methods for configuration handling.
   *
   * @var \Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelperInterface
   */
  protected $adminHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.channel.blizz_bulk_creator'),
      $container->get('current_user'),
      $container->get('blizz_bulk_creator.administration_helper')
    );
  }

  /**
   * BulkcreateConfigurationDeleteForm constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Our custom logger channel.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The user accessing this form.
   * @param \Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelperInterface $admin_helper
   *   Custom service containing administrative methods.
   */
  public function __construct(
    LoggerChannelInterface $logger,
    AccountInterface $current_user,
    BulkcreateAdministrationHelperInterface $admin_helper
  ) {
    $this->logger = $logger;
    $this->currentUser = $current_user;
    $this->adminHelper = $admin_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t(
      'Are you sure you want to delete the bulkcreate configuration "%name"?',
      [
        '%name' => $this->entity->label(),
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('blizz_bulk_creator.bulkcreate_configuration.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {

    if (empty($usages = $this->adminHelper->getBulkcreateUsages($this->entity))) {
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
    else {

      $usages = sprintf(
        '<ul>%s</ul>',
        implode('', array_map(
          function (BulkcreateUsageInterface $usage) {
            return "<li>{$usage->label()}</li>";
          },
          $usages
        ))
      );

      return [
        'warning' => [
          '#type' => 'markup',
          '#markup' => sprintf(
            '<p>%s</p>',
            new TranslatableMarkup('This bulkcreate configuration cannot be deleted because it is in use on the following entity bundles:')
          ),
          '#prefix' => '<div>',
          '#suffix' => '</div>',
        ],
        'usages' => [
          '#type' => 'inline_template',
          '#template' => '<p>{{ markup | raw }}</p>',
          '#context' => ['markup' => $usages],
        ],
        'cancel' => ConfirmFormHelper::buildCancelLink($this, $this->getRequest()),
      ];

    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $this->logger->notice(
      'Bulkcreate configuration "%name" has been deleted by %user.',
      [
        '%name' => $this->entity->label(),
        '%user' => $this->currentUser->getDisplayName(),
      ]
    );
    drupal_set_message($this->t(
      'Bulkcreate configuration "%name" has been deleted.',
      [
        '%name' => $this->entity->label(),
      ]
    ));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
