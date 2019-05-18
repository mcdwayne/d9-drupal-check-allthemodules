<?php

namespace Drupal\mailing_list\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\mailing_list\MailingListManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;

/**
 * Mailing list subscription blocks.
 *
 * @Block(
 *   id = "mailing_list_subscription_block",
 *   admin_label = @Translation("Mailing list subscription"),
 *   category = @Translation("Mailing list")
 * )
 */
class SubscriptionBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The mailing list manager.
   *
   * @var \Drupal\mailing_list\MailingListManagerInterface
   */
  protected $mailingListManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a SubscriptionBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\FormBuilderInterface $form_builder
   *   The form builder object.
   * @param \Drupal\mailing_list\MailingListManagerInterface $mailing_list_manager
   *   The mailing list manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder, MailingListManagerInterface $mailing_list_manager, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->mailingListManager = $mailing_list_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
      $container->get('mailing_list.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'list' => '',
      'message' => '',
      'form_id' => '',
      'manage_link' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    // No list, no access opinion.
    if (empty($this->configuration['list'])) {
      return AccessResult::neutral();
    }

    // Users must be able to subscribe to all configured mailing lists.
    return AccessResult::allowedIfHasPermission($account, 'subscribe to ' . $this->configuration['list'] . ' mailing list');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Mailing list for this subscription block.
    $options = [];
    foreach ($this->entityTypeManager->getStorage('mailing_list')->loadMultiple() as $list) {
      $options[$list->id()] = $list->label();
    }
    if (count($options) > 1) {
      asort($options, SORT_STRING);
    }

    $form['manage_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show manage subscriptions link'),
      '#default_value' => $this->configuration['manage_link'],
    ];

    $form['list'] = [
      '#type' => 'select',
      '#title' => $this->t('Mailing list'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $this->configuration['list'] ?: key($options),
    ];

    // Block message.
    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Block message'),
      '#size' => 60,
      '#maxlength' => 255,
      '#description' => $this->t('Message to the user. Leave empty for display the mailing list configured help. Enter @none for no message at all.', ['@none' => '<none>']),
      '#default_value' => $this->configuration['message'],
    ];

    // Subscription form ID.
    $form['form_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form ID'),
      '#field_prefix' => 'mailing_list_subscription_&lt;LIST-ID&gt;_',
      '#field_suffix' => '_block_form',
      '#size' => 16,
      '#maxlength' => 32,
      '#description' => $this->t('Customize the subscription form ID.'),
      '#default_value' => $this->configuration['form_id'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if (!preg_match('/^[a-z0-9_]*$/', $form_state->getValue('form_id'))) {
      $form_state->setErrorByName('form_id', $this->t('A unique machine-readable name containing letters, numbers, and underscores.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['list'] = $form_state->getValue('list');
    $this->configuration['message'] = $form_state->getValue('message');
    $this->configuration['form_id'] = $form_state->getValue('form_id');
    $this->configuration['manage_link'] = $form_state->getValue('manage_link');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\mailing_list\Form\SubscriptionForm $form_object */
    $form_object = $this->entityTypeManager->getFormObject('mailing_list_subscription', 'block');

    // Add message.
    $form_object->setMessage($this->configuration['message']);

    // Alter form ID.
    $form_object->setCustomId($this->configuration['form_id']);

    // Set a new subscription entity as the entity form.
    /** @var \Drupal\mailing_list\SubscriptionInterface $entity */
    $entity = $this->entityTypeManager->getStorage('mailing_list_subscription')->create([
      'mailing_list' => $this->configuration['list'],
      'email' => $this->currentUser->getEmail(),
    ]);
    $form_object->setEntity($entity);

    // Add the form destination.
    $form_object->setFormDestination($entity->getList()->getFormDestination());

    // Alter the default form submit button.
    $form = $this->formBuilder->getForm($form_object);
    if ($form['actions']['submit']['#value'] == $this->t('Save')->render()) {
      $form['actions']['submit']['#value'] = $this->t('Subscribe');
    }

    // Remove admin fields groups.
    unset($form['advanced']);
    unset($form['subscription_authoring']);
    unset($form['subscription_status']);

    // Block title is taken from the form title.
    if (isset($this->configuration['label']) && $block_title = trim($this->configuration['label'])) {
      $form['#title'] = $block_title;
    }

    // Add manage subscription link.
    if ($this->configuration['manage_link']) {
      $manage_url = Url::fromRoute('entity.mailing_list_subscription.manage');
      if ($manage_url->access()) {
        $form['manage_link'] = [
          '#type' => 'link',
          '#title' => $this->t('Manage your subscriptions'),
          '#url' => $manage_url,
        ];
      }
    }

    return $form;
  }

}
