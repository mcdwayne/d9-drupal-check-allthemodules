<?php

namespace Drupal\pagetree\Plugin\rest\resource;

use Drupal\node\Entity\Node;
use Drupal\pagetree\Service\MenuHelper;
use Drupal\pagetree\Service\StateChange;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to copy a page and place the new entry in the menu tree.
 *
 * @RestResource(
 *   id = "pagetree_copy",
 *   label = @Translation("iQ page copy"),
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "/pagetree/copy"
 *   }
 * )
 */

class Copy extends Move
{
    /*
     * Responds to POST requests.
     *
     * Copies a page and it's children under the new parent at the given position.
     * Rebuilds menu.
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   The response containing the id of the copy.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function post($request)
    {
        $id = $request['id'];
        $parentId = $request['newParent'];
        $weight = $request['weight'];
        $recursive = $request['recursive'];
        $menu = $request['menu'];

        if (empty($menu) || $menu == null) {
            $menu = 'main';
        }

        $entity = Node::load($id);
        if ($entity == null) {
            throw new UnprocessableEntityHttpException('Entity not found.');
        }

        if (!$entity->access('create', $this->currentUser)) {
            throw new AccessDeniedHttpException('You are not allowed to create a new node.');
        }

        $clone = $this->stateChange->copy($entity);
        $menuLink = $this->menuHelper::getMenuLink($clone->id(), $menu);
        if ($parentId != -1) {
            $parentLink = $this->menuHelper::loadMenuLink($parentId);
            $menuLink->parent->value = $parentLink->getPluginId();
        }
        $menuLink->save();
        if ($menuLink != null) {
            $menuLink->weight->value = $weight * 10;
            $menuLink->save();
            $this->menuHelper::reorder($menuLink->parent->value, $menuLink, $menu);
            if ($recursive) {
                $root = $this->menuHelper::getMenuLink($entity->id(), $menu);
                $info = $this->_copyChildren($root, $clone, $menu);
            }
        }

        $url = $clone->urlInfo('canonical', ['absolute' => true])->toString(true);
        return new ModifiedResourceResponse(['clone' => $clone->id(), 'original' => $entity->id()], 201, ['Location' => $url->getGeneratedUrl()]);

    }

    protected function _copyChildren($root, $clone, $menu)
    {
        if ($root != null) {
            $tree = $this->menuHelper::getMenuTree($menu, $root->getPluginId());
            $this->_copyTree($tree, $clone);
        }
        return $tree;
    }

    protected function _copyTree($tree, $clone)
    {
        foreach ($tree as $entry) {
            $entity = $entry->link->getRouteParameters()['node'];
            if ($entity != $clone->id()) {
                $entity = Node::load($entity);
                $this->stateChange->copy($entity);
                $this->_copyTree($entry->subtree, $clone);
            }
        }
    }
}
