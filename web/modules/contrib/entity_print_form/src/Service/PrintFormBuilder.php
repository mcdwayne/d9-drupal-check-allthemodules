<?php

namespace Drupal\entity_print_form\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Session\UserSession;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

use Drupal\entity_print\Event\PrintEvents;
use Drupal\entity_print\Event\PreSendPrintEvent;
use Drupal\entity_print\Plugin\PrintEngineInterface;
use Drupal\entity_print\PrintBuilderInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class for entity_print_form building service.
 */
class PrintFormBuilder implements PrintBuilderInterface {
  use StringTranslationTrait;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Account switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * Entity form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * Theme initialization service.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * Theme manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Constructs a new EntityPrintFormPrintBuilder.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, ConfigFactoryInterface $config_factory, AccountSwitcherInterface $account_switcher, EntityFormBuilderInterface $entity_form_builder, ThemeInitializationInterface $theme_initialization, ThemeManagerInterface $theme_manager) {
    $this->dispatcher = $event_dispatcher;
    $this->stringTranslation = $string_translation;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->configFactory = $config_factory;
    $this->accountSwitcher = $account_switcher;
    $this->entityFormBuilder = $entity_form_builder;
    $this->themeInitialization = $theme_initialization;
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function deliverPrintable(array $entities, PrintEngineInterface $print_engine, $force_download = FALSE, $use_default_css = TRUE, $mode = 'default') {
    if (empty($entities)) {
      throw new \InvalidArgumentException('You must pass at least 1 entity');
    }

    $first_entity = reset($entities);
    $entity_type = $first_entity->getEntityTypeId();

    $html = $this->generateHtml($entity_type, $first_entity, $mode, $use_default_css);

    $print_engine->addPage($html);

    // Allow other modules to alter the generated Print object.
    $this->dispatcher->dispatch(PrintEvents::PRE_SEND, new PreSendPrintEvent($print_engine, $entities));

    // If we're forcing a download we need a filename otherwise it's just sent
    // straight to the browser.
    // @TODO fix this.
    $filename = $force_download ? 'download.pdf' : NULL;

    return $print_engine->send($filename, $force_download);
  }

  /**
   * {@inheritdoc}
   */
  public function printHtml(EntityInterface $entity, $use_default_css = TRUE, $optimize_css = TRUE, $mode = 'default') {
    $entity_type = $entity->getEntityTypeId();
    return $this->generateHtml($entity_type, $entity, $mode, $use_default_css);
  }

  /**
   * Generate document and return as a blob.
   */
  public function getBlob(EntityInterface $entity, PrintEngineInterface $print_engine, $mode = 'default') {
    $entity_type = $entity->getEntityTypeId();
    $html = $this->generateHtml($entity_type, $entity, $mode);

    $print_engine->addPage($html);

    return $print_engine->getBlob();
  }

  /**
   * Generate the rendered html.
   *
   * @param string $entity_type
   *   The entity type.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The content entity to generate HTML for.
   * @param string $mode
   *   The form display mode.
   */
  private function generateHtml($entity_type, EntityInterface $entity, $mode) {
    $render_role = $this->configFactory->get('entity_print_form.settings')->get('render_role', '');
    if (!empty($render_role)) {
      $render_roles = array_filter(explode(',', trim($render_role)));
      $render_roles[] = 'authenticated';
      // The difficulty with specifying a role *only* as opposed to a uid/role
      // tuple is that anything that tries to load the user entity *might* die
      // since the user might not exist. Also using uid of 1 also doesn't feel
      // correct re security issues. As such, have opted for a very large uid
      // and that you should handle non-existent user failures as appropriate.
      $this->accountSwitcher->switchTo(new UserSession([
        'uid' => '99999999',
        'roles' => $render_roles,
      ]));
    }

    // @TODO yuck, temporary. Doing this to get access at the current entity for token replacement.
    \Drupal::state()->set('entity_print_form_entity_id', $entity->id());
    \Drupal::state()->set('entity_print_form_entity_bundle', $entity->bundle());
    \Drupal::state()->set('entity_print_form_entity_type_id', $entity->getEntityTypeId());

    $content = $this->entityFormBuilder->getForm($entity, $mode, ['entity_print_form' => TRUE]);
    $content['#attributes']['class'][] = 'entity-print-form';

    $form_display = $this->entityTypeManager->getStorage('entity_form_display')->load($entity_type . '.' . $entity->bundle() . '.' . $mode);

    // Allow modules to alter the form before rendering.
    $module_handler = \Drupal::moduleHandler();
    $context = [
      'entity' => $entity,
      'form_display' => $form_display,
    ];
    $module_handler->alter('entity_print_form_content', $content, $context);

    $render = [
      '#theme' => 'entity_print__' . $entity_type . '__' . $entity->bundle(),
      '#title' => $this->t('View @type', ['@type' => $entity_type]),
      '#content' => $content,
      '#attached' => [],
    ];

    $force_theme = $this->configFactory->get('entity_print_form.settings')->get('force_default_theme');

    $current_theme = NULL;
    if ($force_theme === 1) {
      $default_theme_name = $this->configFactory->get('system.theme')->get('default');
      $current_theme = $this->themeManager->getActiveTheme();
      $this->themeManager->setActiveTheme($this->themeInitialization->initTheme($default_theme_name));
    }

    // @TODO add in the stuff from the replaced renderer. e.g. default css, events etc.
    $html = $this->renderer->renderRoot($render);

    // @TODO yuck, temporary.
    \Drupal::state()->set('entity_print_form_entity_id', '');
    \Drupal::state()->set('entity_print_form_entity_bundle', '');
    \Drupal::state()->set('entity_print_form_entity_type_id', '');

    // Restore the theme.
    if ($force_theme === 1) {
      $this->themeManager->setActiveTheme($current_theme);
    }

    if (!empty($render_role)) {
      $this->accountSwitcher->switchBack();
    }

    return $html;
  }

  /**
   * {@inheritdoc}
   */
  public function savePrintable(array $entities, PrintEngineInterface $print_engine, $scheme = 'public', $filename = '', $use_default_css = TRUE) {
    $entity = reset($entities);
    $blob = $this->getBlob($entity, $print_engine);

    $uri = "$scheme://$filename";

    return file_unmanaged_save_data($blob, $uri, FILE_EXISTS_REPLACE);
  }

}
