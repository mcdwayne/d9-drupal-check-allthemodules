<?php

namespace Drupal\layout_config_block\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Block\BlockManagerInterface;

/**
 * Class LayoutBlockForm.
 */
class LayoutBlockForm extends EntityForm {

  /**
   * Store the plugin.manager.core.layout service.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $pluginManagerCoreLayout;

  /**
   * Store the plugin.manager.block service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $pluginManagerBlock;

  /**
   * {@inheritdoc}
   */
  public function __construct(BlockManagerInterface $plugin_manager_block, LayoutPluginManagerInterface $plugin_manager_core_layout) {
    $this->pluginManagerCoreLayout = $plugin_manager_core_layout;
    $this->pluginManagerBlock = $plugin_manager_block;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get("plugin.manager.block"),
      $container->get("plugin.manager.core.layout")
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $layout_block = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $layout_block->label(),
      '#description' => $this->t("Label for the Layout block."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $layout_block->id(),
      '#machine_name' => [
        'exists' => '\Drupal\layout_config_block\Entity\LayoutBlock::load',
      ],
      '#disabled' => !$layout_block->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */
    $layout_options = array_map(function ($layout_def) {
      return $layout_def->getLabel();
    }, $this->pluginManagerCoreLayout->getDefinitions());
    $layout_options = array_merge([0 => "--Select Layout--"], $layout_options);
    $form['layout'] = [
      '#type' => 'select',
      '#default_value' => $form_state->getValue("layout", $layout_block->get("layout")),
      '#options' => $layout_options,
      '#title' => $this->t('Layout'),
      '#ajax' => [
        'callback' => '::regionsAjaxCallback',
        'event' => 'change',
        'wrapper' => 'edit-regions-wrapper',
      ],
    ];
    $form['regions'] = $this->regionForm($form, $form_state);
    return $form;
  }

  /**
   * Helper function to generate block options array.
   */
  protected function getBlockSelectOptions() {
    $options = array_map(function ($def) {
      return $def['admin_label'];
    }, $this->pluginManagerBlock->getSortedDefinitions());
    return array_merge([0 => "--Empty--"], $options);
  }

  /**
   * Helper function to build the region field.
   */
  protected function regionForm(array &$form, FormStateInterface $form_state) {
    $layout_block = $this->entity;
    $region_form = [
      '#type' => 'item',
      '#title' => $this->t("Regions"),
      '#tree' => TRUE,
      '#prefix' => "<div id='edit-regions-wrapper'>",
      '#suffix' => "</div",
    ];
    if ($layout_value = $form_state->getValue("layout", $layout_block->get("layout"))) {
      $layout_def = $this->pluginManagerCoreLayout->getDefinition($layout_value);
      $region_data = $form_state->getValue("regions", $layout_block->get("regions"));
      foreach ($layout_def->getRegions() as $region => $data) {
        $region_form[$region] = [
          '#type' => 'item',
          '#title' => $data['label'],
          '#tree' => TRUE,
        ];
        $region_data[$region][] = 0;
        foreach ($region_data[$region] as $weight => $id) {
          $region_form[$region][$weight] = [
            '#type' => 'select',
            '#options' => $this->getBlockSelectOptions(),
            '#default_value' => $id,
            // Ajax refresh the block so we can clean up and add new add block
            // option.
            '#ajax' => [
              'callback' => '::regionsAjaxCallback',
              'event' => 'change',
              'wrapper' => 'edit-regions-wrapper',
            ],
          ];
        }
      }
    }
    return $region_form;

  }

  /**
   * Callback function to refresh the regions.
   */
  public function regionsAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['regions'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Filter out any block that have been set to Empty.
    $regions = array_map(function ($blocks) {
      return array_filter($blocks);
    }, $form_state->getValue('regions'));
    $form_state->setValue('regions', $regions);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $layout_block = $this->entity;
    $status = $layout_block->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Layout block.', [
          '%label' => $layout_block->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Layout block.', [
          '%label' => $layout_block->label(),
        ]));
    }
    $form_state->setRedirectUrl($layout_block->toUrl('collection'));
  }

}
