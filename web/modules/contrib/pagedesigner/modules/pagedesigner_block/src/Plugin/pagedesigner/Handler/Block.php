<?php
namespace Drupal\pagedesigner_block\Plugin\pagedesigner\Handler;

use Drupal\block\Entity\Block as BlockEntity;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Markup;
use Drupal\pagedesigner\Definition\PatternDefinition;
use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Plugin\HandlerPluginBase;

/**
 * @PagedesignerHandler(
 *   id = "block",
 *   name = @Translation("Block handler"),
 *   types = {
 *      "block"
 *   },
 * )
 */
class Block extends HandlerPluginBase
{
    protected $definition = [
        "id" => 'block:',
        "pagedesigner" => 1,
        "icon" => "far fa-square",
        "type" => "block",
        "category" => 'Blocks',
        "label" => '',
        "description" => '',
    ];

    public function collectAttachments(&$attachments)
    {
        $attachments['library'][] = 'pagedesigner_block/pagedesigner';
    }

    /**
     * {@inheritDoc}
     */
    public function collectPatterns(&$patterns)
    {
        $blockIds = \Drupal::entityQuery('block')->condition('region', 'pagedesigner')->execute();
        foreach ($blockIds as $blockId) {
            $block = BlockEntity::load($blockId);
            if ($block != null) {
                $definition = $this->definition;
                $definition['id'] .= $blockId;
                $definition['block'] = $blockId;

                $build = \Drupal::entityTypeManager()
                    ->getViewBuilder('block')
                    ->view($block);

                $markup = \Drupal::service('renderer')->renderPlain($build);
                $markup = '<div data-gjs-type="block">' . $markup . '<div>';
                $definition['markup'] = $markup;
                $definition['label'] = (string) $block->label();
                $patterns[$definition['id']] = new PatternDefinition($definition);
            }
        }
    }

    public function render(Element $entity)
    {
        if ($entity->field_block->entity == null) {
            return [];
        }
        return \Drupal::entityManager()
            ->getViewBuilder('block')
            ->view($entity->field_block->entity);
    }

    /**
     * {@inheritdoc}
     */
    public function renderForEdit(Element $entity)
    {
        $build = $this->render($entity);
        $markup = \Drupal::service('renderer')->render($build);

        return [
            '#type' => 'inline_template',
            '#template' => '<div data-gjs-type="block" data-entity-id="{{id}}" id="{{html_id}}">{{markup}}</div>',
            '#context' => ['markup' => ['#markup' => Markup::create($markup)], 'id' => $entity->id(), 'html_id' => 'pd-cp-' . $entity->id()],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function generate($patternDefinition, $data)
    {
        $build = [];
        $build['type'] = $type = $patternDefinition->getAdditional()['type'];

        $blockId = $patternDefinition->getAdditional()['block'];
        $block = BlockEntity::load($blockId);
        if ($block == null) {
            throw new BadRequestHttpException('The given block does not exist.');
        }
        $element = Element::create(['type' => 'block', 'name' => 'block']);
        $element->field_block->entity = $block;
        $element->parent->target_id = $data['parent'];
        $element->container->target_id = $data['container'];
        $element->saveEdit();

        // Collect return data
        $build['id'] = $element->id();

        // Adding entity to parent cell if provided
        $cell = Element::load($element->parent->target_id);
        $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
        $cell = $cell->getTranslation($language);
        if ($cell != null) {
            $cell->children->appendItem($element);
            $cell->saveEdit();
        }

        return $build;

    }
}
