<?php

namespace Drupal\sl_admin_ui;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sl_admin_ui\SLAdminUIWidgetPluginInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

class SLAdminUIWidgetBase extends PluginBase implements SLAdminUIWidgetPluginInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  function contentTable() {
  }

  function content() {

    $ids = \Drupal::entityQuery('node')
      ->condition('type', $this->pluginDefinition['bundle']);

    if ($this->pluginDefinition['bundle'] == 'sl_competition_edition') {
      $ids->condition('field_sl_archived', 0);
    }

    $result = $ids->execute();

    return array(
      'title' => $this->pluginDefinition['name'],
      'description' => $this->pluginDefinition['description'],
      'current' => $this->formatPlural(count($result),'There is %items item', 'There are %items items',
        array('%items' => count($result))),
      'links' => array(
        '#theme' => 'item_list',
        '#items' => array(
          Link::fromTextAndUrl('Add new', Url::fromRoute('node.add', ['node_type' => $this->pluginDefinition['bundle']])),
          Link::fromTextAndUrl('View all', $url = Url::fromUri('internal:/admin/sports_league/sl-admin-ui-content/' . $this->pluginDefinition['bundle'] ))
        )
      ),
      'content' => $this->contentTable()
    );
  }

  /**
   * Constructs a SLAdminUIWidget object.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }
}
