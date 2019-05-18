<?php

/**
 * @file
 * Contains \Drupal\offline_app\Form\AppCachePagesForm;
 */

namespace Drupal\offline_app\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class AppCacheContentForm extends ConfigFormBase {

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
    return 'offline_app_appcache_pages_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('offline_app.appcache');

    $form['configuration'] = [
      '#type' => 'vertical_tabs'
    ];

    $form['pages_container'] = [
      '#type' => 'details',
      '#group' => 'configuration',
      '#title' => $this->t('Pages'),
    ];

    $form['pages_container']['pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pages'),
      '#title_display' => 'invisible',
      '#default_value' => $config->get('pages'),
      '#rows' => 20,
      '#description' => $this->t('Enter pages, line by line, that should be fetched for the application. Currently nodes and views are supported. Enter in the form of alias/node:id or alias/view:name:display.<br />For nodes, configure the "Offline page" view mode that is available. For Views, create an "offline" display.<br />Do not include a node or view if it\'s set as the homepage.'),
    ];

    $form['homepage_container'] = [
      '#type' => 'details',
      '#group' => 'configuration',
      '#title' => $this->t('Homepage'),
    ];

    $form['homepage_container']['homepage_type'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('Homepage type'),
      '#options' => [
        'custom' => $this->t('Custom content'),
        'page' => $this->t('Existing page'),
      ],
      '#default_value' => $config->get('homepage_type'),
    ];

    $form['homepage_container']['homepage_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config->get('homepage_title'),
      '#description' => $this->t('The title of the homepage.'),
      '#states' => [
        'invisible' => [
          ':radio[name="homepage_type"]' => ['value' => 'page'],
        ],
      ],
    ];

    $form['homepage_container']['homepage_content'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Content'),
      '#title_display' => 'invisible',
      '#default_value' => $config->get('homepage_content'),
      '#rows' => 10,
      '#description' => $this->t('Allowed tags:') . ' ' . implode(', ', Xss::getAdminTagList()),
      '#states' => [
        'invisible' => [
          ':radio[name="homepage_type"]' => ['value' => 'page'],
        ],
      ],
    ];

    $form['homepage_container']['homepage_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page'),
      '#default_value' => $config->get('homepage_page'),
      '#description' => $this->t('Enter either a node or a view in the form of node:1 or view:name:display.'),
      '#states' => [
        'invisible' => [
          ':radio[name="homepage_type"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['menu_container'] = [
      '#type' => 'details',
      '#group' => 'configuration',
      '#title' => $this->t('Menu'),
    ];

    $form['menu_container']['menu'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Menu'),
      '#title_display' => 'invisible',
      '#default_value' => $config->get('menu'),
      '#rows' => 10,
      '#description' => $this->t('Enter your menu configuration, line by line, which will be rendered in the application. Enter in the form of alias/title.<br />"appcache-fallback" as an alias will automatically point to the homepage.'),
    ];

    $form['image_style_container'] = [
      '#type' => 'details',
      '#group' => 'configuration',
      '#title' => $this->t('Images'),
    ];

    $form['image_style_container']['add_images_and_derivatives'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add images and derivatives to the manifest'),
      '#default_value' => $config->get('add_images_and_derivatives'),
    ];

    $form['image_style_container']['images_and_derivatives_list'] = [
      '#type' => 'textarea',
      '#rows' => 10,
      '#title' => $this->t('Images'),
      '#default_value' => $config->get('images_and_derivatives_list'),
      '#states' => [
        'visible' => [
          ':checkbox[name="add_images_and_derivatives"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $link = '<p>' . $this->t('<a href="/@url">Click here to update the list of images and derivatives.</a> Note that in case you add or update images, you will need to come here to refresh the list.', ['@url' => $this->getUrlGenerator()->getPathFromRoute('offline_app.appcache.admin_appcache_get_images')]) . '</p>';
    $form['image_style_container']['info_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':checkbox[name="add_images_and_derivatives"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['image_style_container']['info_container']['info'] = [
      '#markup' => $link,
    ];

    $form['messages_container'] = [
      '#type' => 'details',
      '#group' => 'configuration',
      '#title' => $this->t('Offline messages'),
    ];

    $form['messages_container']['first_time_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message on first time download'),
      '#default_value' => $config->get('first_time_text'),
      '#required' => TRUE,
    ];

    $form['messages_container']['update_ready_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message on update of content'),
      '#default_value' => $config->get('update_ready_text'),
      '#required' => TRUE,
    ];

    $form['preview'] = [
      '#markup' => $this->t('<a href="/@url" target="_blank">Click here to preview your offline version</a>', ['@url' => $this->getUrlGenerator()->getPathFromRoute('offline_app.appcache.fallback')]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('offline_app.appcache')
      ->set('homepage_type', $form_state->getValue('homepage_type'))
      ->set('homepage_content', $form_state->getValue('homepage_content'))
      ->set('homepage_title', $form_state->getValue('homepage_title'))
      ->set('homepage_page', $form_state->getValue('homepage_page'))
      ->set('pages', $form_state->getValue('pages'))
      ->set('menu', $form_state->getValue('menu'))
      ->set('add_images_and_derivatives', $form_state->getValue('add_images_and_derivatives'))
      ->set('images_and_derivatives_list', $form_state->getValue('images_and_derivatives_list'))
      ->set('first_time_text', $form_state->getvalue('first_time_text'))
      ->set('update_ready_text', $form_state->getvalue('update_ready_text'))
      ->save();
    Cache::invalidateTags(['appcache.manifest', 'appcache']);
    parent::submitForm($form, $form_state);
  }

}
