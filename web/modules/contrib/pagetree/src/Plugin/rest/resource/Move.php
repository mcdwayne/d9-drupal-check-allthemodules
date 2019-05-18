<?php

namespace Drupal\pagetree\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\pagetree\Service\MenuHelper;
use Drupal\pagetree\Service\StateChange;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to move a page in the menu tree.
 *
 * @RestResource(
 *   id = "pagetree_move",
 *   label = @Translation("Move a page"),
 *   uri_paths = {
 *     "canonical" = "/pagetree/move"
 *   }
 * )
 */

class Move extends ResourceBase
{
    /**
     * The state change service
     *
     * @var \Drupal\pagetree\Service\StateChange
     */
    protected $stateChange = null;

    /**
     * The menu helper service
     *
     * @var \Drupal\pagetree\Service\MenuHelper
     */
    protected $menuHelper = null;

    /**
     * Constructs a new UnpublishResource object.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param array $serializer_formats
     *   The available serialization formats.
     * @param \Psr\Log\LoggerInterface $logger
     *   A logger instance.
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     *   A current user instance.
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        array $serializer_formats,
        LoggerInterface $logger,
        AccountProxyInterface $current_user,
        StateChange $state_change,
        MenuHelper $menu_helper) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
        $this->stateChange = $state_change;
        $this->menuHelper = $menu_helper;

    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->getParameter('serializer.formats'),
            $container->get('logger.factory')->get('pagetree'),
            $container->get('current_user'),
            $container->get('pagetree.state_change'),
            $container->get('pagetree.menu_helper')
        );
    }

    /*
     * Moves a page under the new parent at the given position.
     * Rebuilds menu.
     *
     * @return ModifiedResourceResponse The response containing a list of bundle names.
     */
    public function patch($request)
    {
        $pluginId = $request['id'];
        $parentId = $request['newParent'];
        $position = $request['weight'];
        $menu = $request['menu'];

        $response = [];
        if (empty($menu) || $menu == null) {
            $menu = 'main';
        }

        $this->_menuHelper = \Drupal::service('pagetree.menu_helper');
        $menuLink = $this->_menuHelper::loadMenuLink($pluginId);
        if ($menuLink != null) {
            $oldParent = $menuLink->parent->value;
            if (!empty($parentId)) {
                $parentMenuLink = $this->_menuHelper::loadMenuLink($parentId);
                if ($parentMenuLink != null) {
                    $menuLink->parent->value = $parentMenuLink->getPluginId();
                } else {
                    $menuLink->parent->value = '';
                }
            } else {
                $menuLink->parent->value = '';
            }

            if ($menuLink->parent->value != $oldParent) {
                $this->_menuHelper::reorder($oldParent, null, $menu);
            }
            $this->_menuHelper::saveAndReorder($menuLink, $position, $menu);
            $this->_menuHelper::clearCache($menu);

            $response = $request;
        } else {
            $response = ['error' => 'Could not find menu link ' . $pluginId . ' in menu ' . $menu];
        }
        return new ModifiedResourceResponse($response);
    }
}
