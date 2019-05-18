<?php
namespace Drupal\pagedesigner\Plugin\pagedesigner\Handler;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Markup;
use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Plugin\HandlerPluginBase;
use Drupal\pagedesigner\Service\Renderer;

class Structural extends HandlerPluginBase
{

    public function renderForPublic(Element $entity)
    {
        $entity = $entity->loadNewestPublished();
        if ($entity != null) {
            $fields = [];
            $fieldsSimple = $this->collectFields($entity, 'renderForPublic');
            foreach ($fieldsSimple as $placeholder => $value) {
                if (isset($value['#type'])) {
                    $markup = \Drupal::service('renderer')->render($value);
                    if (is_string($markup)) {
                        $fields[$placeholder] = $markup;
                    } else {
                        $fields[$placeholder] = ['#markup' => Markup::create($markup)];
                    }
                } else {
                    $fields[$placeholder] = $value;
                }
            }
            $styles = $entity->field_styles;
            foreach ($styles as $item) {
                $style = $item->entity->loadNewestPublished();
                Renderer::addStyle($style->name->value, $style->field_css->value, $entity->id());
            }
            $pattern = $entity->field_pattern->value;
            $build = [
                '#type' => 'pattern',
                '#id' => $pattern,
                '#fields' => $fields,
            ];
            $markup = \Drupal::service('renderer')->render($build);
            $markup = $this->addAttributes($markup, $entity);
            return [
                '#type' => 'inline_template',
                '#template' => '{{markup}}',
                '#context' => ['markup' => ['#markup' => Markup::create($markup)]],
                '#id' => $entity->id(),
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function render(Element $entity)
    {
        $fields = [];
        $fieldsSimple = $this->collectFields($entity, 'render');
        foreach ($fieldsSimple as $placeholder => $value) {
            if (isset($value['#type'])) {
                $markup = \Drupal::service('renderer')->render($value);
                if (is_string($markup)) {
                    $fields[$placeholder] = $markup;
                } else {
                    $fields[$placeholder] = ['#markup' => Markup::create($markup)];
                }
            } else {
                $fields[$placeholder] = $value;
            }
        }
        $styles = $entity->field_styles;
        foreach ($styles as $item) {
            $style = $item->entity;
            Renderer::addStyle(
                $style->name->value,
                $style->field_css->value,
                $entity->id()
            );
        }
        $pattern = $entity->field_pattern->value;
        $build = [
            '#type' => 'pattern',
            '#id' => $pattern,
            '#fields' => $fields,
        ];
        $elementMarkup = \Drupal::service('renderer')->render($build);
        $markup = $this->addAttributes($elementMarkup, $entity);
        return [
            '#type' => 'inline_template',
            '#template' => '{{markup}}',
            '#context' => ['markup' => ['#markup' => Markup::create($markup)]],
            '#id' => $entity->id(),
        ];
    }

    public function renderForEdit(Element $entity)
    {
        $fields = [];
        $fieldsSimple = $this->collectFields($entity, 'renderForEdit');
        foreach ($fieldsSimple as $placeholder => $value) {
            if (isset($value['#type'])) {
                $markup = \Drupal::service('renderer')->render($value);
                if (is_string($markup)) {
                    $fields[$placeholder] = $markup;
                } else {
                    $fields[$placeholder] = ['#markup' => Markup::create($markup)];
                }
            } else {
                $fields[$placeholder] = $value;
            }
        }
        $pattern = $entity->field_pattern->value;
        $build = [
            '#type' => 'pattern',
            '#id' => $pattern,
            '#fields' => $fields,
        ];
        $markup = \Drupal::service('renderer')->render($build);
        $markup = $this->addAttributes($markup, $entity, true);
        $styles = $entity->field_styles;
        foreach ($styles as $item) {
            Renderer::addStyle($item->entity->name->value, $item->entity->field_css->value, $entity->id());
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
    public function serialize(Element $entity)
    {
        $build = [
            'type' => 'structural',
            'id' => $entity->id(),
        ];
        $fields = $this->collectFields($entity, 'serialize');
        foreach ($fields as $placeholder => $value) {
            $fields[$placeholder] = $value;
        }
        $build['fields'] = $fields;
        $build['classes'] = $entity->field_classes->value;
        $styles = $entity->field_styles;
        foreach ($styles as $item) {
            $build['styles'][$item->entity->name->value] = $item->entity->field_css->value;
        }
        return $build;
    }

    protected function collectFields(Element $entity, $function = 'get')
    {
        $fields = [];
        $pattern = $entity->field_pattern->value;
        $patternDefinition = $this->_getPatternDefinition($pattern);
        if ($patternDefinition != null) {
            $handlerManager = \Drupal::service('plugin.manager.pagedesigner_handler');
            foreach ($entity->children as $item) {
                if ($item->entity != null && $item->entity->hasField('field_placeholder')) {
                    $placeholder = $item->entity->field_placeholder->value;
                    if ($patternDefinition->hasField($placeholder)) {
                        $field = $patternDefinition->getField($placeholder);
                        $handler = $handlerManager->getInstance(['type' => $field->getType()])[0];
                        $fields[$placeholder] = $handler->{$function}($item->entity);
                    }
                }
            }
        }
        return $fields;
    }

    protected function addAttributes($markup, Element $entity, $edit = false)
    {
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($markup, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOWARNING);
        libxml_clear_errors();
        $root = $dom->documentElement;
        $root->normalize();
        $root->setAttribute('id', 'pd-cp-' . $entity->id());
        $classes = '';
        $patternClasses = explode(' ', $root->getAttribute('class'));
        $styleClasses = explode(' ', $entity->field_classes->value);
        $classes = implode(' ', array_unique(array_merge($patternClasses, $styleClasses)));
        $root->setAttribute('class', $classes);
        if ($edit) {
            $root->setAttribute('data-entity-id', $entity->id());
            $root->setAttribute('data-gjs-type', $entity->field_pattern->value);
        }
        $columns = [];
        $children = $root->childNodes;
        foreach ($children as $item) {
            if (strpos($item->nodeName, '#') !== 0 && strpos($item->getAttribute('class'), 'iq-column') !== false) {
                $columns[] = $item;
            }
        }
        $i = 0;
        foreach ($entity->children as $item) {
            if ($item->entity != null && $item->entity->bundle() == 'cell') {
                if ($item->entity == null || empty($columns[$i])) {
                    continue;
                }
                $column = $columns[$i];
                if (empty($column->getAttribute('id'))) {
                    $column->setAttribute('id', 'pd-cp-' . $item->entity->id());
                }
                if ($edit) {
                    if (empty($column->getAttribute('data-entity-id'))) {
                        $column->setAttribute('data-entity-id', $item->entity->id());
                    }
                    if (empty($column->getAttribute('data-gjs-type'))) {
                        $column->setAttribute('data-gjs-type', 'cell');
                    }
                }
                $i++;

            }
        }
        return $dom->saveHTML();
    }

    /**
     * Undocumented function
     *
     * @param Element $entity
     * @return void
     */
    public function publish(Element $entity)
    {
        parent::publish($entity);
        foreach ($entity->field_styles as $style) {
            $style->entity->setPublished(true);
            $style->entity->save();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function generate($patternDefinition, $data)
    {
        $build = [];
        $build['type'] = $type = $patternDefinition->getAdditional()['type'];

        // Creating entity
        $element = parent::generate(['type' => $type, 'name' => $patternDefinition->id()], $data);
        $element->field_pattern->value = $patternDefinition->id();
        $element->parent->target_id = $data['parent'];
        $element->container->target_id = $data['container'];
        $element->saveEdit();

        // Collect return data
        $build['id'] = $element->id();
        // Creating field entities
        $handlerManager = \Drupal::service('plugin.manager.pagedesigner_handler');
        $fields = $patternDefinition->getFields();
        foreach ($fields as $key => $field) {
            // Create field info array
            $fieldData = $field->toArray();
            $fieldData['parent'] = $element->id();
            $fieldData['container'] = $data['container'];
            $fieldData['placeholder'] = $key;
            $handler = $handlerManager->getInstance(['type' => $field->getType()])[0];
            // Get handler, create field entity and add as child
            $fieldElement = $handler->generate(['type' => $field->getType()], $fieldData);
            $fieldElement->saveEdit();
            $element->children->appendItem($fieldElement);
            // Collect return data
            $returnData = $fieldData;
            $returnData['id'] = $fieldElement->id();
            $build['fields'][] = $returnData;
        }
        $element->saveEdit();

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

    public function copy(Element $entity, ContentEntityBase $container)
    {
        $clone = parent::copy($entity, $container);
        $styles = [];
        foreach ($clone->field_styles as $style) {
            $styleCopy = $style->entity->createDuplicate();
            $styleCopy->parent->entity = $clone;
            $styleCopy->container->entity = $container;
            $styles[] = $styleCopy;
        }
        $clone->field_styles->setValue($styles);
        $clone->setNewRevision(false);
        $clone->save();
        return $clone;
    }

    /**
     * {@inheritDoc}
     */
    public function patch(Element $entity, $data)
    {
        $build = [];
        $build['type'] = 'structural';
        $build['styles'] = [];
        $build['classes'] = "";
        $build['fields'] = [];
        if (!empty($data['fields'])) {
            // Updating fields
            $pattern = $entity->field_pattern->value;
            $patternDefinition = $this->_getPatternDefinition($pattern);
            if ($patternDefinition != null) {
                $handlerManager = \Drupal::service('plugin.manager.pagedesigner_handler');
                foreach ($entity->children as $item) {
                    $placeholder = $item->entity->field_placeholder->value;
                    if ($patternDefinition->hasField($placeholder)) {
                        $field = $patternDefinition->getField($placeholder);
                        $handler = $handlerManager->getInstance(['type' => $field->getType()])[0];
                        $handler->patch($item->entity, $data['fields'][$placeholder]);
                        $build['fields'][$placeholder] = $handler->serialize($item->entity);
                    }
                }
            }

            // Add missing fields
            $patternFields = $patternDefinition->getFields();
            $storedFields = array_keys($build['fields']);
            foreach ($patternFields as $field) {
                if (!in_array($field->getName(), $storedFields)) {
                    $fieldData = $field->toArray();
                    $fieldData['parent'] = $entity->id();
                    $fieldData['container'] = $entity->container->target_id;
                    $fieldData['placeholder'] = $field->getName();
                    $handler = $handlerManager->getInstance(['type' => $field->getType()])[0];
                    // Get handler, create field entity and add as child
                    $fieldElement = $handler->generate(['type' => $field->getType()], $fieldData);
                    if (!empty($data['fields'][$field->getName()])) {
                        $handler->patch($fieldElement, $data['fields'][$field->getName()]);
                    }
                    $fieldElement->saveEdit();
                    $entity->children->appendItem($fieldElement);
                }
            }
        }

        $styles = $entity->field_styles;
        $stylingPermission = \Drupal::currentUser()->hasPermission('edit pagedesigner styles');
        if (!empty($data['styles']) && $stylingPermission) {
            $saved = [];

            foreach ($styles as $item) {
                $saved[$item->entity->name->value] = $item->entity;
            }
            foreach ($data['styles'] as $key => $css) {
                $style = null;
                if (!empty($saved[$key])) {
                    $style = $saved[$key];
                } else {
                    $style = Element::create(['type' => 'style', 'name' => $key]);
                    $entity->field_styles->appendItem($style);
                }
                $style->field_css->value = $css;
                $style->parent->target_id = $entity->id();
                $style->container->target_id = $entity->container->target_id;
                $style->saveEdit();
            }
        }
        foreach ($styles as $item) {
            $build['styles'][$item->entity->name->value] = $item->entity->field_css->value;
        }
        if (!empty($data['classes'])) {
            $entity->field_classes->value = implode(' ', $data['classes']);
        }
        $build['classes'] = $entity->field_classes->value;
        $entity->saveEdit();
        return $build;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Element $entity)
    {
        parent::delete($entity);
        foreach ($entity->field_styles as $item) {
            $item->entity->deleted = true;
            $item->entity->saveEdit();
        }
    }
}
