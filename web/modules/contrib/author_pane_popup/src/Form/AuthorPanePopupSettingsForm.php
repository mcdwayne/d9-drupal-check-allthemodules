<?php

namespace Drupal\author_pane_popup\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays the Google Analytics Vimeo settings form.
 */
class AuthorPanePopupSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'author_pane_popup_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('author_pane_popup.admin_settings');
    $content_types = node_type_get_types();
    $author_pane_popup_selected_content_types = $config->get('author_pane_popup.content_types');
    $manage_qtip_url = Url::fromRoute('author_pane_popup.qtip_list');
    $manage_content_qtip_url = Url::fromUri('internal:/admin/structure/views/view/author_pane_popup');
    $form['author_pane_popup'] = array(
      '#type' => 'fieldset',
      '#title' => t('Select the content types you want to enable author pane popup'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );
    foreach ($content_types as $content_type) {
      $author_pane_popup_content_types[$content_type->id()] = $content_type->label();
    }
    $form['author_pane_popup']['author_pane_popup_content_types'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Enable Tracking'),
      '#options' => $author_pane_popup_content_types,
    );

    if (!empty($author_pane_popup_selected_content_types)) {
      $form['author_pane_popup']['author_pane_popup_content_types']['#default_value'] = array_keys(array_filter($author_pane_popup_selected_content_types));
    }
    $form['author_pane_popup']['author_pane_popup_jquery_selectors'] = array(
      '#type' => 'textarea',
      '#title' => t('jQuery selector to trigger Author Pane Popup'),
      '#default_value' => $config->get('author_pane_popup.jquery_selectors'),
      '#description' => t('Specify jQuery identifiers. Enter one item per line. For example: <em>.username</em>. Please note: Respective HTML elements should be anchor tags which links to the user profile.'),
    );
    $form['author_pane_popup']['author_pane_popup_use_loading_image'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use image instead of displaying text while loading.'),
      '#default_value' => $config->get('author_pane_popup.use_loading_image'),
    );
    $form['author_pane_popup']['author_pane_popup_loading_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Loading Text'),
      '#default_value' => $config->get('author_pane_popup.loading_text'),
      '#states' => array(
        'visible' => array(
          ':input[name="author_pane_popup_use_loading_image"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['author_pane_popup']['author_pane_popup_loading_image'] = array(
      '#type' => 'managed_file',
      '#name' => 'author_pane_popup_loading_image',
      '#title' => t('Upload Image'),
      '#description' => t("Here you can upload an image to show while Author Information loading!"),
      '#default_value' => $config->get('author_pane_popup.loading_image'),
      '#upload_location' => 'public://',
      '#upload_validators' => array(
        'file_validate_extensions' => array('gif png jpg jpeg'),
        'file_validate_image_resolution' => array('20x20', 0),
      ),
      '#states' => array(
        'visible' => array(
          ':input[name="author_pane_popup_use_loading_image"]' => array('checked' => TRUE),
        ),
        'required' => array(
          ':input[name="author_pane_popup_use_loading_image"]' => array('checked' => TRUE),
        ),
      ),
    );
    $fid = $config->get('author_pane_popup.loading_image');
    if ($fid != '') {
      $author_pane_popup_file = file_load($fid);
      $author_pane_popup_file_uri = $author_pane_popup_file->getFileUri();
      $author_pane_popup_image_variables = array(
        '#theme' => 'image_style',
        '#uri' => $author_pane_popup_file_uri,
        '#alt' => 'Loading...',
        '#title' => 'Loading...',
        '#style_name' => 'thumbnail',
        '#width' => '20',
        '#height' => '20',
      );
      $form['author_pane_popup']['author_pane_popup_loading_image']['#field_prefix'] = drupal_render($author_pane_popup_image_variables);
    }

    $qtip_instances = AuthorPanePopupAdminSettingsQtipForm::authorPanePopupQtipLoadMultiple(TRUE);

    $form['author_pane_popup_style'] = array(
      '#type' => 'fieldset',
      '#title' => t('SELECT Author Pane Popup Style to use'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );
    $form['author_pane_popup_style']['author_pane_popup_qtip_instance'] = array(
      '#type' => 'select',
      '#title' => t('qTip instances'),
      '#options' => $qtip_instances,
      '#default_value' => $config->get('author_pane_popup.qtip_instance'),
      '#description' => t('Select the qTip style to use for your Author Pane Popup. @click_here to add new qTip instances.', array('@click_here' => \Drupal::l(t('Click here'), $manage_qtip_url))),
      '#required' => TRUE,
    );
    // Page specific visibility configurations.
    $account = \Drupal::currentUser();
    $php_access = $account->hasPermission('use php for author pane popup visibility');
    $visibility_options = $config->get('author_pane_popup.visibility_options');
    $pages = $config->get('author_pane_popup.visibility_pages');
    $options = array();
    $title = '';
    $description = '';
    if ($visibility_options == AUTHOR_PANE_POPUP_USE_PHP_FOR_TRACKING && !$php_access) {
      $form['author_pane_popup_visibility_options'] = array('#type' => 'value', '#value' => 1);
      $form['author_pane_popup_visibility_pages'] = array('#type' => 'value', '#value' => $pages);
    }
    else {
      $options = array(
        t('Every page except the listed pages'),
        t('The listed pages only'),
      );
      $description = t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array(
        '%blog' => 'blog',
        '%blog-wildcard' => 'blog/*',
        '%front' => '<front>',
      ));
      if (\Drupal::moduleHandler()->moduleExists('php') && $php_access) {
        $options[] = t('Pages on which this PHP code returns <code>TRUE</code> (experts only)');
        $title = t('Pages or PHP code');
        $description .= ' ' . t('If the PHP option is chosen, enter PHP code between %php. Note that executing incorrect PHP code can break your Drupal site.', array('%php' => '<?php ?>'));
      }
      else {
        $title = t('Pages');
      }
    }
    $form['author_pane_popup_visibility'] = array(
      '#type' => 'fieldset',
      '#title' => t('Add Author Pane Popup to specific pages'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );
    $form['author_pane_popup_visibility']['author_pane_popup_visibility_options'] = array(
      '#type' => 'radios',
      '#options' => $options,
      '#default_value' => $config->get('author_pane_popup.visibility_options'),
    );
    $form['author_pane_popup_visibility']['author_pane_popup_visibility_pages'] = array(
      '#type' => 'textarea',
      '#title' => $title,
      '#default_value' => $pages,
      '#description' => $description,
      '#wysiwyg' => FALSE,
      '#rows' => 10,
    );
    $form['author_pane_popup_content'] = array(
      '#type' => 'fieldset',
      '#title' => t('Manage Author Pane Popup Content'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );
    $form['author_pane_popup_content']['manage'] = array(
      '#markup' => t('@click_here to update author pane popup content.', array('@click_here' => \Drupal::l(t('Click here'), $manage_content_qtip_url))),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    \Drupal::configFactory()->getEditable('author_pane_popup.admin_settings')
      ->set('author_pane_popup.content_types', $values['author_pane_popup_content_types'])
      ->set('author_pane_popup.jquery_selectors', $values['author_pane_popup_jquery_selectors'])
      ->set('author_pane_popup.use_loading_image', $values['author_pane_popup_use_loading_image'])
      ->set('author_pane_popup.loading_text', $values['author_pane_popup_loading_text'])
      ->set('author_pane_popup.loading_image', isset($values['author_pane_popup_loading_image'][0]) ? $values['author_pane_popup_loading_image'][0] : '')
      ->set('author_pane_popup.qtip_instance', $values['author_pane_popup_qtip_instance'])
      ->set('author_pane_popup.visibility_options', $values['author_pane_popup_visibility_options'])
      ->set('author_pane_popup.visibility_pages', $values['author_pane_popup_visibility_pages'])
      ->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * Get list of jQuery triggers separated by ","(comma).
   */
  public static function authorPanePopupGetTriggers() {
    $config = \Drupal::config('author_pane_popup.admin_settings');
    $trigger = $config->get('author_pane_popup.jquery_selectors');
    $trigger = explode("\n", $trigger);
    // Trim all entries.
    $trigger = array_map('trim', $trigger);
    // Filter out empty lines.
    $trigger = array_filter($trigger);

    $triggers = array();

    foreach ($trigger as $this_trigger) {
      $triggers[] = $this_trigger;
    }
    return implode(',', $triggers);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['author_pane_popup.settings'];
  }

}
