<?php

namespace Drupal\views_attach_library\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;

/**
 * Views Attach Library display extender plugin.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "library_in_views_display_extender",
 *   title = @Translation("Library In Views Display Extender"),
 *   help = @Translation("Library In Views settings for this view."),
 *   no_ui = FALSE
 * )
 */
class ViewsAttachLibraryDisplayExtender extends DisplayExtenderPluginBase {

  /**
   * Provide a form to edit options for this plugin.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') == 'attach_library') {
      $form['attach_library'] = [
        '#type' => 'textfield',
        '#title' => 'Add Libraries',
        '#description' => $this->t('Add library name in textfield , for exmaple '
            . 'add <b>"abc/xyz"</b> where <b>abc</b> is module or theme name and '
            . '<b>xyz</b> is library name. For more info please read README.txt file.'),
        '#default_value' => (!empty($this->options['attach_library']['attach_library'])) ? $this->options['attach_library']['attach_library']: '',
      ];
    }
  }

  /**
   * Validate the options form.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    
  }

  /**
   * Handle any special handling on the validate form.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {

    if ($form_state->get('section') == 'attach_library') {
      $this->options['attach_library'] = $form_state->cleanValues()->getValue($section);
    }
  }

  /**
   * Set up any variables on the view prior to execution.
   */
  public function preExecute() {
    
  }

  /**
   * Inject anything into the query that the display_extender handler needs.
   */
  public function query() {
    
  }

  /**
   * Provide the default summary for options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) {
    $categories['attach_library'] = [
      'title' => t('Attach Library'),
      'column' => 'second',
    ];
    $options['attach_library'] = [
      'category' => 'attach_library',
      'title' => t('Attach Library'),
      'value' => (empty($this->options['attach_library']['attach_library'])) ? $this->t('Add Library'): $this->t('Edit Library'),
    ];
  }

  /**
   * Lists defaultable sections and items contained in each section.
   */
  public function defaultableSections(&$sections, $section = NULL) {
    
  }

}
