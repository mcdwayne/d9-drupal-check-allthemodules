<?php

namespace Drupal\google_webfont_loader_api\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Configure locale settings for this site.
 */
class GoogleWebfontLoaderApiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_webfont_loader_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_webfont_loader_api_site_settings';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_webfont_loader_api.settings');
    $listing = google_webfont_loader_api_get_font_list(TRUE);
    foreach ($listing as $list_item) {
      $fonts[$list_item['name']] = SafeMarkup::checkPlain($list_item['info']['name']);
    }

    $form['google_webfont_loader_api_font_listing'] = [
      '#type' => 'value',
      '#value' => $listing,
    ];

    $form['google_webfont_loader_api_font'] = [
      '#type' => 'checkboxes',
      '#title' => t('Font'),
      '#description' => t('Use the google webfont loader api to add a font styling to the site. If you select none, the loader will act as though it is disabled unless some other module loads it.'),
      '#options' => $fonts,
      '#default_value' => $config->get('font'),
    ];

    $form['google_webfont_loader_api_cache'] = [
      '#type' => 'checkbox',
      '#title' => t('Use locally packaged Webfont Loader file'),
      '#description' => t("If checked, Use version packaged locally with module. Useful in event CDN is down. Note that cannot take advantage that site visitor may have already downloaded library if using CDN version."),
      '#default_value' => $config->get('cache'),
    ];

    $tokens = [
      '!link' => \Drupal::l(t('google webfont docs'), Url::fromUri('https://code.google.com/apis/webfonts/docs/webfont_loader.html')),
    ];

    $form['google_webfont_loader_api_display_style'] = [
      '#type' => 'radios',
      '#title' => t('Font Loading Style'),
      '#description' => t('Select default if you wish for the font to get
                          displayed even though it may not yet have loaded (or
                          however you define it in your render stylesheets).
                          This provides you with control on how you want the fonts
                          to behave and this is useful if you want the users to see
                          text regardless of current loaded status of the font.
                          Choose hidden if you want the page to display only after
                          the fonts have been loaded on the page. Please read the
                          !link or the contents of the README.txt file for
                          more information on how to format your CSS', $tokens),
      '#options' => [
        '' => t('default'),
        'hidden' => t('hidden'),
      ],
      '#default_value' => $config->get('display_style'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $config = $this->config('google_webfont_loader_api.settings');
    $config->set('font', $values['google_webfont_loader_api_font'])
      ->set('cache', $values['google_webfont_loader_api_cache'])
      ->set('display_style', $values['google_webfont_loader_api_display_style'])
      ->set('fontinfo_listing', $values['google_webfont_loader_api_font_listing'])
      ->save();
    parent::submitForm($form, $form_state);
    drupal_flush_all_caches();
  }

}
