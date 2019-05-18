<?php

/**
 * @file
 * Contains \Drupal\mapplic\Form\MapplicAdminSettings.
 */

namespace Drupal\mapplic\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\file\Entity\File;

class MapplicAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mapplic_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('mapplic.settings');
    $map_file  = $form_state->getValue('mapplic_map');
    /* Load the object of the file by it's fid */ 
    $file = File::load( $map_file[0] );

    /* Set the status flag permanent of the file object */
    $file->setPermanent();

    /* Save the file in database */
    $file->save();
    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mapplic.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $config = $this->config('mapplic.settings');
    $form = ['#attributes' => ['enctype' => 'multipart/form-data']];

    $form['mapplic_map'] = [
      '#title' => t('Mapplic Map'),
      '#description' => t('SVG Map for the Mapplic Map'),
      '#required' => TRUE,
      '#upload_location' => 'public://',
      '#default_value' => $config->get('mapplic_map'),
      '#type' => 'managed_file',
      '#upload_validators'  => [
        'file_validate_extensions' => [
          'svg',
        ],
      ],
    ];

    $form['mapplic_height'] = [
      '#title' => t('Floorplan height'),
      '#description' => t('Height of the application in pixels. The width will take up the available space.'),
      '#required' => TRUE,
      '#default_value' => $config->get('mapplic_height'),
      '#type' => 'textfield',
    ];

    $form['mapplic_map_height'] = [
      '#title' => t('Map height'),
      '#description' => t('Height of the map in pixels.'),
      '#required' => TRUE,
      '#default_value' => $config->get('mapplic_map_height'),
      '#type' => 'textfield',
    ];

    $form['mapplic_map_width'] = [
      '#title' => t('Map width'),
      '#description' => t('Width of the map in pixels.'),
      '#required' => TRUE,
      '#default_value' => $config->get('mapplic_map_width'),
      '#type' => 'textfield',
    ];

    $form['mapplic_sidebar'] = [
      '#title' => t('Show sidebar'),
      '#description' => t('Whether to display the sidebar, which contains a search form and a list with locations.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('mapplic_sidebar'),
    ];

    $form['mapplic_minimap'] = [
      '#title' => t('Show minimap'),
      '#description' => t('Whether to display the minimap.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('mapplic_minimap'),
    ];

    $form['mapplic_locations'] = [
      '#title' => t('Show locations'),
      '#description' => t('Whether to display the locations on the map. This should be set to false in case we have an interactive SVG as map, because the overlaying locations layer may block the interactivity of the SVG.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('mapplic_locations'),
    ];

    $form['mapplic_fullscreen'] = [
      '#title' => t('Allow fullscreen'),
      '#description' => t('Enable or disable the fullscreen option.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('mapplic_fullscreen'),
    ];

    $form['mapplic_hovertip'] = [
      '#title' => t('Show hovertip'),
      '#description' => t("Show or hide the hover tooltip containing the landmark's title"),
      '#type' => 'checkbox',
      '#default_value' => $config->get('mapplic_hovertip'),
    ];

    $form['mapplic_search'] = [
      '#title' => t('Enable search'),
      '#description' => t("in case there's a small number of locations, the search form can be disabled."),
      '#type' => 'checkbox',
      '#default_value' => $config->get('mapplic_search'),
    ];

    $form['mapplic_animate'] = [
      '#title' => t('Animate'),
      '#description' => t('Enable or disable pin animations.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('mapplic_animate'),
    ];

    $form['mapplic_mapfill'] = [
      '#title' => t('Fill container'),
      '#description' => t('To make the map fill the container, set this to false. Otherwise the map will fit into the container, as the default behavior.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('mapplic_mapfill'),
    ];

    $form['mapplic_zoombuttons'] = [
      '#title' => t('Emable zoom buttons'),
      '#description' => t('Show or hide the +/- zoom buttons.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('mapplic_zoombuttons'), //variable_get('mapplic_zoombuttons', TRUE),
    ];

    $form['mapplic_clearbutton'] = [
      '#title' => t('Emable clear button'),
      '#description' => t('Whether to display the clear button.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('mapplic_clearbutton'), //variable_get('mapplic_clearbutton', TRUE),
    ];

    $form['mapplic_developer_mode'] = [
      '#title' => t('Activate developer mode'),
      '#description' => t('Enable or disable the developer option (displaying coordinates of the cursor).'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('mapplic_developer_mode'), //variable_get('mapplic_developer_mode', FALSE),
    ];

    $form['mapplic_zoom'] = [
      '#title' => t('Enable zoom'),
      '#description' => t('enable or disable the zoom feature.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('mapplic_zoom'), //variable_get('mapplic_zoom', TRUE),
    ];

    $scales = array_combine(range(1, 10), range(1, 10));
    $form['mapplic_max_scale'] = [
      '#title' => t('Mapplic max scale'),
      '#description' => t('The zoom-in limit of the map. For example, if we have a file with 600x400 dimensions when it fits, and the limit is set to 2, the maximum zoom will be 1200x800.'),
      '#type' => 'select',
      '#options' => $scales,
      '#default_value' => $config->get('mapplic_max_scale'), //variable_get('mapplic_max_scale', 10),
    ];

    return parent::buildForm($form, $form_state);
  }

}
?>
