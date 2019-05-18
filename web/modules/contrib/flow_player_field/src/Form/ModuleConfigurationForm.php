<?php

namespace Drupal\flow_player_field\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class ModuleConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flow_player_field_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'flow_player_field.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('flow_player_field.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api Key'),
      '#default_value' => $config->get('api_key'),
    ];

    $form['site_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site ID'),
      '#default_value' => $config->get('site_id'),
    ];

    $form['search_results'] = [
      '#type' => 'number',
      '#title' => $this->t('Search results number:'),
      '#default_value' => $config->get('search_results') ?: 100,
    ];

    $form['flowplayer_html'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Embed code:'),
      '#default_value' => $config->get('flowplayer_html') ?: '<style>.fp-embed-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width:100%; }.fp-embed-container iframe { position: absolute; top: 0; left:0; width: 100%; height: 100%; }</style><div class="fp-embed-container"><iframe src="//ljsp.lwcdn.com/api/video/embed.jsp?id=[VIDEOID]&pi=[PLAYERID]" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen allow="autoplay"></iframe></div>',
    ];

    $form['flowplayer_paragraph'] = [
      '#type' => 'inline_template',
      '#template' => '
                <p>Embed code example:
                <pre>' . htmlspecialchars('<iframe src="//ljsp.lwcdn.com/api/video/embed.jsp?id=[VIDEOID]&pi=[PLAYERID]" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen allow="autoplay"></iframe>') .
      '</pre><br />
                <strong>[VIDEOID]</strong> & <strong>[PLAYERID]</strong> are important.<br />
                id=[VIDEOID]&pi=[PLAYERID] will be replaced with their appropriate values from the video. </p>
            ',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('flow_player_field.settings')
      ->set('api_key', $values['api_key'])
      ->set('site_id', $values['site_id'])
      ->set('search_results', $values['search_results'])
      ->set('flowplayer_html', $values['flowplayer_html'])
      ->save();
  }

}
