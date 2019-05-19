<?php

namespace Drupal\hyphenator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * The settings form.
 */
class HyphenatorSettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'hyphenator_admin_settings';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['hyphenator.settings'];
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @param \Drupal\node\Entity\Node|null $platform
   *
   * @return array The form structure.
   * The form structure.
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if (!hyphenator_get_library_path()) {
      \Drupal::messenger()
        ->addMessage(t('The library could not be detected. You need to download the @hyphenator and extract the entire contents of the archive into the %path directory on your server.', [
          '@hyphenator' => Link::fromTextAndUrl(t('Hyphenator JavaScript file'), Url::fromUri(HYPHENATOR_WEBSITE_URL))
            ->toString(),
          '%path' => '/libraries',
        ]), 'error');

      return [];
    }

    $conf = $this->config('hyphenator.settings');

    $form = [];

    $form['configure'] = [
      '#type' => 'fieldset',
      '#title' => t('Configure how <em>Hyphenator.js</em> must operate'),
      '#description' => t('Those parameters permit to customize how <em>Hyphenator.js</em> must operate (see @hyphenator_site for full documentation).', [
        '@hyphenator_site' => Link::fromTextAndUrl('Hyphenator.js web site', Url::fromUri(HYPHENATOR_WEBSITE_URL))
          ->toString(),
      ]),
    ];

    $form['configure']['selector'] = [
      '#type' => 'textarea',
      '#title' => t('Apply Hyphenat to the following elements'),
      '#description' => t("A comma-separated list of jQuery selectors to apply Hyphenation. Default: '.hyphenate'"),
      '#default_value' => $conf->get('selector'),
    ];

    $form['configure']['hyphenator_config'] = [
      '#type' => 'details',
      '#title' => t('Config parameters'),
    ];

    foreach (_hyphenator_get_js_default_config() as $key => $value) {
      switch ($value['type']) {
        case 'string':
        case 'number':
          $form['configure']['hyphenator_config'][$key] = [
            '#type' => 'textfield',
          ];
          break;

        case 'select':
          $form['configure']['hyphenator_config'][$key] = [
            '#type' => 'select',
            '#options' => $value['list'],
          ];
          break;

        case 'boolean':
          $form['configure']['hyphenator_config'][$key] = [
            '#type' => 'select',
            '#options' => [
              'true' => t('Enabled:d', [':d' => ($value['default'] == 'true') ? ' (default)' : '']),
              'false' => t('Disabled:d', [':d' => ($value['default'] == 'false') ? ' (default)' : '']),
            ],
          ];
          break;
      }

      if (array_key_exists($key, $form['configure']['hyphenator_config'])) {
        $form['configure']['hyphenator_config'][$key]['#title'] = $value['title'];
        $form['configure']['hyphenator_config'][$key]['#default_value'] = $conf->get($key);
        $form['configure']['hyphenator_config'][$key]['#description'] = $value['description'];
      }
    }

    $form['configure']['hyphenator_exceptions'] = [
      '#type' => 'details',
      '#title' => t('Exceptions'),
      '#description' => t('Add words here that you find are wrongly hyphenated by <em>Hyphenator.js</em>.'),
    ];

    $rows = [];
    $exceptions = \Drupal::state()->get('hyphenator_exceptions', []);

    foreach ($exceptions as $key => $value) {
      $rows[] = [
        $key,
        $value,
        Link::fromTextAndUrl(t('edit'), Url::fromUserInput('/admin/config/content/hyphenator/edit/' . $key))
          ->toString(),
        Link::fromTextAndUrl(t('delete'), Url::fromUserInput('/admin/config/content/hyphenator/delete/' . $key))
          ->toString(),
      ];
    }
    $form['configure']['hyphenator_exceptions']['add'] = [
      '#type' => 'item',
      '#theme' => 'links',
      '#links' => [
        'add' => [
          'title' => t('Add an exception'),
          'url' => Url::fromRoute('hyphenator.add_exception'),
        ],
      ],
      '#attributes' => ['class' => 'action-links'],
    ];
    $form['configure']['hyphenator_exceptions']['table'] = [
      '#type' => 'markup',
      '#theme' => 'table',
      '#header' => [
        t('Language'),
        t('Words'),
        ['data' => t('Operations'), 'colspan' => 2],
      ],
      '#empty' => t('No exceptions recorded.'),
      '#rows' => $rows,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Settings form submission.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $values = $form_state->getValues();
    $conf = $this->configFactory->getEditable('hyphenator.settings');

    unset($values['add']);
    foreach ($values as $key => $value) {
      $conf->set($key, $value);
    }
    $conf->save();

    parent::submitForm($form, $form_state);
  }
}
