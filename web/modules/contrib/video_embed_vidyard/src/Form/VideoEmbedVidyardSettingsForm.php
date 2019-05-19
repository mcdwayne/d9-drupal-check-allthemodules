<?php

namespace Drupal\video_embed_vidyard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class VideoEmbedVidyardSettingsForm.
 *
 * @package Drupal\video_embed_vidyard\Form
 */
class VideoEmbedVidyardSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_embed_vidyard_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'video_embed_vidyard.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('video_embed_vidyard.settings');

    $form['description'] = [
      '#type' => 'item',
      '#title' => $this->t('Video Embed Vidyard Settings'),
      '#description' => $this->t('Add additional settings for Video Embed Vidyard such as custom domain and additional patterns.'),
    ];

    // Fieldset for custom domain settings.
    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
    ];

    // Custom Domain used for parsing input.
    $form['settings']['custom_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Domain'),
      '#description' => $this->t("Vidyard allows for a different domain other then embed.vidyard.com. Insert the custom domain (e.g. vidoes.mysite.com) so it can be used to parse the input. See <a href=':url'>Set up a Sharing Page</a>", [':url' => Url::fromUri('https://knowledge.vidyard.com/hc/en-us/articles/360009869514-Set-up-a-Sharing-Page')->toString()]),
      '#default_value' => $config->get('custom_domain'),
    ];

    // Get additional pattern.
    $form['settings']['additional_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional pattern'),
      '#description' => $this->t("Vidyard may allow for different URL when generating the Sharing URL. Not all Sharing URLs will be share or embed_select. If there is more than one you can add | delimiter. <a href=':url'>Example URL with watch instead of share/embed_select</a>", [':url' => Url::fromUri('https://demos.vidyard.com/watch/WtQbzSSTQvik776jvDidxP')->toString()]),
      '#default_value' => $config->get('additional_pattern'),
    ];

    // Build the form.
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('video_embed_vidyard.settings');
    $config
      ->set('additional_pattern', $form_state->getValue('additional_pattern'))
      ->set('custom_domain', $form_state->getValue('custom_domain'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
