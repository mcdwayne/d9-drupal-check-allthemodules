<?php

/**
 * Created by PhpStorm.
 * User: andy
 * Date: 15/01/2016
 * Time: 23:16
 */

namespace Drupal\subsite\Plugin\Subsite;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\subsite\BaseSubsitePlugin;
use Drupal\subsite\SubsitePluginInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * @Plugin(
 *   id = "subsite_branding",
 *   label = @Translation("Branding"),
 *   block_prerender = {
 *     "system_branding_block"
 *   },
 *   page_attachments_alter = TRUE,
 *
 * )
 */
class BrandingSubsitePlugin extends BaseSubsitePlugin {
  /**
   * Gets default configuration for this plugin.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  public function defaultConfiguration() {
    return array(
      'name' => '',
      'logo_upload' => '',
      'override_favicon' => FALSE,
      'favicon_upload' => '',
    );
  }

  /**
   * Form constructor.
   *
   * Plugin forms are embedded in other forms. In order to know where the plugin
   * form is located in the parent form, #parents and #array_parents must be
   * known, but these are not available during the initial build phase. In order
   * to have these properties available when building the plugin form's
   * elements, let this method return a form element that has a #process
   * callback and build the rest of the form in the callback. By the time the
   * callback is executed, the element's #parents and #array_parents properties
   * will have been set by the form API. For more documentation on #parents and
   * #array_parents, see \Drupal\Core\Render\Element\FormElement.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The form structure.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Subsite name'),
      '#default_value' => $configuration['name'],
    );

    $form['logo_path'] = array(
      '#type' => 'markup',
      '#title' => t('Logo path'),
      '#value' => $configuration['logo_path'],
    );

    $form['logo_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to subsite logo'),
      '#default_value' => $configuration['logo_path'],
    );

    $form['logo_upload'] = array(
      '#title' => t('Upload a logo'),
      '#type' => 'file',
//      '#name' => 'files[' . implode('_', $form['#parents']) . ']'
      '#name' => 'files[logo_upload]',
//      '#default_value' => $this->configuration['logo_upload'],
    );

    $form['favicon_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to custom icon'),
      '#default_value' => $configuration['favicon_path'],
    );
    $form['favicon_upload'] = array(
      '#type' => 'file',
      '#name' => 'files[favicon_upload]',
      '#title' => t('Upload icon image'),
      '#description' => t("If you don't have direct file access to the server, use this field to upload your shortcut icon.")
    );

    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Handle file uploads.
    $validators = array('file_validate_is_image' => array());

    // Check for a new uploaded logo.
    $file = file_save_upload('logo_upload', $validators, FALSE, 0);
    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        // Put the temporary file in form_values so we can save it on submit.
        $form_state->setValueForElement($form['logo_upload'], $file);
      }
      else {
        // File upload failed.
        $form_state->setError($form['logo_upload'], $this->t('The logo could not be uploaded.'));
      }
    }

    // Check for a new uploaded logo.
    $file = file_save_upload('favicon_upload', $validators, FALSE, 0);
    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        // Put the temporary file in form_values so we can save it on submit.
        $form_state->setValueForElement($form['favicon_upload'], $file);
      }
      else {
        // File upload failed.
        $form_state->setError($form['favicon_upload'], $this->t('The favicon could not be uploaded.'));
      }
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $plugin_form_values = $form_state->getValue($form['#parents']);

    // If the user uploaded a new logo or favicon, save it to a permanent location
    // and use it in place of the default theme-provided file.
    if (!empty($plugin_form_values['logo_upload'])) {
      $filename = file_unmanaged_copy($plugin_form_values['logo_upload']->getFileUri());
      $plugin_form_values['logo_path'] = $filename;
    }

    if (!empty($plugin_form_values['favicon_upload'])) {
      $filename = file_unmanaged_copy($plugin_form_values['favicon_upload']->getFileUri());
      $plugin_form_values['favicon_path'] = $filename;
    }

    $this->setConfiguration($plugin_form_values);

    Cache::invalidateTags(array('config:block.block.sitebranding'));
  }

  /**
   * Alter the site branding block.
   *
   * @param $build
   * @param $node
   * @param $subsite_node
   * @return mixed
   */
  public function blockPrerender($build, $node, $subsite_node) {
    $branding_configuration = $this->getConfiguration();

    if (!empty($branding_configuration['name'])) {
      $build['content']['site_name']['#markup'] = $branding_configuration['name'];
    }

    if (!empty($branding_configuration['logo_path'])) {
      $logo_url = file_create_url($branding_configuration['logo_path']);
      $build['content']['site_logo']['#uri'] = $logo_url;
    }

    return $build;
  }

  public function pageAttachmentsAlter(array &$attachments) {
    $branding_configuration = $this->getConfiguration();

    if (!empty($branding_configuration['favicon_path'])) {
      /** @var MimeTypeGuesserInterface $mime_type_guesser */
      $mime_type_guesser = \Drupal::service('file.mime_type.guesser');
      $mime_type = $mime_type_guesser->guess($branding_configuration['favicon_path']);
      $favicon_url = file_create_url($branding_configuration['favicon_path']);
      foreach ($attachments['#attached']['html_head_link'] as $key => $value) {
        foreach ($value as $inner_key => $inner_value) {
          if (!empty($inner_value['rel']) && $inner_value['rel'] == 'shortcut icon') {
            $attachments['#attached']['html_head_link'][$key][$inner_key]['href'] = UrlHelper::stripDangerousProtocols($favicon_url);
            $attachments['#attached']['html_head_link'][$key][$inner_key]['type'] = $mime_type;
          }
        }
      }
    }
  }
}