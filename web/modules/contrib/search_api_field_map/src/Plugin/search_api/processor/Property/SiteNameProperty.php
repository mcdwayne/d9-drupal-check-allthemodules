<?php

namespace Drupal\search_api_field_map\Plugin\search_api\processor\Property;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;

/**
 * Defines a "site name" property.
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\SiteName
 */
class SiteNameProperty extends ConfigurablePropertyBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'site_name' =>  [\Drupal::config('system.site')->get('name')],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(FieldInterface $field, array $form, FormStateInterface $form_state) {
    $configuration = $field->getConfiguration();

    // Create a link to the Basic Site Settings Page from route.
    $options = [
      'attributes' => [
        'target' => '_blank'
      ],
    ];
    $basic_site_settings_page_url = Url::fromRoute('system.site_information_settings', [], $options);
    $basic_site_settings_page_link = Link::fromTextAndUrl('Basic Site settings page', $basic_site_settings_page_url)->toString();


    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';
    $form['#tree'] = TRUE;

    $form['site_name_group'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Site Name'),
      '#description' => $this->t('The name of the site from which this content originated. This can be useful if indexing multiple sites with a single search index.'),
    ];

    $form['site_name_group']['use_system_site_name'] = [
      '#type' => 'checkbox',
      '#title' => '<b>' . $this->t('Use the site name from '. $basic_site_settings_page_link  .' > Site Details > Site Name') . '</b>',
      '#default_value' => isset($configuration['use_system_site_name']) ? $configuration['use_system_site_name'] : 0,
      '#description' => $this->t('This option is recommended for multisite installations that share config across sites.'),
      '#attributes' => [
        'data-use-system-site-name' => TRUE,
      ],
    ];

    $form['site_name_group']['site_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site Name'),
      '#description' => $this->t('Create a Site Name.'),
      '#default_value' => isset($configuration['site_name']) ? $configuration['site_name'] : '',
      '#states' => [
        'visible' => [
          ':input[data-use-system-site-name]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(FieldInterface $field, array &$form, FormStateInterface $form_state) {
    // Confirm that at least one field is populated.
    if (!$form_state->getValue(['site_name_group', 'use_system_site_name']) && !strlen($form_state->getValue(['site_name_group', 'site_name']))) {
      $form_state->setError($form['site_name_group'], $this->t('Please either select the option to use the system site name or enter a site name.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(FieldInterface $field, array &$form, FormStateInterface $form_state) {
    $values = [
      'use_system_site_name' => $form_state->getValue(['site_name_group', 'use_system_site_name']),
      'site_name' => $form_state->getValue(['site_name_group', 'site_name']),
    ];
    $field->setConfiguration($values);
  }

}
