<?php
namespace Drupal\pagedesigner\Plugin\pagedesigner\Handler;

use Drupal\Core\Render\Markup;
use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Plugin\HandlerPluginBase;

/**
 * @PagedesignerHandler(
 *   id = "container",
 *   name = @Translation("Container render"),
 *   types = {
 *     "container",
 *   },
 * )
 */
class Container extends HandlerPluginBase
{

    public function renderForPublic(Element $entity)
    {
        $entity = $entity->loadNewestPublished();
        if ($entity != null) {
            $markup = '';
            foreach ($entity->children as $item) {
                if ($item->entity != null) {
                    $handler = $this->handlerManager->getInstance(['type' => $item->entity->bundle()])[0];
                    $build = $handler->renderForPublic($item->entity);
                    $markup .= \Drupal::service('renderer')->render($build);
                }
            }
            return [
                '#type' => 'inline_template',
                '#template' => '<section class="pd-content pd-live container-fluid">{{markup}}</section>',
                '#context' => ['markup' => ['#markup' => Markup::create($markup)]],
            ];
        }
    }

    public function render(Element $entity)
    {
        $markup = '';
        foreach ($entity->children as $item) {
            if ($item->entity != null) {
                $handler = $this->handlerManager->getInstance(['type' => $item->entity->bundle()])[0];
                $build = $handler->render($item->entity);
                $markup .= \Drupal::service('renderer')->render($build);
            }
        }
        return [
            '#type' => 'inline_template',
            '#template' => '<section class="pd-content pd-live container-fluid">{{markup}}</section>',
            '#context' => ['markup' => ['#markup' => Markup::create($markup)]],
        ];
    }

    public function renderForEdit(Element $entity)
    {
        $markup = '';
        foreach ($entity->children as $item) {
            if ($item->entity != null) {
                $handler = $this->handlerManager->getInstance(['type' => $item->entity->bundle()])[0];
                $build = $handler->renderForEdit($item->entity);
                $markup .= \Drupal::service('renderer')->render($build);
            }
        }
        return [
            '#type' => 'inline_template',
            '#template' => '<section data-grapes-block="content" data-gjs-draggable="false" data-gjs-droppable="true" data-entity-id="{{entity}}" data-gjs-type="container" class="pd-content pd-edit container-fluid">{{markup}}</section>',
            '#context' => ['markup' => ['#markup' => Markup::create($markup)], 'entity' => $entity->id()],
        ];
    }

    public function serialize(Element $entity)
    {
        $list = [];
        foreach ($entity->children as $item) {
            $list[] = $item->entity->id();
        }
        return $list;
    }

    /**
     * {@inheritDoc}
     */
    public function patch(Element $entity, $data)
    {
        $build = [];
        $build['type'] = 'container';
        $build['order'] = [];
        if (isset($data['order'])) {
            // Defining build array
            $entity->children->setValue(array());
            foreach ($data['order'] as $target_id) {
                $entity->children->appendItem($target_id);
                $build['order'][] = $target_id;
            }
            $entity->saveEdit();
        }
        return $build;
    }

}
