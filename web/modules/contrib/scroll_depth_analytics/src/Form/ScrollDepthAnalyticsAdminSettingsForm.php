<?php

namespace Drupal\scroll_depth_analytics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure  Scroll Analytics settings for this site.
 */
class ScrollDepthAnalyticsAdminSettingsForm extends ConfigFormBase {
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scroll_depth_analytics_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('scroll_depth_analytics.admin_settings');
    $form['scroll_depth_analytics_tracking'] = array(
      '#type' => 'fieldset',
      '#title' => t('Select Tracking'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );
    $form['scroll_depth_analytics_tracking']['scroll_depth_analytics_tracking_options'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add scroll tracking to the pages'),
      '#description' => t('Monitors the 25%, 50%, 75%, and 100% scroll points, sending a Google Analytics Event for each one.'),
	  '#default_value' => $config->get('visibility.tracking_options'),
    );
    $form['scroll_depth_analytics_tracking']['scroll_depth_analytics_page_element_tracker'] = array(
     '#type' => 'checkbox',
     '#title' => t('Add element tracking to the pages'),
     '#description' => t('Record scroll events for specific elements on the page.'),
	 '#default_value' => $config->get('elements.elements_tracker'),
    );
    $form['scroll_depth_analytics_tracking']['scroll_depth_analytics_scroll_elements'] = array(
      '#type' => 'textarea',
      '#title' => t('Elements'),
      '#default_value' => $config->get('elements.scroll_elements'),
      '#description' => t('Specify the elements by using the id or class attribute. Example: id <em class="placeholder">#content</em>, for class <em class="placeholder">.error</em>, for any html tag give the tag name. Enter one item per line.'),
      '#wysiwyg' => FALSE,
      '#rows' => 10,
      '#class' => ('form-textarea-wrapper'),
	  '#states' => array(
        'visible' => array(
          ':input[name="scroll_depth_analytics_page_element_tracker"]' => array('checked' => TRUE),
        ),
      ),
    );
    // Page specific visibility configurations.
    $scroll_analytics_pages = $config->get('visibility.request_path_pages');
    $scroll_analytics_visibilit_mode = $config->get('visibility.visibility_mode');
    $options = [];
    $title = '';
    $description = '';
    $options = [
      t('Every page except the listed pages'),
      t('The listed pages only'),
    ];
    $description = t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard.
	Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.",
    ['%blog' => '/blog', '%blog-wildcard' => '/blog/*', '%front' => '<front>']);
    $form['tracking']['page_visibility_settings']['scroll_analytics_visibility_request_path_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add tracking to specific pages'),
      '#options' => $options,
      '#default_value' => !empty($scroll_analytics_visibilit_mode) ? $scroll_analytics_visibilit_mode : '',
    ];
    $form['tracking']['page_visibility_settings']['scroll_analytics_visibility_request_path_pages'] = [
      '#type' => 'textarea',
      '#title' => $title,
      '#title_display' => 'invisible',
      '#default_value' => !empty($scroll_analytics_pages) ? $scroll_analytics_pages : '',
      '#description' => $description,
      '#rows' => 10,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    \Drupal::configFactory()->getEditable('scroll_depth_analytics.admin_settings')
      ->set('visibility.tracking_options', $values['scroll_depth_analytics_tracking_options'])
      ->set('visibility.visibility_mode', $values['scroll_analytics_visibility_request_path_mode'])
      ->set('visibility.request_path_pages', $values['scroll_analytics_visibility_request_path_pages'])
      ->set('elements.elements_tracker', $values['scroll_depth_analytics_page_element_tracker'])
      ->set('elements.scroll_elements', $values['scroll_depth_analytics_scroll_elements'])
      ->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['scroll_depth_analytics.admin_settings'];
  }

}
