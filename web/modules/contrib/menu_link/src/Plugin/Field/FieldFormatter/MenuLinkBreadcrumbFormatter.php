<?php

namespace Drupal\menu_link\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'menu_link_breadcrumb' formatter.
 *
 * @FieldFormatter(
 *   id = "menu_link_breadcrumb",
 *   label = @Translation("Menu link breadcrumb"),
 *   field_types = {
 *     "menu_link",
 *   }
 * )
 */
class MenuLinkBreadcrumbFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

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
   * Constructs a MenuLinkBreadcrumbFormatter object.
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

    $options['parents_only'] = FALSE;
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
      '#title' => $this->t('Enable links'),
      '#description' => $this->t('Enable this for links in the breadcrumb, or disable this for a plain text breadcrumb.'),
      '#default_value' => $this->getSetting('link_to_target'),
    ];

    $form['parents_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only show the parents of the menu link'),
      '#description' => $this->t('Enable this only show the parents of the menu link, hiding the men link itself from the breadcrumb trail.'),
      '#default_value' => $this->getSetting('parents_only'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('link_to_target')) {
      $summary[] = $this->t('Links enabled');
    }
    if ($this->getSetting('parents_only')) {
      $summary[] = $this->t('Parents only');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $menu_links = [];
      $id = $items[$delta]->getMenuPluginId();
      $parent_ids = $this->menuLinkManager->getParentIds($id);
      if (!empty($parent_ids)) {
        foreach ($parent_ids as $parent_id) {
          if (!$this->getSetting('parents_only') || $parent_id != $id) {
            $menu_links[] = $this->menuLinkManager->createInstance($parent_id);
          }
        }
      }
      $menu_links = array_reverse($menu_links);
      // Get the links to add to the breadcrumb.
      $links = array_map(function ($link) {
        if ($this->getSetting('link_to_target')) {
          $url = $link->getUrlObject();
        }
        else {
          // Set URL to none if we don't want to link to the menu link target.
          $url = new Url('<none>');
        }
        return Link::fromTextAndUrl($link->getTitle(), $url);
      }, $menu_links);

      // Set up the breadcrumb.
      $breadcrumb = new Breadcrumb();
      $breadcrumb->setLinks($links);
      $elements[$delta] = $breadcrumb->toRenderable();
    }
    return $elements;
  }


}
