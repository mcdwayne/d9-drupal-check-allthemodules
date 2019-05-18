<?php

namespace Drupal\block_theme_sync\Form;

use Drupal\block_theme_sync\Entity\ThemeMapping;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ThemeMappingForm.
 *
 * @package Drupal\block_theme_sync\Form
 */
class ThemeMappingForm extends EntityForm {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandler
   */
  protected $themeHandler;

  /**
   * An array of human readable theme names keyed by theme machine name.
   *
   * @var array
   */
  protected $themeOptions;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ThemeHandlerInterface $theme_handler) {
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $theme_mapping = $this->entity;

    $options = $this->getThemeOptions();

    $source = $theme_mapping->getSource();
    $form['source'] = [
      '#type' => 'select',
      '#title' => $this->t('Source theme'),
      '#default_value' => $source,
      '#description' => $this->t('The theme that is the source of block configuration.'),
      '#required' => TRUE,
      '#options' => $options,
      '#disabled' => !$theme_mapping->isNew(),
      '#ajax' => array(
        'callback' => '::themeSwitch',
        'wrapper' => 'edit-region-mapping-wrapper',
      ),
    ];

    $destination = $theme_mapping->getDestination();
    $form['destination'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination theme'),
      '#default_value' => $destination,
      '#description' => $this->t('The theme that block configuration should be copied to.'),
      '#required' => TRUE,
      '#options' => $options,
      '#disabled' => !$theme_mapping->isNew(),
      '#ajax' => array(
        'callback' => '::themeSwitch',
        'wrapper' => 'edit-region-mapping-wrapper',
      ),
    ];

    $form['region_mapping'] = $this->buildRegionMapping($source, $destination);

    return $form;
  }

  /**
   * Handles switching the available regions based on the selected themes.
   */
  public function themeSwitch($form, FormStateInterface $form_state) {
    return $form['region_mapping'];
  }

  /**
   * Builds the portion of the form showing a mapping of theme regions.
   *
   * @param string $source
   *   The name of the source theme.
   * @param string $destination
   *   The name of the destination theme.
   *
   * @return array
   *   A render array of a form element.
   */
  protected function buildRegionMapping($source, $destination) {
    $theme_names = $this->getThemeOptions();

    if ($source && $destination) {
      $element = [
        '#type' => 'table',
        '#header' => [
          'source' => ['data' => $theme_names[$source]],
          'destination' => ['data' => $theme_names[$destination]],
        ],
        '#prefix' => '<div id="edit-region-mapping-wrapper"><div>' . $this->t('For each region in the source theme, select a corresponding region in the destination theme.') . '</div>',
        '#suffix' => '</div>',
      ];
      $source_regions = $this->getVisibleRegionNames($source);
      $destination_regions = $this->getVisibleRegionNames($destination);
      if ($source_regions && $destination_regions) {
        // Load existing data.
        $region_mapping = $this->entity->getRegionMapping();
        if ($region_mapping) {
          // The regions available in the source theme may have changed.
          // Remove any obsolete regions.
          $region_mapping = array_filter($region_mapping, function ($mapping) use ($source_regions) {
            return array_key_exists($mapping['source'], $source_regions);
          });
          // Add any new regions.
          $mapped_regions = [];
          array_walk($region_mapping, function ($mapping) use (&$mapped_regions) {
            $mapped_regions[] = $mapping['source'];
          });
          $new_regions = array_diff(array_keys($source_regions), $mapped_regions);
          foreach ($new_regions as $source_region) {
            $this->addRegionMapping($region_mapping, $source_region, $destination_regions);
          }
        }
        // For new mappings, construct data defaults.
        else {
          $region_mapping = [];

          foreach (array_keys($source_regions) as $source_region) {
            $this->addRegionMapping($region_mapping, $source_region, $destination_regions);
          }
        }

        foreach ($region_mapping as $key => $mapping) {
          $element[$key]['source'] = [
            '#type' => 'select',
            '#default_value' => $mapping['source'],
            '#required' => TRUE,
            '#options' => $source_regions,
            '#disabled' => TRUE,
          ];
          $element[$key]['destination'] = [
            '#type' => 'select',
            '#default_value' => $mapping['destination'],
            '#required' => TRUE,
            '#options' => $destination_regions,
          ];
        }
      }

      return $element;
    }

    $element = [
      '#markup' => '<div>' . $this->t('Select target and destination theme before mapping regions.') . '</div>',
      '#prefix' => '<div id="edit-region-mapping-wrapper">',
      '#suffix' => '</div>',
    ];

    return $element;
  }

  /**
   * Adds a region mapping, setting appropriate default.
   *
   * @param array &$region_mapping
   *   The region mapping.
   * @param string $source_region
   *   The name of a region in the source theme.
   * @param array $destination_regions
   *   An array of human readable theme names keyed by theme machine name. 
   */
  protected function addRegionMapping(&$region_mapping, $source_region, $destination_regions) {
    // Look for a corresponding region.
    if (isset($destination_regions[$source_region])) {
      $destination_region = $source_region;
    }
    // Failing that, default to the first region.
    else {
      $destination_region = key($destination_regions);
    }

    $region_mapping[] = [
      'source' => $source_region,
      'destination' => $destination_region,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $theme_mapping = $this->entity;
    $source = $form_state->getValue('source');
    $destination = $form_state->getValue('destination');
    if ($source && $destination) {
      if ($source === $destination) {
        $form_state->setErrorByName('destination', $this->t('The destination cannot be the same as the source.'));
      }
      else {
        // Only set the ID once.
        if ($theme_mapping->isNew()) {
          $id = $source . '_to_' . $destination;
          // Validate ID.
          if (ThemeMapping::load($id)) {
            $form_state->setErrorByName('destination', $this->t('A mapping of these two themes already exists.'));
          }
        }
        else {
          $id = $theme_mapping->id();
        }

        $form_state->setValue('id', $id);
        $options = $this->getThemeOptions();
        $form_state->setValue('label', $this->t('@source » @destination', ['@source' => $options[$source], '@destination' => $options[$destination]]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $theme_mapping = $this->entity;
    $status = $theme_mapping->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label theme mapping.', [
          '%label' => $theme_mapping->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label theme mapping.', [
          '%label' => $theme_mapping->label(),
        ]));
    }
    $form_state->setRedirectUrl($theme_mapping->urlInfo('collection'));
  }

  /**
   * Initializes the theme options variable.
   */
  protected function initializeThemeOptions() {
    if (empty($this->themeOptions)) {
      $theme_options = [];

      foreach ($this->themeHandler->listInfo() as $theme_name => $theme_info) {
        // List themes that:
        // - are enabled,
        // - are not hidden, and
        // - have visible regions.
        if (!empty($theme_info->status) &&
          empty($theme_info->info['hidden']) &&
          !empty($this->getVisibleRegionNames($theme_name))) {
          $theme_options[$theme_name] = $theme_info->info['name'];
        }
      }

      asort($theme_options);
      $this->themeOptions = $theme_options;
    }

  }

  /**
   * Returns a list of installed themes.
   *
   * @return array
   *   An array of human readable theme names keyed by theme machine name. 
   */
  protected function getThemeOptions() {
    $this->initializeThemeOptions();
    return $this->themeOptions;
  }

  /**
   * Returns the human-readable list of regions keyed by machine name.
   *
   * @param string $theme
   *   The name of the theme.
   *
   * @return array
   *   An array of human-readable region names keyed by machine name.
   */
  protected function getVisibleRegionNames($theme) {
    return system_region_list($theme, REGIONS_VISIBLE);
  }

}
