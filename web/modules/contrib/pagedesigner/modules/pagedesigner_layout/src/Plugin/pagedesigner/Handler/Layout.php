<?php
namespace Drupal\pagedesigner_layout\Plugin\pagedesigner\Handler;

use Drupal\pagedesigner\Definition\PatternDefinition;
use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Plugin\pagedesigner\Handler\Standard;

/**
 * @PagedesignerHandler(
 *   id = "layout",
 *   name = @Translation("Layout handler"),
 *   types = {
 *      "layout"
 *   },
 * )
 */
class Layout extends Standard
{
    protected $definition = [
        "id" => 'layout',
        "pagedesigner" => 1,
        "icon" => "far fa-sticky-note",
        "type" => "layout",
        "category" => 'layouts',
        "label" => '',
        "description" => '',
    ];

    public function collectAttachments(&$attachments)
    {
        $attachments['library'][] = 'pagedesigner_layout/pagedesigner';
    }

    /**
     * {@inheritDoc}
     */
    public function collectPatterns(&$patterns)
    {
        $nids = \Drupal::entityQuery('pagedesigner_element')->condition('type', 'layout')->execute();
        foreach ($nids as $nid) {
            $layout = Element::load($nid);
            if ($layout != null) {
                $root = $layout->children->entity;
                if ($root != null) {
                    $definition = $this->definition;
                    $definition['id'] .= $nid;
                    $definition['layout'] = $nid;
                    // $handler = $this->handlerManager->getInstance(['type' => $root->bundle()])[0];
                    // $build = $handler->renderForEdit($root);
                    $definition['markup'] = '<div></div>';//\Drupal::service('renderer')->renderRoot($build);
                    $definition['label'] = $layout->name->value;
                    //echo $layout->name->value .' __ ' . "\n";
                    // $definition['description'] = $layout->field_content->value;
                    $patterns[$definition['id']] = new PatternDefinition($definition);
                }
            }
        }
    }

    public function render(Element $entity)
    {
        return [];
        // return \Drupal::entityManager()
        //     ->getViewBuilder('block')
        //     ->view($entity->field_block->entity);
    }

    /**
     * {@inheritdoc}
     */
    public function renderForEdit(Element $entity)
    {
        return [];

        // $build = $this->render($entity);
        // $markup = \Drupal::service('renderer')->render($build);

        // return [
        //     '#type' => 'inline_template',
        //     '#template' => '<div data-gjs-type="block" data-entity-id="{{id}}" id="{{html_id}}">{{markup}}</div>',
        //     '#context' => ['markup' => ['#markup' => Markup::create($markup)], 'id' => $entity->id(), 'html_id' => 'pd-cp-' . $entity->id()],
        // ];
    }

}
