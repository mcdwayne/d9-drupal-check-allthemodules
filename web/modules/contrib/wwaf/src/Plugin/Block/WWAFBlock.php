<?php

namespace Drupal\wwaf\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Hello' Block.
 *
 * @Block(
 *   id = "wwaf_block",
 *   admin_label = @Translation("WWAF block"),
 *   category = @Translation("WWAF"),
 * )
 */
class WWAFBlock extends BlockBase implements BlockPluginInterface {
  
  
  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    
    $config   = $this->getConfiguration();
    $g_config = \Drupal::config('wwaf.settings');

    $form['rendering'] = [
      '#type' => 'details',
      '#title' => $this->t('WWAF Rendering options'),
      '#open' => TRUE,
    ];

    $form['rendering']['location_markup_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Render the location info markup inside the list'),
      '#description' => $this->t('Overrides Global settings. (currently = @value)', ['@value' => $g_config->get('location_markup')?'TRUE':'FALSE']),
      '#default_value' => isset($config['location_markup_override'])? $config['location_markup_override'] : 0,
    ];
    
    $form['rendering']['location_info_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use separate (default) sidebar window for InfoBox'),
      '#description' => $this->t('Overrides Global settings. (currently = @value)', ['@value' => $g_config->get('location_info')?'TRUE':'FALSE']),
      '#default_value' => isset($config['location_info_override'])? $config['location_info_override'] : 0,
    ];
    
    $form['rendering']['hide_map_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide map by default'),
      '#description' => $this->t('Overrides Global settings. (currently = @value)', ['@value' => $g_config->get('hide_map')?'TRUE':'FALSE']),
      '#default_value' => isset($config['hide_map_ovveride'])? $config['hide_map_ovveride'] : 0,
    ];
    
    $form['rendering']['enable_countries_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enables the countries search'),
      '#description' => $this->t('Overrides Global settings. (currently = @value)', ['@value' => $g_config->get('enable_countries')?'TRUE':'FALSE']),
      '#default_value' => isset($config['enable_countries_override'])? $config['enable_countries_override'] : 0,
    ];


    $form['rendering']['custom_suggestion'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom template suggestion (machine_name)'),
      '#default_value' => isset($config['custom_suggestion'])? $config['custom_suggestion'] : '',
    ];
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValue('rendering');
    
    $this->configuration['location_markup_override'] = $values['location_markup_override'];
    $this->configuration['location_info_override'] = $values['location_info_override'];
    $this->configuration['hide_map_override'] = $values['hide_map_override'];
    $this->configuration['enable_countries_override'] = $values['enable_countries_override'];
    $this->configuration['custom_suggestion'] = $values['custom_suggestion'];
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function build() {

    $request = \Drupal::request();
    
    // Overrides:
    //------------------------------------
    $overriden = FALSE;
    $config = $this->getConfiguration();

    $build = \Drupal\wwaf\Controller\WWAFController::getMainBuild($request, $config['custom_suggestion']);

    if (!empty($config['location_markup_override'])) {
      $build['#attached']['drupalSettings']['wwaf']['location_markup'] = $config['location_markup_override'] == 1;
      $overriden = TRUE;
    }

    if (!empty($config['location_info_override'])) {
      $build['#attached']['drupalSettings']['wwaf']['location_info'] = $config['location_info_override'] == 1;
      $overriden = TRUE;
    }

    if (!empty($config['hide_map_override'])) {
      $build['#attached']['drupalSettings']['wwaf']['hide_map'] = $config['hide_map_override'] == 1;
      $overriden = TRUE;
    }
    
    if (!empty($config['enable_countries_override'])) {
      $build['#attached']['drupalSettings']['wwaf']['enable_countries'] = $config['enable_countries_override'] == 1;
      $build['#enable_countries'] = $config['enable_countries_override'] == 1;
      $overriden = TRUE;
    }

    $build['#attached']['drupalSettings']['wwaf']['block_override'] = $overriden;

    return $build;
  }
}
