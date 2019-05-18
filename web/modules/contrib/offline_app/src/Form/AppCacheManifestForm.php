<?php

/**
 * @file
 * Contains \Drupal\offline_app\Form\AppCacheManifestForm;
 */

namespace Drupal\offline_app\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class AppCacheManifestForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['offline_app.appcache'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'offline_app_appcache_manifest_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('offline_app.appcache');

    $form['configuration'] = [
      '#type' => 'vertical_tabs'
    ];

    $form['manifest_container'] = [
      '#type' => 'details',
      '#group' => 'configuration',
      '#title' => $this->t('Pages'),
    ];

    $form['manifest_container']['pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pages'),
      '#title_display' => 'invisible',
      '#default_value' => $config->get('manifest.pages'),
      '#description' => $this->t('Enter additional explicit pages that do not fit in content or assets.'),
      '#rows' => 20,
    ];

    $form['fallback_container'] = [
      '#type' => 'details',
      '#group' => 'configuration',
      '#title' => $this->t('Fallback'),
    ];

    $form['fallback_container']['fallback'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Fallback'),
      '#title_display' => 'invisible',
      '#default_value' => $config->get('manifest.fallback'),
      '#description' => $this->t('Add additional fallback entries.'),
      '#rows' => 20,
    ];

    $form['network_container'] = [
      '#type' => 'details',
      '#group' => 'configuration',
      '#title' => $this->t('Network'),
    ];

    $form['network_container']['network'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Network'),
      '#title_display' => 'invisible',
      '#default_value' => $config->get('manifest.network'),
      '#rows' => 20,
    ];

    $form['tag_on_offline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add the manifest attribute to the HTML tag on the "offline" pages if you are not using the iframe.'),
      '#default_value' => $config->get('manifest.tag_on_offline'),
    ];

    $form['validate'] = [
      '#markup' => $this->t('<a href="/@url">Validate your manifest</a>', ['@url' => $this->getUrlGenerator()->getPathFromRoute('offline_app.appcache.admin_appcache_validate')]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $fallback = $form_state->getValue('fallback');
    if (strpos($fallback, '/ /offline/appcache-fallback') === FALSE) {
      $form_state->setErrorByName('fallback', $this->t('/ /offline/appcache-fallback is a mandatory entry for the fallback.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('offline_app.appcache')
      ->set('manifest.pages', $form_state->getValue('pages'))
      ->set('manifest.fallback', $form_state->getValue('fallback'))
      ->set('manifest.network', $form_state->getValue('network'))
      ->set('manifest.tag_on_offline', $form_state->getValue('tag_on_offline'))
      ->save();
    Cache::invalidateTags(['appcache.manifest', 'appcache']);
    parent::submitForm($form, $form_state);
  }

}
