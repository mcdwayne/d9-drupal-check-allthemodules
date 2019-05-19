<?php

namespace Drupal\views_blogspot_archive\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The style plugin for views_blogspot_archive.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_blogspot_archive",
 *   title = @Translation("Views Blogspot Archive"),
 *   help = @Translation("Displays result in archive formatted, with month and year that link to archive page."),
 *   theme = "views_blogspot_archive_view_archive",
 *   theme_file = "views_blogspot_archive.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ViewsBlogspotArchive extends StylePluginBase {
  /**
   * Overrides Drupal\views\Plugin\Plugin::$usesOptions.
   *
   * @var bool
   */
  protected $usesOptions = TRUE;

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = FALSE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = FALSE;

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // Define options.
    $options['vba_field_name'] = array('default' => '');
    $options['vba_view_name'] = array('default' => FALSE);
    $options['vba_view_display_id'] = array('default' => FALSE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // Options form here.
    parent::buildOptionsForm($form, $form_state);
    $form['sna_blocks_wrapper'] = array(
      '#markup' => '<b>Note:</b> Archive blocks required two settings. 1. Date field, based on which archive will be created and 2. A view page to display output when user click links in archive block. Archive block settings for each block need to be unique so override the settings.',
    );

    $form['vba_field_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Date Field Name'),
      '#default_value' => $this->options['vba_field_name'] ? $this->options['vba_field_name'] : 'created',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#description' => $this->t('Provide date type field machine name. Archive will be created based on this field.'),
    );

    $views_data = array();
    $views = \Drupal::entityManager()->getStorage('view')->loadMultiple();
    /* @var \Drupal\views\Entity\View[] $views */
    foreach ($views as $view) {
      $displays = $view->get('display');
      foreach (array_keys($displays) as $display_id) {
        $display =& $view->getDisplay($display_id);
        if ($display['display_options']['path']) {
          $data = 'view.' . $view->get('id') . '.' . $display_id;
          $views_data[$data] = $data;
        }
      }
    }
    $form['vba_view_name'] = array(
      '#type' => 'select',
      '#title' => $this->t('Page'),
      '#options' => $views_data,
      '#default_value' => $this->options['vba_view_name'] ? $this->options['vba_view_name'] : NULL,
      '#required' => TRUE,
      '#empty_option' => '- None -',
      '#description' => $this->t('Machine name of the view whose page is used to display archive result.'),
    );
  }

}
