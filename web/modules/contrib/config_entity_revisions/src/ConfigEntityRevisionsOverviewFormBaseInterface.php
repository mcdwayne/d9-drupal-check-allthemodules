<?php

namespace Drupal\config_entity_revisions;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\diff\DiffLayoutManager;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Utility\LinkGenerator;

/**
 * Controller to make library functions available to various consumers.
 */
interface ConfigEntityRevisionsOverviewFormBaseInterface extends FormInterface, ContainerInjectionInterface {

  /**
   * Constructs a ConfigEntityRevisionsController object.
   *
   * @param DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param RendererInterface $renderer
   *   The renderer service.
   * @param ImmutableConfig $config
   *   The configuration service.
   * @param ModuleHandler $module_handler
   *   The module handler service.
   * @param EntityTypeManager $entity_type_manager
   *   The entity type manager service.
   * @param DiffLayoutManager $diff_layout_manager
   *   The diff layout manager service
   * @param LinkGenerator $link
   *   The Link generator service.
   */
  public function __construct(
    DateFormatterInterface $date_formatter,
    RendererInterface $renderer,
    ImmutableConfig $config,
    ModuleHandler $module_handler,
    EntityTypeManager $entity_type_manager,
    DiffLayoutManager $diff_layout_manager,
    LinkGenerator $link
  );

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container);

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId();

  /**
   * Generates an overview table of older revisions of a config entity.
   *
   * @param array $form
   *   A form being built.
   * @param FormStateInterface $form_state
   *   The form state.
   * @param ConfigEntityInterface $config_entity
   *   A configuration entity.
   *
   * @return array
   *   An array as expected by \Drupal\Core\Render\RendererInterface::render().
   */
  public function buildForm(array $form, FormStateInterface $form_state, ConfigEntityInterface $config_entity = NULL);

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state);

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state);

}
