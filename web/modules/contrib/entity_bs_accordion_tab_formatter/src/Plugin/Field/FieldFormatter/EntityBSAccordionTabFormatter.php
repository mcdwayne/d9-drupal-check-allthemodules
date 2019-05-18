<?php

namespace Drupal\entity_bs_accordion_tab_formatter\Plugin\Field\FieldFormatter;

use Drupal\Field\Entity\FieldConfig;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'entity_bs_accordion_tab_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_bs_accordion_tab_formatter",
 *   label = @Translation("Entity Bootstrap Accordion Tab formatter"),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions"
 *   }
 * )
 */
class EntityBSAccordionTabFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldConfig $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityManagerInterface $entityManager, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityManager = $entityManager;
    $this->entityTypeManager = $entityTypeManager;
  }

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
      $container->get('entity.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
    // Implement default settings.
      'tab_title' => '',
      'tab_body' => '',
      'style' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Implements the settings form.
    $field_settings = $this->getFieldSettings();
    $entity_type_id = $field_settings['target_type'];
    $bundles = $field_settings['handler_settings']['target_bundles'];
    $fields_style = array_keys($this->getEntityFields($form['#entity_type'], $form['#bundle']));
    foreach ($bundles as $bundle) {
      $fields_title = $fields_body = array_keys($this->getEntityFields($entity_type_id, $bundle));
    }
    if (!empty($fields_title)) {
      array_unshift($fields_title, 'title');
      $fields_title = array_combine($fields_title, $fields_title);
      $fields_body = array_combine($fields_body, $fields_body);
      $fields_style = array_combine($fields_style, $fields_style);
    }
    $elements['entity_type_id'] = [
      '#value' => $entity_type_id,
    ];
    $elements['tab_title'] = [
      '#type' => 'select',
      '#options' => $fields_title,
      '#title' => $this->t('Select the title field.'),
      '#default_value' => $this->getSetting('tab_title'),
      '#required' => TRUE,
    ];
    $elements['tab_body'] = [
      '#type' => 'select',
      '#options' => $fields_body,
      '#title' => $this->t('Select the body field.'),
      '#default_value' => $this->getSetting('tab_body'),
    ];
    $elements['style'] = [
      '#type' => 'select',
      '#options' => $fields_style,
      '#title' => $this->t('Select the display style field.'),
      '#default_value' => $this->getSetting('style'),
    ];
    return $elements + parent::settingsForm($form, $form_state);
  }

  /**
   * Helper function.
   */
  private function getEntityFields($entity_type_id, $bundle) {
    $fields = [];
    if (!empty($entity_type_id)) {
      $fields = array_filter(
        $this->entityManager->getFieldDefinitions($entity_type_id, $bundle), function ($field_definition) {
            return $field_definition instanceof FieldConfig;
        }
      );
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $config = \Drupal::config('entity_bs_accordion_tab_formatter.settings');
    $bs = $config->get('bootstrap_version');
    $elements = [];
    $title_field = $this->getSetting('tab_title');
    $body_field = $this->getSetting('tab_body');
    $style_field = $this->getSetting('style');
    $entity_type_id = $this->getFieldSettings()['target_type'];
    if ($parent = $items->getParent()) {
      $content_parent = $parent->getValue();
      $style = $content_parent->get($style_field)->getValue()[0]['value'];
      $component_id = $style . $content_parent->get('id')->getValue()[0]['value'];
    }
    $tabs = [];
    $first = TRUE;
    foreach ($items as $delta => $item) {
      $id = $item->getValue()['target_id'];
      if ($id) {
        $content = $this->entityTypeManager->getStorage($entity_type_id)->load($id);
        $title = $content->get($title_field)->getValue()[0]['value'];
        $body = check_markup($content->get($body_field)->getValue()[0]['value'], $content->get($body_field)->getValue()[0]['format']);

        switch ($style) {
          case 'tab':
          $theme = 'entity_bs_tab_formatter';
          if ($bs == 'bs4') {
            $li_attributes = [
              'class' => ['nav-item'],
            ];
            $header_attributes = [
              'role' => 'tab',
              'data-toggle' => 'tab',
              'href' => '#' . $style . $id,
              'aria-controls' => $style . $id,
              'class' => $first ? ['nav-link', 'active'] : ['nav-link'],
            ];
          }
          else {
            $li_attributes = [
              'role' => 'presentation',
              'class' => $first ? ['active'] : NULL,
            ];
            $header_attributes = [
              'role' => 'tab',
              'data-toggle' => 'tab',
              'href' => '#' . $style . $id,
              'aria-controls' => $style . $id,
            ];
          }
          $body_attributes = [
            'role' => 'tabpanel',
            'class' => $first ? ['tab-pane', 'active'] : ['tab-pane'],
            'id' => $style . $id,
          ];
          break;

          case 'accordion':
          case 'accordion_closed':
          default:
          $theme = 'entity_bs_accordion_formatter';
          $li_attributes = [];
          $header_attributes = [
            'aria-expanded' => $first && $style != 'accordion_closed' ? 'true' : 'false',
            'data-toggle' => 'collapse',
            'data-parent' => '#' . $component_id,
            'aria-controls' => 'c' . $style . $id,
          ];
          $body_attributes = [
            'id' => 'c' . $style . $id,
            'class' => [
            'collapse',
            ],
            'role' => 'tabpanel',
            'aria-labelledby' => $style . $id,
            'data-parent' => '#' . $component_id,
            ];
            if ($bs == 'bs4') {
              $header_attributes['data-target'] = '#c' . $style . $id;
              $body_attributes['class'][] = $first && $style != 'accordion_closed' ? 'in show' : '';
            }
            else {
              $header_attributes['href'] = '#c' . $style . $id;
              $body_attributes['class'][] = 'panel-collapse';
              $body_attributes['class'][] = $first && $style != 'accordion_closed' ? 'in' : '';
            }
            break;
          }

          $tabs[$id] = [
          'id' => $style . $id,
          'li_attributes' => $li_attributes,
          'header_attributes' => $header_attributes,
          'body_attributes' => $body_attributes,
          'title' => $title,
          'body' => $body,
          ];
          $first = FALSE;
      }
    }
    switch ($style) {
      case 'tab':
        $theme = 'entity_bs_tab_formatter';
        break;

      case 'accordion':
      default:
        $theme = 'entity_bs_accordion_formatter';
        break;
    }
    $elements[$delta] = [
      '#theme' => $theme,
      '#tabs' => $tabs,
      '#attributes' => [
        'id' => $component_id,
      ],
    ];
    if ($style == 'tab' && $bs == 'bs3') {
      $elements[$delta]['#attached']['library'][] = 'entity_bs_accordion_tab_formatter/tabs';
    }

    return $elements;
  }

}
