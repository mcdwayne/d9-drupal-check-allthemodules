<?php

namespace Drupal\paragraphs_collection\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\paragraphs_collection\StyleDiscoveryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for revision overview page.
 */
class StylesOverviewForm extends ConfigFormBase {

  /**
   * The discovery service for style files.
   *
   * @var \Drupal\paragraphs_collection\StyleDiscoveryInterface
   */
  protected $styleDiscovery;

  /**
   * Wrapper object for simple configuration from diff.settings.yml.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a RevisionOverviewForm object.
   *
   * @param \Drupal\paragraphs_collection\StyleDiscoveryInterface $style_discovery
   *   The discovery service for style files.
   */
  public function __construct(StyleDiscoveryInterface $style_discovery) {
    $this->config = $this->config('paragraphs_collection.settings');
    $this->styleDiscovery = $style_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('paragraphs_collection.style_discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['paragraphs_collection.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'paragraphs_collection_styles_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $grouped_styles = NULL) {
    $group_options = $this->styleDiscovery->getStyleGroupsLabel();
    asort($group_options);
    $empty_option = ['' => '- All -'];

    $filters = [
      '#type' => 'fieldset',
      '#attributes' => [
        'class' => ['table-filter', 'js-show', 'form--inline'],
        'data-table' => '.paragraphs-collection-overview-table',
      ],
      '#title' => $this->t('Filter'),
    ];
    $filters['group'] = [
      '#type' => 'select',
      '#title' => $this->t('Group'),
      '#options' => $empty_option + $group_options,
      '#attributes' => [
        'class' => ['table-filter-group-select'],
      ],
    ];
    $filters['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Style label or ID'),
      '#size' => 40,
      '#attributes' => [
        'class' => ['table-filter-text'],
        'autocomplete' => 'off',
        'title' => $this->t('Enter a part of the style label or ID to filter by.'),
      ],
    ];

    $header = [
      'enabled' => $this->t('Style'),
      'details' => $this->t('Details'),
      'use' => $this->t('Used in'),
    ];

    $styles = $this->styleDiscovery->getStyles();
    $enabled_styles = $this->config->get('enabled_styles');
    $rows = [];
    foreach ($grouped_styles as $style_id => $value) {
      $style = $styles[$style_id];

      $paragraphs_type_link_list = [];
      foreach ($value as $paragraphs_type_id => $paragraphs_type) {
        /** @var \Drupal\paragraphs\Entity\ParagraphsType $paragraphs_type */

        if($paragraphs_type_link_list != []) {
          $paragraphs_type_link_list[] = ['#plain_text' => ', '];
        }

        $paragraphs_type_link_list[] = [
          '#type' => 'link',
          '#title' => $paragraphs_type->label(),
          '#url' => $paragraphs_type->toUrl(),
          '#attributes' => [
            'class' => ['table-filter-paragraphs-type-source'],
          ],
        ];
      }

      $group_list = [];
      foreach ($style['groups'] as $group) {
        if ($group_list != []) {
          $group_list[] = ['#plain_text' => ', '];
        }
        $group_list[] = [
          '#type' => 'container',
          '#plain_text' => $group,
          '#attributes' => ['class' => ['table-filter-group-source']],
        ];
      }

      $rows[$style_id]['enabled'] = array(
        '#type' => 'checkbox',
        '#title' => $style['title'],
        '#title_display' => 'after',
        '#default_value' => in_array($style_id, $enabled_styles) ?: FALSE,
      );
      $rows[$style_id]['details'] = [
        '#type' => 'details',
        '#title' => !empty($style['description']) ? $style['description'] : $this->t('Description not available.'),
        '#open' => FALSE,
        '#attributes' => ['class' => ['overview-details']],
      ];
      $rows[$style_id]['details']['id'] = [
        '#type' => 'item',
        '#title' => $this->t('ID'),
        '#prefix' => '<span class="container-inline">',
        '#suffix' => '</span>',
        'item' => [
          '#type' => 'container',
          '#plain_text' => $style_id,
          '#attributes' => ['class' => ['table-filter-text-source']],
        ],
      ];
      $rows[$style_id]['details']['groups'] = [
        '#type' => 'item',
        '#title' => $this->t('Groups'),
        'item' => $group_list,
        '#prefix' => '<div class="container-inline">',
        '#suffix' => '</div>',
      ];
      $rows[$style_id]['use'] = $paragraphs_type_link_list;
    }

    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => ['paragraphs-collection-overview-table'],
      ],
    ];

    $table += $rows;

    $form['filters'] = $filters;
    $form['styles'] = $table;
    $form['#attached']['library'] = ['paragraphs_collection/overview'];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $enabled_styles = array_keys(array_filter((array) $form_state->getValue('styles'), function ($value) { return !empty($value['enabled']); }));
    $this->config->set('enabled_styles', $enabled_styles);
    $this->config->save();
  }

}
