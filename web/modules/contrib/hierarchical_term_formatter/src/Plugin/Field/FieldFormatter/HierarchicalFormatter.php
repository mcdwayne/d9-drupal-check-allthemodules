<?php

namespace Drupal\hierarchical_term_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'rating' formatter.
 *
 * @FieldFormatter(
 *   id = "hierarchical_term_formatter",
 *   label = @Translation("Hierarchical Term Formatter"),
 *   description = @Translation("Provides hierarchical term formatters for taxonomy reference fields."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class HierarchicalFormatter extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The image style entity storage.
   *
   * @var \Drupal\taxonomy\TermStorage
   */
  protected $taxonomyTermStorage;

  /**
   * Constructs a HierarchicalFormatter object.
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
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityStorageInterface $taxonomy_term_storage
   *   The Taxonomy Term storage.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityStorageInterface $taxonomy_term_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->taxonomyTermStorage = $taxonomy_term_storage;
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
      $container->get('entity_type.manager')->getStorage('taxonomy_term')
    );
  }

  /**
   * Returns a list of supported display options.
   *
   * @return array
   *   An array whose keys are display machine names
   *   and whose values are their labels.
   */
  private function displayOptions() {
    return [
      'all' => $this->t('The selected term and all of its parents'),
      'parents' => $this->t('Just the parent terms'),
      'root' => $this->t('Just the topmost/root term'),
      'nonroot' => $this->t('Any non-topmost/root terms'),
      'leaf' => $this->t('Just the selected term'),
    ];
  }

  /**
   * Returns a list of supported wrapping options.
   *
   * @return array
   *   An array whose keys are wrapper machine names
   *   and whose values are their labels.
   */
  private function wrapOptions() {
    return [
      'none' => $this->t('None'),
      'span' => $this->t('@tag elements', ['@tag' => '<span>']),
      'div' => $this->t('@tag elements', ['@tag' => '<div>']),
      'ul' => $this->t('@tag elements surrounded by a @parent_tag', [
        '@tag' => '<li>',
        '@parent_tag' => '<ul>',
      ]),
      'ol' => $this->t('@tag elements surrounded by a @parent_tag', [
        '@tag' => '<li>',
        '@parent_tag' => '<ol>',
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display' => 'all',
      'link' => FALSE,
      'wrap' => 'none',
      'separator' => ' » ',
      'reverse' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for taxonomy terms.
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'taxonomy_term';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['display'] = [
      '#title' => $this->t('Terms to display'),
      '#description' => $this->t('Choose what terms to display.'),
      '#type' => 'select',
      '#options' => $this->displayOptions(),
      '#default_value' => $this->getSetting('display'),
      '#required' => FALSE,
    ];
    $form['link'] = [
      '#title' => $this->t('Link each term'),
      '#description' => $this->t('If checked, the terms will link to their corresponding term pages.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('link'),
    ];
    $form['reverse'] = [
      '#title' => $this->t('Reverse order'),
      '#description' => $this->t('If checked, children display first, parents last.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('reverse'),
    ];
    $form['wrap'] = [
      '#title' => $this->t('Wrap each term'),
      '#description' => $this->t('Choose what type of html elements you would like to wrap the terms in, if any.'),
      '#type' => 'select',
      '#options' => $this->wrapOptions(),
      '#default_value' => $this->getSetting('wrap'),
      '#required' => FALSE,
    ];
    $form['separator'] = [
      '#title' => $this->t('Separator'),
      '#description' => $this->t('Enter some text or markup that will separate each term in the hierarchy. Leave blank for no separator. Example: <em>»</em>'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('separator'),
      '#required' => FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $display_options = $this->displayOptions();
    $order = $this->getSetting('reverse') ? $this->t('Reverse') : $this->t('Natural');
    $summary = [];
    $summary[] = $this->t('Display: %display as %format.', [
      '%display' => $display_options[$this->getSetting('display')],
      '%format' => $this->getSetting('link') ? $this->t('links') : $this->t('plain text'),
    ]);
    $summary[] = $this->t('Wrapper: @wrapper.', ['@wrapper' => $this->getSetting('wrap')]);
    $summary[] = $this->t('Order: %order.', ['%order' => $order]);
    $summary[] = $this->t('Separator: "%separator".', ['%separator' => $this->getSetting('separator')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = $used = [];
    foreach ($items as $delta => $item) {
      $item_value = $item->getValue();
      $tid = $item_value['target_id'];
      $term_tree = [];
      switch ($this->getSetting('display')) {
        case 'leaf':
          $term = $this->taxonomyTermStorage->load($tid);
          $term_tree = [$term];
          break;

        case 'root':
          $parents = $this->taxonomyTermStorage->loadAllParents($tid);
          if (!empty($parents)) {
            $term_tree = [array_pop($parents)];
            if (isset($used[$term_tree[0]->id()])) {
              $term_tree = [];
              break;
            }
            $used[$term_tree[0]->id()] = TRUE;
          }
          break;

        case 'parents':
          $term_tree = array_reverse($this->taxonomyTermStorage->loadAllParents($tid));
          array_pop($term_tree);
          break;

        case 'nonroot':
          $parents = $this->taxonomyTermStorage->loadAllParents($tid);
          if (count($parents) > 1) {
            $term_tree = array_reverse($parents);
            // This gets rid of the first topmost term.
            array_shift($term_tree);
            // Terms can have multiple parents. Now remove any remaining topmost
            // terms.
            foreach ($term_tree as $key => $term) {
              $has_parents = $this->taxonomyTermStorage->loadAllParents($term->id());
              // This has no parents and is topmost.
              if (empty($has_parents)) {
                unset($term_tree[$key]);
              }
            }
          }
          break;

        default:
          $term_tree = array_reverse($this->taxonomyTermStorage->loadAllParents($tid));
          break;
      }

      // Change output order if Reverse order is checked.
      if ($this->getSetting('reverse') && count($term_tree)) {
        $term_tree = array_reverse($term_tree);
      }

      // Remove empty elements caused by discarded items.
      $term_tree = array_filter($term_tree);

      if (!$term_tree) {
        break;
      }

      foreach ($term_tree as $index => $term) {
        if ($term->hasTranslation($langcode)) {
          $term_tree[$index] = $term->getTranslation($langcode);
        }
      }

      $elements[$delta] = [
        '#theme' => 'hierarchical_term_formatter',
        '#terms' => $term_tree,
        '#wrapper' => $this->getSetting('wrap'),
        '#separator' => $this->getSetting('separator'),
        '#link' => ($this->getSetting('link')) ? TRUE : FALSE,
      ];
    }

    return $elements;
  }

}
