<?php

namespace Drupal\formblock\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block for contact form.
 *
 * @Block(
 *   id = "formblock_contact",
 *   admin_label = @Translation("Site-wide contact form"),
 *   provider = "contact",
 *   category = @Translation("Forms")
 * )
 *
 * Note that we set module to contact so that blocks will be disabled correctly
 * when the module is disabled.
 */
class ContactFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface.
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface.
   */
  protected $currentUser;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface.
   */
  protected $entityFormBuilder;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface.
   */
  protected $configFactory;

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface.
   */
  protected $flood;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter.
   */
  protected $dateFormatter;

  /**
   * Constructs a new ContactFormBlock plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entityFormBuilder
   *   The entity form builder interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   * @param \Drupal\Core\DateTime\DateFormatter $dateFormatter
   *   The date formatter service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, AccountInterface $currentUser, EntityFormBuilderInterface $entityFormBuilder, ConfigFactoryInterface $configFactory, FloodInterface $flood, DateFormatter $dateFormatter) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->entityFormBuilder = $entityFormBuilder;
    $this->configFactory = $configFactory;
    $this->flood = $flood;
    $this->dateFormatter = $dateFormatter;

    // We have to do this after our injections since the parent constructor
    // calls defaultConfiguration() which depends on the configFactory service.
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('entity.form_builder'),
      $container->get('config.factory'),
      $container->get('flood'),
      $container->get('date.formatter')
    );
  }

  /**
   * Overrides \Drupal\block\BlockBase::settings().
   */
  public function defaultConfiguration() {
    return [
      'contact_form' => $this->configFactory->get('contact.settings')->get('default_form'),
    ];
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $categories = $this->entityTypeManager->getStorage('contact_form')->loadMultiple();

    $options = [];
    foreach ($categories as $category) {
      $options[$category->id()] = $category->label();
    }

    $form['formblock_contact_form'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#default_value' => $this->configuration['contact_form'],
      '#description' => $this->t('Select the category to show.'),
      '#options' => $options,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['contact_form'] = $form_state->getValue('formblock_contact_form');
  }

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    $build = [];

    // Check if flood control has been activated for sending emails.
    if (!$this->currentUser->hasPermission('administer contact forms') && $message = $this->floodControl()) {
      $build['message'] = [
        '#markup' => $message,
      ];
      return $build;
    }

    $message = $this->entityTypeManager
      ->getStorage('contact_message')
      ->create([
        'contact_form' => $this->getContactForm()->id(),
      ]);

    $build['form'] = $this->entityFormBuilder->getForm($message);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf($this->getContactForm()->access('view', $account) && $account->hasPermission('access site-wide contact form'))
      ->cachePerPermissions()
      ->addCacheTags($this->getContactForm()->getCacheTags())
      ->addCacheTags($this->configFactory->get('contact.settings')->getCacheTags());
  }

  /**
   * Returns the current status of flood control.
   *
   * @return bool|string
   *   False if flood control should not be enabled or an error message if
   *   flood control is enabled.
   */
  protected function floodControl() {
    $limit = $this->configFactory->get('contact.settings')->get('flood.limit');
    $interval = $this->configFactory->get('contact.settings')->get('flood.interval');
    if (!$this->flood->isAllowed('contact', $limit, $interval)) {
      return $this->t('You cannot send more than %limit messages in @interval. Try again later.', [
        '%limit' => $limit,
        '@interval' => $this->dateFormatter->formatInterval($interval),
      ]);
    }
    return FALSE;
  }

  /**
   * Return the contact form entity (type of contact form).
   *
   * @return \Drupal\contact\Entity\ContactForm
   *   The contact form type.
   */
  private function getContactForm() {
    return $this->entityTypeManager->getStorage('contact_form')->load($this->configuration['contact_form']);
  }

}
