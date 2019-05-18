<?php

namespace Drupal\discussions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\RendererInterface;
use Drupal\discussions\DiscussionTypeInterface;
use Drupal\discussions\Entity\Discussion;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\group\Entity\Group;

/**
 * Controller for group Discussions.
 *
 * @ingroup discussions
 */
class GroupDiscussionController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new GroupDiscussionController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('renderer')
    );
  }

  /**
   * Displays add links for available bundles/types for Discussions.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group Discussion to.
   *
   * @return array
   *   A render array for a list of the Discussion bundles/types that can be
   *   added or if there is only one bundle/type defined for the site, the
   *   function returns the add page for that bundle/type.
   */
  public function add(GroupInterface $group) {
    $build = ['#theme' => 'entity_add_list', '#bundles' => []];
    $add_form_route = 'entity.group_content.group_discussion_add_form';

    // Build a list of available bundles.
    $bundles = [];

    $entityTypeManager = \Drupal::entityTypeManager();

    $access_control_handler = $entityTypeManager->getAccessControlHandler('group_content');
    foreach ($group->getGroupType()->getInstalledContentPlugins() as $plugin_id => $plugin) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      // Only select the group_discussion plugins.
      if ($plugin->getBaseId() == 'group_discussion') {
        $bundle = $plugin->getContentTypeConfigId();

        // Add the user's access rights as cacheable dependencies.
        $access = $access_control_handler->createAccess($bundle, NULL, ['group' => $group], TRUE);
        $this->renderer->addCacheableDependency($build, $access);

        // Filter out the bundles the user doesn't have access to.
        if ($access->isAllowed()) {
          $bundles[$plugin_id] = $bundle;
        }
      }
    }

    // Redirect if there's only one bundle available.
    if (count($bundles) == 1) {
      $plugin = $group->getGroupType()->getContentPlugin(key($bundles));
      $route_params = ['group' => $group->id(), 'discussion_type' => $plugin->getEntityBundle()];
      $url = Url::fromRoute($add_form_route, $route_params, ['absolute' => TRUE]);
      return new RedirectResponse($url->toString());
    }

    // Get the Discussion Type storage handler.
    $storage_handler = $entityTypeManager->getStorage('discussion_type');

    // Set the info for all of the remaining bundles.
    foreach ($bundles as $plugin_id => $bundle) {
      $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
      $bundle_label = $storage_handler->load($plugin->getEntityBundle())->label();
      $route_params = ['group' => $group->id(), 'discussion_type' => $plugin->getEntityBundle()];

      $build['#bundles'][$bundle] = [
        'label' => $bundle_label,
        'description' => $this->t('Create a Discussion of type %discussion_type for the group.', ['%discussion_type' => $bundle_label]),
        'add_link' => Link::createFromRoute($bundle_label, $add_form_route, $route_params),
      ];
    }

    // Add the list cache tags for the GroupContentType entity type.
    $bundle_entity_type = $entityTypeManager->getDefinition('group_content_type');
    $build['#cache']['tags'] = $bundle_entity_type->getListCacheTags();

    return $build;
  }

  /**
   * Provides the form for creating a Discussion in a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to create a Discussion in.
   * @param \Drupal\discussions\DiscussionTypeInterface $discussion_type
   *   The Discussion type to create.
   *
   * @return array
   *   The form array for either step 1 or 2 of group Discussion creation.
   */
  public function addForm(GroupInterface $group, DiscussionTypeInterface $discussion_type) {
    $plugin_id = 'group_discussion:' . $discussion_type->id();

    $plugin = $group->getGroupType()->getContentPlugin($plugin_id);

    $entity = Discussion::create(['type' => $discussion_type->id()]);

    // Return the form with the group and plugin ID added to the form state.
    $extra = [
      'group' => $group,
      'plugin' => $plugin,
    ];

    return $this->entityFormBuilder()->getForm($entity, 'add_to_group', $extra);
  }

  /**
   * Provides the page title for this controller.
   *
   * @param \Drupal\Core\Entity\EntityInterface $discussion
   *   The discussion entity.
   *
   * @return string
   *   The page title.
   */
  public function getTitle(EntityInterface $discussion) {
    return $discussion->label();
  }

  /**
   * Provides the page title for a discussion creation form.
   *
   * @param \Drupal\group\Entity\Group $group
   *   The group entity.
   *
   * @return string
   *   The page title.
   */
  public function addTitle(Group $group) {
    $label[] = $this->t('Start a new Discussion in');
    $label[] = $group->label();
    return implode(' ', $label);
  }

}
