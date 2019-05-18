<?php

namespace Drupal\pagedesigner\Plugin\pagedesigner;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\pagedesigner\Entity\Element;
use Drupal\ui_patterns\Definition\PatternDefinitionField;
use Drupal\Core\Entity\ContentEntityBase;

interface HandlerPluginInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface
{
    public function collectAttachments(&$attachments);

    /**
     * Allows to add dynamic patterns to the list.
     *
     * @param \Drupal\ui_patterns\Definition\PatternDefinition[] $patterns
     * @return void
     */
    public function collectPatterns(&$patterns);

    /**
     * Processes a field.
     *
     * @param \Drupal\ui_patterns\Definition\PatternDefinitionField $field
     * @param array $fieldArray
     * @return void
     */
    public function prepare(PatternDefinitionField &$field, &$fieldArray);

    /**
     * Get the field value.
     *
     * @param \Drupal\pagedesigner\Entity\Element $entity
     * @param string $state
     * @return mixed
     */
    public function get(Element $entity);

    /**
     * Get the field value.
     *
     * @param \Drupal\pagedesigner\Entity\Element $entity
     * @param string $state
     * @return void
     */
    public function patch(Element $entity, $data);

    /**
     * Generate
     *
     * @param array $value
     * @param string $state
     * @return void
     */
    public function generate($definition, $data);

    /**
     * Generate
     *
     * @param array $value
     * @param string $state
     * @return void
     */
    public function copy(Element $entity, ContentEntityBase $container);

    /**
     * Generate
     *
     * @param \Drupal\pagedesigner\Entity\Element $entity
     * @param string $state
     * @return void
     */
    public function delete(Element $entity);

    /**
     * Processes the node.
     *
     * @param Element $entity
     * @param array $children
     * @return void
     */
    public function render(Element $entity);
    /**
     * Processes the node.
     *
     * @param Element $entity
     * @param array $children
     * @return void
     */
    public function renderForPublic(Element $entity);

    /**
     * Processes the node.
     *
     * @param Element $entity
     * @param array $children
     * @return void
     */
    public function renderForEdit(Element $entity);
}
