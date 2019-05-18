<?php

namespace Drupal\bandsintown\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides Bandsintown module settings.
 */
class BandsintownFormSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bandsintown_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return array('bandsintown.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bandsintown.settings');

    $url = Url::fromUri('http://news.bandsintown.com/home', ['attributes' => ['target' => '_blank']]);
    $bandsintown_link = \Drupal::service('link_generator')->generate(t('official page'), $url);

    $url = Url::fromRoute('block.admin_display', array(), ['attributes' => ['target' => '_blank']]);
    $blocks_link = \Drupal::service('link_generator')->generate(t('block admin page'), $url);

    $url = Url::fromRoute('entity.node_type.collection', array(), ['attributes' => ['target' => '_blank']]);
    $content_type_link = \Drupal::service('link_generator')->generate(t('content type'), $url);

    $form['info'] = array(
      '#type'   => 'item',
      '#markup' => $this->t('Bandsintown`s fully customizable website widget automatically syncs your tour information,
      ticket links, Facebook events, and Bandsintown specials to your website. Please refer to it`s @link.
      In order to make use of this module you will need to add and configure an instance of Bandsintown block
      on the @blocks for each of the artists you are interested in. Also you can add Bandsintown field to the @content
      to make widget show up in your content.',
        [
          '@link'    => $bandsintown_link,
          '@blocks'  => $blocks_link,
          '@content' => $content_type_link,
        ]
      ),
    );
    $form['widget_version'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Use Tour Widget v2'),
      '#description'   => $this->t('Specify whether you would like to use Tour Widget v2'),
      '#default_value' => $config->get('widget_version'),
    );
    $form['show_track_button'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show Track button'),
      '#description'   => $this->t('If checked - Bandsintown Track button will be included in the block below Tour widget'),
      '#default_value' => $config->get('show_track_button'),
    );
    $form['include_artist_name'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Include Artist Name'),
      '#description'   => $this->t('If checked - Artist Name will be included above Tour widget in your content. Applies only to nodes. Blocks can be configured via admin interface'),
      '#default_value' => $config->get('include_artist_name'),
    );
    $form['widget_language'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Tour Dates Widget language'),
      '#description'   => $this->t('If not set - the widget will detect browser language. If set, the widget will always display in the chosen language. English, Spanish, German, French, Japanese, Portuguese and Italian are supported via the following language codes: en, es, de, fr, ja, pt, it.'),
      '#default_value' => $config->get('widget_language'),
      '#states'        => array(
        'visible' => array(
          array(
            ':input[name="widget_version"]' => array(
              'checked' => TRUE
            )
          ),
        ),
      ),
    );
    $form['widget_affil_code'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('A word to identify your application, company or band.'),
      '#description'   => $this->t('If not set - the widget will use default value like "js_(website
domain name)".'),
      '#default_value' => $config->get('widget_affil_code'),
      '#states'        => array(
        'visible' => array(
          array(
            ':input[name="widget_version"]' => array(
              'checked' => TRUE
            )
          ),
        ),
      ),
    );
    $form['widget_app_id'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('You may specify a word to identify your application, company or band.'),
      '#description'   => $this->t('If not set - the widget will use default value like "js_(website
domain name)".'),
      '#default_value' => $config->get('widget_app_id'),
      '#states'        => array(
        'visible' => array(
          array(
            ':input[name="widget_version"]' => array(
              'checked' => TRUE
            )
          ),
        ),
      ),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('bandsintown.settings');
    $config->set('widget_version', $form_state->getValue('widget_version'));
    $config->set('widget_language', $form_state->getValue('widget_language'));
    $config->set('widget_affil_code', $form_state->getValue('widget_affil_code'));
    $config->set('widget_app_id', $form_state->getValue('widget_app_id'));
    $config->set('show_track_button', $form_state->getValue('show_track_button'));
    $config->set('include_artist_name', $form_state->getValue('include_artist_name'));
    $config->save();

    // Clear render cache.
    $this->clearCache();
  }

  /**
   * Clear render cache.
   */
  protected function clearCache() {
    \Drupal::cache('render')->invalidateAll();
  }

}
