<?php

namespace Drupal\menu_link\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Link;
use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'menu_link' formatter.
 *
 * @FieldFormatter(
 *   id = "menu_link",
 *   label = @Translation("Menu link"),
 *   field_types = {
 *     "menu_link",
 *   }
 * )
 */
class MenuLinkFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * @var MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.menu.link')
    );
  }

  /**
   * Constructs a MenuLinkFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings,  $menu_link_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();

    $options['link_to_target'] = TRUE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['link_to_target'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable link'),
      '#description' => $this->t('Enable this for a link, or disable this for a plain text menu link title.'),
      '#default_value' => $this->getSetting('link_to_target'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('link_to_target')) {
      $summary[] = $this->t('Link enabled');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $id = $items[$delta]->getMenuPluginId();
      $link = $this->menuLinkManager->createInstance($id);
      if ($this->getSetting('link_to_target')) {
        $elements[$delta] = Link::fromTextAndUrl($link->getTitle(), $link->getUrlObject())->toRenderable();
      }
      else {
        // Set URL to none if we don't want to link to the menu link target.
        $elements[$delta] = ['#markup' => new HtmlEscapedText($link->getTitle())];
      }
    }
    return $elements;
  }


}
