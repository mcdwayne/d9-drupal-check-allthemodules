<?php

/**
 * Contains \Drupal\fblikebutton\Form\FblikebuttonFormSettings
 */

namespace Drupal\fblikebutton\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

class FblikebuttonFormSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fblikebutton_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return array('fblikebutton.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $fblikebutton_node_options = node_type_get_names();
    $config = $this->config('fblikebutton.settings');

    $form['fblikebutton_dynamic_visibility'] = array(
      '#type' => 'details',
      '#title' => $this->t('Visibility settings'),
      '#open' => TRUE,
    );
    $form['fblikebutton_dynamic_visibility']['fblikebutton_node_types'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Display the Like button on these content types:'),
      '#options' => $fblikebutton_node_options,
      '#default_value' => $config->get('node_types'),
      '#description' => $this->t('Each of these content types will have the "like" button automatically added to them.'),
    );
    /** 
     * @TODO: Uncomment this when the module is also able to add the button to 
     * the links area
     * 
    $form['fblikebutton_dynamic_visibility']['fblikebutton_full_node_display'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Where do you want to show the Like button (full node view)?'),
      '#options' => array(
        $this->t('Content area'),
        $this->t('Links area')
      ),
      '#default_value' => $config->get('full_node_display'),
      '#description' => $this->t('If <em>Content area</em> is selected, the button will appear in the same area as the node content. When you select <em>Links area</em> the Like button will be visible in the links area, usually at the bottom of the node (When you select this last option you may want to adjust the Appearance settings). You can also configure Static Like Button Blocks in'. \Drupal::l($this->t('block page'), Url::fromRoute('block.admin_display')) . '.'),
    );
    */
    $form['fblikebutton_dynamic_visibility']['fblikebutton_teaser_display'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Where do you want to show the Like button on teasers?'),
      '#options' => array(
        $this->t('Don\'t show on teasers'),
        $this->t('Content area'),
        /**
         * @TODO: Uncomment this when the module is also able to add the button to 
         * the links area
         */
        //$this->t('Links area')
      ),
      '#default_value' => $config->get('teaser_display'),
      '#description' => $this->t('If you want to show the like button on teasers you can select the display area.'),
    );
    $form['fblikebutton_dynamic_appearance'] = array(
      '#type' => 'details',
      '#title' => $this->t('Appearance settings'),
      '#open' => TRUE,
    );
    $form['fblikebutton_dynamic_appearance']['fblikebutton_layout'] = array(
      '#type' => 'select',
      '#title' => $this->t('Layout style'),
      '#options' => array('standard' => $this->t('Standard'),
                          'box_count' => $this->t('Box Count'),
                          'button_count' => $this->t('Button Count'),
                          'button' => $this->t('Button')),
      '#default_value' => $config->get('layout'),
      '#description' => $this->t('Determines the size and amount of social context next to the button.'),
    );
    // The actial values passed in from the options will be converted to a boolean
    // in the validation function, so it doesn't really matter what we use.
    $form['fblikebutton_dynamic_appearance']['fblikebutton_show_faces'] = array(
      '#type' => 'select',
      '#title' => $this->t('Show faces in the box?'),
      '#options' => array(t('Do not show faces'), $this->t('Show faces')),
      '#default_value' => $config->get('show_faces', TRUE),
      '#description' => $this->t('Show profile pictures below the button. Only works if <em>Layout style</em> (found above) is set to <em>Standard</em> (otherwise, value is ignored).'),
    );
    $form['fblikebutton_dynamic_appearance']['fblikebutton_action'] = array(
      '#type' => 'select',
      '#title' => $this->t('Verb to display'),
      '#options' => array('like' => $this->t('Like'), 'recommend' => $this->t('Recommend')),
      '#default_value' => $config->get('action'),
      '#description' => $this->t('The verbiage to display inside the button itself.'),
    );
    $form['fblikebutton_dynamic_appearance']['fblikebutton_font'] = array(
      '#type' => 'select',
      '#title' => $this->t('Font'),
      '#options' => array(
        'arial' => 'Arial',
        'lucida+grande' => 'Lucida Grande',
        'segoe+ui' => 'Segoe UI',
        'tahoma' => 'Tahoma',
        'trebuchet+ms' => 'Trebuchet MS',
        'verdana' => 'Verdana',
      ),
      '#default_value' => $config->get('font', 'arial'),
      '#description' => $this->t('The font with which to display the text of the button.'),
    );
    $form['fblikebutton_dynamic_appearance']['fblikebutton_color_scheme'] = array(
      '#type' => 'select',
      '#title' => $this->t('Color scheme'),
      '#options' => array('light' => $this->t('Light'), 'dark' => $this->t('Dark')),
      '#default_value' => $config->get('color_scheme'),
      '#description' => $this->t('The color scheme of the box environment.'),
    );
    
    $weight_description = "The weight determines where, at the content block, the like button will appear. The larger the weight, ";
    $weight_description .= "the lower it will appear on the node. For example, if you want the button to appear more toward the ";
    $weight_description .= "top of the node, choose <em>-40</em> as opposed to <em>-39, -38, 0, 1,</em> or <em>50,</em> etc. ";
    $weight_description .= "To position the Like button in its own block, go to the @link_to_block_page.";
    $form['fblikebutton_dynamic_appearance']['fblikebutton_weight'] = array(
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#default_value' => $config->get('weight'),
      '#description' => $this->t($weight_description, array(
        '@link_to_block_page' => Link::fromTextAndUrl($this->t('block page'), Url::fromRoute('block.admin_display'))->toString()
      )),
    );
    
    $language_description = "Specific language to use. Default is English. Examples:<br />French (France): <em>fr_FR</em><br />";
    $language_description .= "French (Canada): <em>fr_CA</em><br />More information can be found at <a href='@info_url'>@info_url</a> ";
    $language_description .= "and a full XML list can be found at <a href='@list_url'>@list_url</a>";
    $form['fblikebutton_dynamic_appearance']['fblikebutton_language'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Language'),
      '#default_value' => $config->get('language'),
      '#description' => $this->t($language_description, array(
        '@info_url' => 'http://developers.facebook.com/docs/internationalization',
        '@list_url' => 'http://www.facebook.com/translations/FacebookLocales.xml'
      )),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (null != $form_state->getValue('fblikebutton_weight')) {
      if (!is_numeric($form_state->getValue('fblikebutton_weight'))) {
        $form_state->setErrorByName('fblikebutton_bl_weight', $this->t('The weight of the like button must be a number (examples: 50 or -42 or 0).'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('fblikebutton.settings');

    $node_types = $form_state->getValue('fblikebutton_node_types');
    $full_node_display = $form_state->getValue('fblikebutton_full_node_display');
    $teaser_display = $form_state->getValue('fblikebutton_teaser_display');
    $layout = $form_state->getValue('fblikebutton_layout');
    $show_faces = $form_state->getValue('fblikebutton_show_faces');
    $action = $form_state->getValue('fblikebutton_action');
    $font = $form_state->getValue('fblikebutton_font');
    $color_scheme = $form_state->getValue('fblikebutton_color_scheme');
    $weight = $form_state->getValue('fblikebutton_weight');
    $language = $form_state->getValue('fblikebutton_language');

    $config->set('node_types', $node_types)
          ->set('full_node_display', $full_node_display)
          ->set('teaser_display', $teaser_display)
          ->set('layout', $layout)
          ->set('show_faces', $show_faces)
          ->set('action', $action)
          ->set('font', $font)
          ->set('color_scheme', $color_scheme)
          ->set('weight', $weight)
          ->set('language', $language)
          ->save();

    // Clear render cache
    $this->clearCache();
  }

  /**
   * @TODO Clear render cache to make the button use the new configuration
   */
  protected function clearCache() {
    \Drupal::cache('render')->invalidateAll();
  }
}
