<?php
namespace Drupal\pagedesigner\Plugin\pagedesigner\Handler;

use Drupal\Core\Render\Markup;
use Drupal\pagedesigner\Entity\Element;

/**
 * @PagedesignerHandler(
 *   id = "cell",
 *   name = @Translation("Cell renderer"),
 *   types = {
 *     "cell",
 *   },
 * )
 */
class Cell extends Standard
{

    /**
     * {@inheritDoc}
     */
    public function renderForPublic(Element $entity)
    {
        $entity = $entity->loadNewestPublished();
        if ($entity != null) {
            $markup = '';
            foreach ($entity->children as $item) {
                $handler = $this->handlerManager->getInstance(['type' => $item->entity->bundle()])[0];
                $build = $handler->renderForPublic($item->entity);
                $markup .= \Drupal::service('renderer')->render($build);
            }
            return [
                '#type' => 'inline_template',
                '#template' => '{{markup}}',
                '#context' => ['markup' => ['#markup' => Markup::create($markup)]],
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function render(Element $entity)
    {
        $markup = '';
        foreach ($entity->children as $item) {
            $handler = $this->handlerManager->getInstance(['type' => $item->entity->bundle()])[0];
            $build = $handler->render($item->entity);
            $markup .= \Drupal::service('renderer')->render($build);
        }
        return [
            '#type' => 'inline_template',
            '#template' => '{{markup}}',
            '#context' => ['markup' => ['#markup' => Markup::create($markup)]],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function renderForEdit(Element $entity)
    {
        $markup = '';
        foreach ($entity->children as $item) {
            $handler = $this->handlerManager->getInstance(['type' => $item->entity->bundle()])[0];
            $build = $handler->renderForEdit($item->entity);
            $markup .= \Drupal::service('renderer')->render($build);
        }
        return [
            '#type' => 'inline_template',
            '#template' => '{{markup}}',
            '#context' => ['markup' => ['#markup' => Markup::create($markup)]],
        ];
    }

    public function get(Element $entity)
    {
        return $entity->id();
    }

    public function serialize(Element $entity)
    {
        $list = [];
        foreach ($entity->children as $item) {
            if ($item->entity != null) {
                $list[] = $item->entity->id();
            }
        }
        return $list;
    }

    /**
     * {@inheritDoc}
     */
    public function patch(Element $entity, $data)
    {

        // Defining build array
        $build = [];
        $build['type'] = 'cell';
        $build['order'] = [];

        if (isset($data['order'])) {
            $entity->children->setValue(array());

            foreach ($data['order'] as $target_id) {
                $entity->children->appendItem($target_id);
                $build['order'][] = $target_id;
            }
            foreach ($entity->children as $item) {
                $item->entity->parent->entity = $entity;
                $item->entity->save();
            }
            $entity->saveEdit();
        }
        return $build;
    }
}
