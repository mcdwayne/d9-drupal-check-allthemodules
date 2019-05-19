<?php

namespace Drupal\simple_content\Plugin\Block;

use Drupal\block_content\BlockContentUuidLookup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\simple_content\Entity\SimpleContentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a generic custom block type.
 *
 * @Block(
 *  id = "simple_content_block",
 *  admin_label = @Translation("Simple content"),
 *  category = @Translation("Simple content"),
 *  deriver = "Drupal\simple_content\Plugin\Derivative\SimpleContentBlock"
 * )
 */
class SimpleContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Drupal account to use for checking for access to block.
   *
   * @var \Drupal\Core\Session\AccountInterface.
   */
  protected $account;

  /**
   * The simple content entity.
   *
   * @var \Drupal\simple_content\Entity\SimpleContentInterface
   */
  protected $simpleContent;

  /**
   * The simple content type.
   *
   * @var \Drupal\simple_content\Entity\SimpleContentTypeInterface
   */
  protected $simpleContentType;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The block content UUID lookup service.
   *
   * @var \Drupal\block_content\BlockContentUuidLookup
   */
  protected $uuidLookup;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a new SimpleContentBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which view access should be checked.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   * @param \Drupal\block_content\BlockContentUuidLookup $uuid_lookup
   *   The block content UUID lookup service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $account, UrlGeneratorInterface $url_generator, BlockContentUuidLookup $uuid_lookup, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->account = $account;
    $this->urlGenerator = $url_generator;
    $this->uuidLookup = $uuid_lookup;
    $this->entityDisplayRepository = $entity_display_repository;
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
      $container->get('current_user'),
      $container->get('url_generator'),
      $container->get('block_content.uuid_lookup'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'simple_content_id' => NULL,
      'view_mode' => 'default',
    ];
  }

  /**
   * Overrides \Drupal\Core\Block\BlockBase::blockForm().
   *
   * Adds body and description fields to the block configuration form.
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $simpleContent = NULL;
    $simpleContentType = $this->getSimpleContentType();

    if (!$simpleContentType) {
      $form['info']['#markup'] = '<p>' . $this->t('No simple content type found') . '</p>';
      return $form;
    }

    $simpleContent = $this->getEntity();
    if (!$simpleContent) {
      $form['info']['#markup'] = '<p>' . $this->t('No simple content found') . '</p>';
      return $form;
    }

    // Add the entity form display in a process callback so that #parents can
    // be successfully propagated to field widgets.
    $form['simple_content_form'] = [
      '#type' => 'container',
      '#process' => [[static::class, 'processSimpleContentForm']],
      '#simple_content' => $simpleContent,
    ];

    $options = $this->entityDisplayRepository->getViewModeOptionsByBundle('block_content', $simpleContentType->id());

    $form['view_mode'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('View mode'),
      '#description' => $this->t('The view mode in which to render the content.'),
      '#default_value' => $this->configuration['view_mode'],
      '#access' => count($options) > 1,
    ];

    return $form;
  }

  /**
   * Process callback to insert a Simple Content form.
   *
   * @param array $element
   *   The containing element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The containing element, with the Simple Content form inserted.
   */
  public static function processSimpleContentForm(array $element, FormStateInterface $form_state) {
      /** @var \Drupal\simple_content\Entity\SimpleContentInterface $simple_content */
      $simple_content = $element['#simple_content'];
      EntityFormDisplay::collectRenderDisplay($simple_content, 'edit')->buildForm($simple_content, $element, $form_state);
      return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $simple_content_form = $form['simple_content_form'];
    /** @var \Drupal\simple_content\Entity\SimpleContentInterface $simple_content */
    $simple_content = $simple_content_form['#simple_content'];
    $form_display = EntityFormDisplay::collectRenderDisplay($simple_content, 'edit');
    $complete_form_state = ($form_state instanceof SubformStateInterface) ? $form_state->getCompleteFormState() : $form_state;
    $form_display->extractFormValues($simple_content, $simple_content_form, $complete_form_state);
    $form_display->validateFormValues($simple_content, $simple_content_form, $complete_form_state);
    // @todo Remove when https://www.drupal.org/project/drupal/issues/2948549 is closed.
    $form_state->setTemporaryValue('block_form_parents', $simple_content_form['#parents']);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');

    // @todo Remove when https://www.drupal.org/project/drupal/issues/2948549 is closed.
    $simple_content_form = NestedArray::getValue($form, $form_state->getTemporaryValue('block_form_parents'));
    /** @var \Drupal\simple_content\Entity\SimpleContentInterface $simple_content */
    $simple_content = $simple_content_form['#simple_content'];
    if (!$simple_content instanceof SimpleContentInterface) {
        throw new \UnexpectedValueException("Invalid value, expected \Drupal\simple_content\Entity\SimpleContentInterface.");
    }
    $form_display = EntityFormDisplay::collectRenderDisplay($simple_content, 'edit');
    $complete_form_state = $form_state instanceof SubformStateInterface ? $form_state->getCompleteFormState() : $form_state;
    $form_display->extractFormValues($simple_content, $simple_content_form, $complete_form_state);

    try {
      $simple_content->save();
      $this->configuration['simple_content_id'] = $simple_content->id();
    }
    catch (\Exception $ignored) {}
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($this->getSimpleContentType() && $this->getEntity()) {
      return $this->getEntity()->access('view', $account, TRUE);
    }
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($simple_content = $this->getEntity()) {
      return $this->entityTypeManager->getViewBuilder('simple_content')->view($simple_content, $this->configuration['view_mode']);
    }
    else {
      return [
        '#markup' => $this->t('Simple content with id %id does not exist.', [
          '%id' => $this->configuration['simple_content_id'],
        ]),
        '#access' => $this->account->hasPermission('administer simple content entities')
      ];
    }
  }

  /**
   * Loads the simple content type.
   *
   * @return \Drupal\simple_content\Entity\SimpleContentTypeInterface|null
   *   The simple content type.
   */
  protected function getSimpleContentType() {
    if (!isset($this->simpleContentType)) {
      try {
        $type = $this->getDerivativeId();
        if ($simpleContentType = $this->entityTypeManager->getStorage('simple_content_type')->load($type)) {
          $this->simpleContentType = $simpleContentType;
        }
      }
      catch (\Exception $ignored) {}
    }
    return $this->simpleContentType;
  }

  /**
   * Gets the simple content entity.
   *
   * @return \Drupal\simple_content\Entity\SimpleContentInterface
   */
  protected function getEntity() {

    if (!isset($this->simpleContentType)) {
      $this->getSimpleContentType();
    }

    if (!isset($this->simpleContent)) {
      try {
        if (!$this->configuration['simple_content_id']) {
          $this->simpleContent = $this->entityTypeManager->getStorage('simple_content')->create(['type' => $this->simpleContentType->id()]);
        }
        else {
          $this->simpleContent = $this->entityTypeManager->getStorage('simple_content')->load($this->configuration['simple_content_id']);
        }
      }
      catch (\Exception $ignored) {}
    }

    return $this->simpleContent;
  }

}
