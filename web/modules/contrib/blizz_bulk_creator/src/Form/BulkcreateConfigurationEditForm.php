<?php

namespace Drupal\blizz_bulk_creator\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\blizz_bulk_creator\Services\EntityHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BulkcreateConfigurationEditForm.
 *
 * Provides the basic config-entity edit form for bulkcreate configurations.
 *
 * @package Drupal\blizz_bulk_creator\Form
 */
class BulkcreateConfigurationEditForm extends EntityForm {

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
   * Custom service to ease the handling of media entities.
   *
   * @var \Drupal\blizz_bulk_creator\Services\EntityHelperInterface
   */
  protected $entityHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.channel.blizz_bulk_creator'),
      $container->get('current_user'),
      $container->get('blizz_bulk_creator.entity_helper')
    );
  }

  /**
   * BulkcreateConfigurationEditForm constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The custom logger channel for this module.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The user accessing this form.
   * @param \Drupal\blizz_bulk_creator\Services\EntityHelperInterface $entity_helper
   *   Custom service to ease the handling of media entities.
   */
  public function __construct(
    LoggerChannelInterface $logger,
    AccountInterface $current_user,
    EntityHelperInterface $entity_helper
  ) {
    $this->logger = $logger;
    $this->currentUser = $current_user;
    $this->entityHelper = $entity_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    // Adjust the title.
    $form['#title'] = $this->t(
      'Edit bulkcreation configuration %name',
      ['%name' => $this->entity->label()]
    );

    // Should the generated entity names be customizable?
    $form['custom_entity_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Custom naming'),
      '#description' => $this->t('Do you want to provide a custom name for the bulk-created entities?'),
      '#default_value' => $this->entity->get('custom_entity_name'),
    ];

    // Provide checkboxes for the default value providers.
    $form['defaultValueProviders'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Default value providers'),
      '#description' => $this->t('Please select the field you wish to pre-fill with default data when using this bulkcreation configuration.'),
      '#options' => array_filter(
        $this->entityHelper->getBundleFieldOptions('media', $this->entity->get('target_bundle')),
        function ($machine_name) {
          return $machine_name != $this->entity->get('bulkcreate_field');
        },
        ARRAY_FILTER_USE_KEY
      ),
      '#default_value' => array_combine(
        $this->entity->get('default_values'),
        $this->entity->get('default_values')
      ),
    ];

    // Return the form.
    return parent::form($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return [
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save changes'),
        '#submit' => [
          [$this, 'save'],
        ],
      ],
      'cancel' => [
        '#type' => 'link',
        '#title' => $this->t('Cancel'),
        '#attributes' => ['class' => ['button']],
        '#url' => new Url('blizz_bulk_creator.bulkcreate_configuration.list'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    // Set the newly configured settings.
    $this->entity->set(
      'custom_entity_name',
      (bool) $form_state->getValue('custom_entity_name')
    );
    $this->entity->set(
      'default_values',
      array_keys(
        array_filter($form_state->getValue('defaultValueProviders'))
      )
    );

    // Save the entity.
    $this->entity->save();

    // Set a message on the frontend.
    drupal_set_message($this->t(
      'The Bulkcreation configuration "%label" has been updated.',
      ['%label' => $this->entity->label()]
    ));

    // Log a notice to watchdog (or whereever).
    $this->logger->notice(
      'The Bulkcreation configuration "%label" has been updated by user %user.',
      [
        '%label' => $this->entity->label(),
        '%user' => $this->currentUser->getDisplayName(),
      ]
    );

    // Redirect back to the list view.
    $form_state->setRedirect('blizz_bulk_creator.bulkcreate_configuration.list');

  }

}
