<?php

namespace Drupal\rel_content\Plugin\views\filter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters by given list of node title options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("related_content_filter")
 */
class RelatedContentFilter extends FilterPluginBase {

  /**
   * Related content manager.
   *
   * @var \Drupal\rel_content\RelatedContentPluginManager
   */
  protected $relatedContentManager;
  /**
   * Current node.
   *
   * @var \Drupal\node\Entity\Node
   */
  public $currentNode;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $manager, $node) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->relatedContentManager = $manager;
    $this->currentNode = $node;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.rel_content'),
      // TODO change
      \Drupal::routeMatch()->getParameter('node')
    );
  }


  public function adminSummary() { }
  protected function operatorForm(&$form, FormStateInterface $form_state) { }
  public function canExpose() {
    return FALSE;
  }
  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Allowed node titles');
  }

  /**
   * Override the query so that no filtering takes place if the user doesn't
   * select any options.
   */
  public function query() {
    if (!empty($this->value)) {
      parent::query();
    }

    if(empty($this->currentNode)) {
      return;
    }

    foreach ($this->currentNode->getFields() as $field) {
      // TODO change hardcode.
      if ('list_rel_content' == $field->getFieldDefinition()->getType()) {
        foreach ($field as $delta => $item) {
          list($plugin_machine_name, $id) = explode(':', $item->value);
          $instance = $this->relatedContentManager->createInstance($plugin_machine_name, [
            'items' => $field,
            'delta' => $delta,
            'id' => $id,
          ]);
          $instance->viewsAlteration($this);
        }
      }
    }

  }

  /**
   * Skip validation if no options have been chosen so we can use it as a
   * non-filter.
   */
  public function validate() {
    if (!empty($this->value)) {
      parent::validate();
    }
  }

}
