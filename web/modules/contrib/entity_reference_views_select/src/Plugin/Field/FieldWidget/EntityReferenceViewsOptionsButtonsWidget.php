<?php

namespace Drupal\entity_reference_views_select\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin implementation of the 'erviews_options_buttons' widget.
 *
 * @FieldWidget(
 *   id = "erviews_options_buttons",
 *   label = @Translation("Entity Reference Views Check boxes/radio buttons"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class EntityReferenceViewsOptionsButtonsWidget extends OptionsWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The exec factory.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $viewFactory;

  /**
   * The loader.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $viewLoader;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')->getStorage('view'),
      $container->get('views.executable'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityStorageInterface $view_loader, ViewExecutableFactory $view_factory, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->viewLoader = $view_loader;
    $this->viewFactory = $view_factory;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $options = $this->getOptions($items->getEntity());
    $selected = $this->getSelectedOptions($items);
    if ($this->getFieldSettings()['handler'] == 'views') {
      $view = $this->viewFactory->get($this->viewLoader->load($this->getFieldSettings()['handler_settings']['view']['view_name']));
      $view->setArguments($this->getFieldSettings()['handler_settings']['view']['arguments']);
      $view->execute($this->getFieldSettings()['handler_settings']['view']['display_name']);
      $filter_options = [];
      foreach ($view->result as $row) {
        $row_output = $view->style_plugin->view->rowPlugin->render($row);
        $filter_options[$row->_entity->id()] = $options[$row->_entity->id()]->create($this->renderer->render($row_output));
      }
      $options = $filter_options;
    }
    // If required and there is one single option, preselect it.
    if ($this->required && count($options) == 1) {
      reset($options);
      $selected = [key($options)];
    }

    if ($this->multiple) {
      $element += [
        '#type' => 'checkboxes',
        '#default_value' => $selected,
        '#options' => $options,
      ];
    }
    else {
      $element += [
        '#type' => 'radios',
        // Radio buttons need a scalar value. Take the first default value, or
        // default to NULL so that the form element is properly recognized as
        // not having a default value.
        '#default_value' => $selected ? reset($selected) : NULL,
        '#options' => $options,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    if (!$this->required && !$this->multiple) {
      return t('N/A');
    }
  }

}
