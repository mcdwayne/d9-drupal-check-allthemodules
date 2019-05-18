<?php

namespace Drupal\hidden_tab\Form;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\ContextualLinkManagerInterface;
use Drupal\Core\Menu\LocalActionManagerInterface;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Event\HiddenTabPageFormEvent;
use Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager;
use Drupal\hidden_tab\Service\HiddenTabEntityHelper;
use Drupal\hidden_tab\Service\HiddenTabEntityHelperInterface;
use Drupal\hidden_tab\Utility;
use Drupal\user\PermissionHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Hidden Tab Page entity add/edit form..
 *
 * @property \Drupal\hidden_tab\Entity\HiddenTabPageInterface $entity
 *
 * @see \Drupal\hidden_tab\Entity\HiddenTabPageInterface
 */
class HiddenTabPageForm extends EntityForm {

  /**
   * User permission service.
   *
   * To provide a list of permissions for select list.
   *
   * @var \Drupal\user\PermissionHandler
   *
   * @see \Drupal\hidden_tab\Form\HiddenTabPageForm::form()
   */
  protected $userPermissionService;

  /**
   * Event dispatcher service, to dispatch the form and it's event to plugins.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   *
   * @see \Drupal\hidden_tab\Event\HiddenTabPageFormEvent
   */
  protected $eventer;

  /**
   * To get list of bundles of an entity type.
   *
   * Needed to limit the page to specific bundle.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   *
   * @see \Drupal\hidden_tab\Form\HiddenTabPageForm::form()
   */
  protected $bundleInfo;

  /**
   * Handy service for creating the event.
   *
   * @var \Drupal\Component\Uuid\Php
   */
  protected $uuid;

  /**
   * Handy service for creating the event.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * To find templates.
   *
   * @var \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager
   */
  protected $templateMan;

  /**
   * To work with hidden tab defined entities.
   *
   * @var \Drupal\hidden_tab\Service\HiddenTabEntityHelperInterface
   */
  protected $entityHelper;

  // ==========================================================================

  /**
   * To clear route cache and rebuild routes, to register page's route.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * To clear route cache and rebuild routes, to register page's route.
   *
   * @var \Drupal\Core\Menu\ContextualLinkManagerInterface
   */
  protected $contextualLinkManager;

  /**
   * To clear route cache and rebuild routes, to register page's route.
   *
   * @var \Drupal\Core\Menu\LocalTaskManagerInterface
   */
  protected $localTaskLinkManager;

  /**
   * To clear route cache and rebuild routes, to register page's route.
   *
   * @var \Drupal\Core\Menu\LocalActionManagerInterface
   */
  protected $localActionLinkManager;

  /**
   * To clear route cache and rebuild routes, to register page's route.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $menuCache;

  /**
   * To clear route cache and rebuild routes, to register page's route.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerRebuilder;

  // ==========================================================================

  /**
   * HiddenTabPageForm constructor.
   *
   * @param \Drupal\user\PermissionHandler $user_permission_service
   *   See $this->user_permission_service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventer
   *   See $this->eventer.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $bundle_info
   *   See $this->bundleInfo.
   * @param \Drupal\Component\Uuid\Php $uuid
   *   See $this->uuid.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   See $this->formBuilder.
   * @param \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager $template_man
   *   See $this->templateMan.
   * @param \Drupal\hidden_tab\Service\HiddenTabEntityHelperInterface $entity_helper
   *   See $this->entityHelper.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   See $this->menuLinkManager.
   * @param \Drupal\Core\Menu\ContextualLinkManagerInterface $contextual_link_manager
   *   See $this->contextualLinkManager.
   * @param \Drupal\Core\Menu\LocalTaskManagerInterface $local_task_link_manager
   *   See $this->localTaskLinkManager.
   * @param \Drupal\Core\Menu\LocalActionManagerInterface $local_action_link_manager
   *   See $this->localActionLinkManager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $menu_cache
   *   See $this->menuCache.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   See $this->routerRebuilder.
   */
  public function __construct(PermissionHandler $user_permission_service,
                              EventDispatcherInterface $eventer,
                              EntityTypeBundleInfo $bundle_info,
                              Php $uuid,
                              FormBuilderInterface $form_builder,
                              HiddenTabTemplatePluginManager $template_man,
                              HiddenTabEntityHelperInterface $entity_helper,
                              MenuLinkManagerInterface $menu_link_manager,
                              ContextualLinkManagerInterface $contextual_link_manager,
                              LocalTaskManagerInterface $local_task_link_manager,
                              LocalActionManagerInterface $local_action_link_manager,
                              CacheBackendInterface $menu_cache,
                              RouteBuilderInterface $route_builder) {
    $this->userPermissionService = $user_permission_service;
    $this->eventer = $eventer;
    $this->bundleInfo = $bundle_info;
    $this->uuid = $uuid;
    $this->formBuilder = $form_builder;
    $this->templateMan = $template_man;
    $this->entityHelper = $entity_helper;
    $this->menuLinkManager = $menu_link_manager;
    $this->contextualLinkManager = $contextual_link_manager;
    $this->localTaskLinkManager = $local_task_link_manager;
    $this->localActionLinkManager = $local_action_link_manager;
    $this->menuCache = $menu_cache;
    $this->routerRebuilder = $route_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('user.permissions'),
      $container->get('event_dispatcher'),
      $container->get('entity_type.bundle.info'),
      $container->get('uuid'),
      $container->get('form_builder'),
      $container->get('plugin.manager.hidden_tab_template'),
      $container->get('hidden_tab.entity_helper'),
      $container->get('plugin.manager.menu.link'),
      $container->get('plugin.manager.menu.contextual_link'),
      $container->get('plugin.manager.menu.local_task'),
      $container->get('plugin.manager.menu.local_action'),
      $container->get('cache.menu'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $target_types = ['node' => $this->t('Node')];
    $target_type = 'node';

    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\hidden_tab\Entity\HiddenTabPage::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Whether if the page is enabled or not.'),
      '#default_value' => $this->entity->isNew() ? TRUE : $this->entity->status(),
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $this->entity->description(),
    ];

    $form['tab_uri'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Tab Uri'),
      '#description' => $this->t('The Uri from which the tab page is accessible.'),
      '#default_value' => $this->entity->tabUri(),
      '#machine_name' => [
        'exists' => '\Drupal\hidden_tab\Service\HiddenTabEntityHelper::uriExists',
        'error' => $this->t('The uri is already in use or is invalid.'),
      ],
      '#maxlength' => 255,
    ];

    $form['secret_uri'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Secret Uri'),
      '#description' => $this->t('The Uri from which the secret tab page is accessible.'),
      '#default_value' => $this->entity->secretUri(),
      '#machine_name' => [
        'exists' => '\Drupal\hidden_tab\Service\HiddenTabEntityHelper::uriExists',
        'error' => $this->t('The uri is already in use.'),
      ],
      '#maxlength' => 255,
      '#required' => FALSE,
    ];

    $form['is_access_denied'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show access denied'),
      '#description' => $this->t('It is possible and sometimes recommended to simply display page not found instead of access denied. Yet you get a log entry in case of illegal access.'),
      '#default_value' => $this->entity->isAccessDenied(),
    ];

    $form['target_entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Target Entity Type'),
      '#description' => $this->t('On which entity type this page is attached.'),
      '#default_value' => $this->entity->targetEntityType() ?: $target_type,
      '#options' => $target_types,
    ];

    $form['target_entity_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Target Entity Bundle'),
      '#description' => $this->t('On which bundle this page is attached.'),
      '#default_value' => $this->entity->targetEntityBundle(),
      '#options' => HiddenTabEntityHelper::nodeBundlesSelectList(TRUE),
    ];

    $form['tab_view_permission'] = [
      '#type' => 'select',
      '#title' => $this->t('Tab Permission'),
      '#description' => $this->t('The permission user must posses to access the page via tab.'),
      '#default_value' => $this->entity->tabViewPermission() === NULL ? HiddenTabPageInterface::TAB_PERMISSION_DEFAULT_PERMISSION : $this->entity->tabViewPermission(),
      '#options' => Utility::permissionOptions($this->userPermissionService->getPermissions()),
    ];

    $form['secret_uri_view_permission'] = [
      '#type' => 'select',
      '#title' => $this->t('Secret Uri Permission'),
      '#description' => $this->t('The permission user must posses to access the page via secret uri.'),
      '#default_value' => $this->entity->secretUriViewPermission() === NULL ? 'access content' : $this->entity->secretUriViewPermission(),
      '#options' => Utility::permissionOptions($this->userPermissionService->getPermissions()),
    ];

    $form['credit_check_order'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Credit check order'),
      '#description' => $this->t('Page/Entity/User, allowed values (comma separated): @order', [
        '@order' => HiddenTabPageInterface::DEFAULT_CREDIT_CHECK_ORDER,
      ]),
      '#default_value' => $this->entity->creditCheckOrder()
        ? $this->entity->creditCheckOrder()
        : HiddenTabPageInterface::DEFAULT_CREDIT_CHECK_ORDER,
    ];

    $form['template'] = [
      '#type' => 'select',
      '#title' => $this->t('Template'),
      '#description' => $this->t('The template used to render the page. Will define page regions.'),
      '#default_value' => $this->entity->template()
        ? $this->entity->template()
        : 'hidden_tab_two_column',
      '#options' => $this->templateMan->pluginsForSelectElement('general'),
    ];

    $previews = NULL;
    try {
      $previews = $this->previews();
    }
    catch (\Exception $exception) {
      $this->getLogger('hidden_tab')
        ->error($this->t('error generating previews: @err', [
          '@err' => $exception->getMessage(),
        ]));
      $previews = NULL;
    }
    if (is_array($previews)) {
      $form += $previews;
    }

    $d = $this->t('The inline twig template used to render the page. Overrides template property. Use [regions.reg_N] where N is from 0 to region_count, for placements. Please note that each region_N is an array containing rendered html.');
    $form['inline_template'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Inline Template'),
      '#description' => $d,
      '#default_value' => $this->entity->inlineTemplate(),
    ];

    $d = $this->t('How many regions in form of regions.region_N will be available in inline region, where N is the count set here.');
    $form['inline_template_region_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Inline Template Region Count'),
      '#description' => $d,
      '#default_value' => $this->entity->inlineTemplateRegionCount(),
      '#min' => 0,
    ];

    $form['clear_menu'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clear menu and routing caches upon save'),
      '#description' => $this->t("It is necessary to clear routing caches to register a new rout (the created page's rout)."),
      '#default_value' => FALSE,
    ];

    // ------------------------------------------------------------------------

    // Used for checks in save step.
    $form['old_template'] = [
      '#type' => 'value',
      '#value' => $this->entity->template(),
      '#default_value' => $this->entity->template(),
    ];

    // Used for checks in save step.
    $form['old_inline_template'] = [
      '#type' => 'value',
      '#value' => $this->entity->inlineTemplate(),
      '#default_value' => $this->entity->inlineTemplate(),
    ];

    // Used for checks in save step.
    $form['old_inline_template_region_count'] = [
      '#type' => 'value',
      '#value' => $this->entity->inlineTemplateRegionCount(),
      '#default_value' => $this->entity->inlineTemplateRegionCount(),
    ];

    // Used for checks in save step.
    $is_inline = $this->entity->inlineTemplate() || !$this->entity->template();
    $form['old_is_inline'] = [
      '#type' => 'value',
      '#value' => $is_inline,
      '#default_value' => $is_inline,
    ];

    // Used for checks in save step.
    $form['old_tab_uri'] = [
      '#type' => 'value',
      '#value' => $this->entity->tabUri(),
      '#default_value' => $this->entity->tabUri(),
    ];

    // Used for checks in save step.
    $form['was_new'] = [
      '#type' => 'value',
      '#value' => $this->entity->isNew(),
      '#default_value' => $this->entity->isNew(),
    ];

    // Give other modules opportunity to add stuff to the form.
    $this->eventer->dispatch(HiddenTabPageFormEvent::EVENT_NAME,
      $this->event($form, $form_state, HiddenTabPageFormEvent::PHASE_FORM));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Don't show just yet if not entered. Let other validation handle it.
    if ($form_state->getValue('tab_uri') &&
      $form_state->getValue('tab_uri') === $form_state->getValue('secret_uri')) {
      $form_state->setErrorByName('tab_uri', $this->t("Both Uris can't be same"));
      $form_state->setErrorByName('secret_uri', $this->t("Both Uris can't be same"));
    }
    // Give other modules opportunity to validate their added stuff.
    $this->eventer->dispatch(HiddenTabPageFormEvent::EVENT_NAME,
      $this->event($form, $form_state, HiddenTabPageFormEvent::PHASE_VALIDATE));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $is_cc = $form_state->getValue('clear_menu');

    $is_inline = $form_state->getValue('inline_template') || !$form_state->getValue('template');
    $inline_changed = $form_state->getValue('old_is_inline') != $is_inline;

    $red = TRUE;
    $was_new = $form_state->getValue('was_new');

    if (!$was_new && $inline_changed) {
      $this->resetRegion($form_state);
      $this->messenger()->addWarning($this->t(
        'You have changed the template type (inline <---> file), please revisit the layout page and order the komponents as necessary.'));
    }
    elseif (!$was_new && !$is_inline && ($form_state->getValue('old_template') !== $form_state->getValue('template'))) {
      $this->resetRegion($form_state);
      $this->messenger()->addWarning($this->t(
        'You have changed the template, please revisit the layout page and order the komponents as necessary.'));
    }
    elseif (!$was_new && $is_inline && $form_state->getValue('old_inline_template') !== $form_state->getValue('inline_template')) {
      $this->messenger()->addWarning($this->t(
        'You have changed the inline template, please revisit the layout page and order the komponents as necessary.'));
    }
    elseif ($is_inline && ((int) $form_state->getValue('old_inline_template_region_count'))
      > ((int) $form_state->getValue('inline_template_region_count'))) {
      // Check if those regions were empty anyways.
      $old_count = (int) $form_state->getValue('old_inline_template_region_count');
      $new_count = (int) $form_state->getValue('inline_template_region_count');
      $placements = $this->entityHelper->placementsOfPage($this->entity->id());
      $found = FALSE;
      for ($i = $new_count; $i < $old_count; $i++) {
        foreach ($placements as $placement) {
          $placement_i = explode('_', $placement->region())[1];
          if ($placement_i >= $new_count) {
            $found = TRUE;
            break;
          }
        }
      }
      if ($found) {
        $this->resetRegion($form_state);
        $this->messenger()->addWarning($this->t(
          'You have reduced the inline template region count, please revisit the layout page and order the komponents as necessary.'));
      }
      else {
        $red = FALSE;
      }
    }
    else {
      $red = FALSE;
    }

    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created new Hidden Tab Page %label.', $message_args)
      : $this->t('Updated Hidden Tab Page %label.', $message_args);
    $this->messenger()->addStatus($message);

    if ($red) {
      $form_state->setRedirect(
        'entity.hidden_tab_page.layout_form',
        ['hidden_tab_page' => $this->entity->id()]
      );
    }
    else {
      $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    }

    // Give other modules opportunity to save their added stuff.
    $this->eventer->dispatch(HiddenTabPageFormEvent::EVENT_NAME,
      $this->event($form, $form_state, HiddenTabPageFormEvent::PHASE_SUBMIT));

    if (!$is_cc && !$was_new && ($form_state->getValue('tab_uri') !== $form_state->getValue('old_tab_uri'))) {
      $this->messenger()
        ->addWarning($this->t("You have changed the Uri, don't forget to clear the caches for the Uri change to take effect."));
    }
    elseif (!$is_cc && $was_new) {
      $this->messenger()
        ->addWarning($this->t("You have added a new page, don't forget to clear the caches for the Uri addition to take effect."));
    }

    if (Utility::checkRedirect()) {
      $form_state->setRedirectUrl(Utility::checkRedirect());
    }

    if ($is_cc) {
      $this->clearMenuCache();
    }

    return $result;
  }

  /**
   * Re-order and re-organise placements for the newly selected template.
   *
   * TODO instead, put them under a new hidden region and show hidden region on
   * the layout form. This gives ability of hiding komponents too.
   *
   * When a new template is chosen some regions may become unavailable. This
   * causes the placements of un-available regions not to be shown any more
   * to user on the layouts form. Which is bad. So we put those with missing
   * regions, in the first region of the newly selected template.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of page being saved.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function resetRegion(FormStateInterface $form_state) {
    $placements = $this->entityHelper->placementsOfPage($this->entity->id());
    $new_regions = $this->templateMan->regionsOfTemplate($form_state->getValue('template'));
    // Usually shouldn't happen, as templates have at least one region.
    // TODO FIXME this hides the placement from the user! bad.
    $catch_all = '';
    foreach ($new_regions as $region => $crap) {
      $catch_all = $region;
      break;
    }
    foreach ($placements as $placement) {
      if (!isset($new_regions[$placement->region()])) {
        // Old region is no longer available.
        $placement->set('region', $catch_all);
        $placement->save();
      }
    }
  }

  /**
   * Event constructor.
   *
   * @param array $form
   *   Event argument.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Event argument.
   * @param int $phase
   *   Event argument.
   *
   * @return \Drupal\hidden_tab\Event\HiddenTabPageFormEvent
   */
  private function event(array &$form,
                         FormStateInterface $form_state,
                         int $phase) {
    /** @noinspection PhpParamsInspection */
    return new HiddenTabPageFormEvent(
      $form,
      $form_state,
      $this->getEntity(),
      $phase
    );
  }

  /**
   * Populate template previews in admin form.
   *
   * @return array
   *   Form element for previews.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function previews(): array {
    // Show all of them at once.
    $previews = [];
    foreach ($this->templateMan->templatePreviewImages() as $label => $img_uri) {
      $previews[$label] = [
        '#weight' => -10,
        '#theme' => 'image',
        '#width' => 100,
        '#height' => 200,
        '#style_name' => 'medium',
        '#uri' => $GLOBALS['base_url'] . '/' . $img_uri,
      ];
    }
    $pid = 'hidden_tab_admin_templates_preview';
    $attach = [];
    if ($this->templateMan->exists($pid)) {
      /** @noinspection PhpUndefinedMethodInspection */
      $attach = $this->templateMan->plugin('hidden_tab_admin_templates_preview')
        ->attachLibrary();
    }
    else {
      $this->messenger()->addWarning($this->t('Plugin missing: @plug', [
        '@plug' => 'hidden_tab_admin_templates_preview',
      ]));
    }
    $form['previews'] = [
      '#attached' => $attach,
      '#theme' => 'hidden_tab_hidden_tab_admin_templates_preview',
      '#previews' => $previews,
    ];
    return $form;
  }

  /**
   * Clear menu caches so page's new route is registered.
   */
  private function clearMenuCache() {
    $this->menuCache->invalidateAll();
    $this->menuLinkManager->rebuild();
    /** @noinspection PhpUndefinedMethodInspection */
    $this->contextualLinkManager->clearCachedDefinitions();
    /** @noinspection PhpUndefinedMethodInspection */
    $this->localTaskLinkManager->clearCachedDefinitions();
    /** @noinspection PhpUndefinedMethodInspection */
    $this->localActionLinkManager->clearCachedDefinitions();
    $this->routerRebuilder->rebuild();
    $this->messenger()
      ->addMessage($this->t('Routing and links cache cleared.'));
  }

}
