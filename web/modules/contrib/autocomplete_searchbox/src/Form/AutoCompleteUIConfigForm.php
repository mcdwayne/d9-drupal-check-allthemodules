<?php

namespace Drupal\autocomplete_searchbox\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\Role;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Configure CHN careers settings.
 */
class AutoCompleteUIConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autocomplete_searchbox_ui_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'autocomplete_searchbox.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('autocomplete_searchbox.settings');
    global $base_url;
    $page['#attached']['library'][] = 'autocomplete_searchbox/autocomplete.search.color';
    //Sample.
    $form['fieldset_sample'] = array(
      '#type' => 'details',
      '#title' => $this->t('Sample'),
      '#description' => $this->t('This is how autocomplete dropdown result will look like. Color settings apply only to labels.'),
      '#open' => TRUE,
    );
    $form['fieldset_sample']['autocomplete_searchbox_sample'] = array(
      '#markup' => '<div class="as-result result-first"><span class="top">Entity name</span> :: autocomplete result 1</div><div class="as-result result-second"><span class="middle"">Entity name</span> :: autocomplete result 2</div><div class="as-result result-third"><span class="last">Entity name</span> :: autocomplete result 3</div>',
    );

    // Color Defaults
    $form['fieldset_defaults'] = array(
      '#type' => 'details',
      '#title' => $this->t('Defaults'),
      '#description' => $this->t('Default color codes to use for entities, example #636363. Values provided below will always have greater precedence.'),
      '#open' => TRUE,
    );

    $form['fieldset_defaults']['autocomplete_searchbox_ctype_bgcolor'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('autocomplete_searchbox_ctype_bgcolor', ''),
      '#title' => $this->t('Node Background color'),
      '#attributes' => array(
        'placeholder' => '#ffffff',
      )
    );

    $form['fieldset_defaults']['autocomplete_searchbox_ctype_color'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('autocomplete_searchbox_ctype_color', ''),
      '#title' => $this->t('Node Text color'),
      '#attributes' => array(
        'placeholder' => '#636363',
      )
    );

    $form['fieldset_defaults']['hr1'] = array(
      '#markup' => '<br /><hr /><br />',
    );

    $form['fieldset_defaults']['autocomplete_searchbox_term_bgcolor'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('autocomplete_searchbox_term_bgcolor', ''),
      '#title' => $this->t('Term Background color'),
      '#attributes' => array(
        'placeholder' => '#ffffff',
      )
    );

    $form['fieldset_defaults']['autocomplete_searchbox_term_color'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('autocomplete_searchbox_term_color', ''),
      '#title' => $this->t('Term Text color'),
      '#attributes' => array(
        'placeholder' => '#636363',
      )
    );

    $form['fieldset_defaults']['hr2'] = array(
      '#markup' => '<br /><hr /><br />',
    );

    $form['fieldset_defaults']['autocomplete_searchbox_user_dbgcolor'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('autocomplete_searchbox_user_dbgcolor', ''),
      '#title' => $this->t('User Background color'),
      '#attributes' => array(
        'placeholder' => '#ffffff',
      )
    );

    $form['fieldset_defaults']['autocomplete_searchbox_user_color'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('autocomplete_searchbox_user_color', ''),
      '#title' => $this->t('User Text color'),
      '#attributes' => array(
        'placeholder' => '#636363',
      )
    );

    $form['fieldset_defaults']['hr3'] = array(
      '#markup' => '<br /><hr /><br />',
    );

    // Override Color Defaults
    $form['fieldset_override_defaults'] = array(
      '#type' => 'details',
      '#title' => $this->t('Override Defaults'),
      '#description' => $this->t('Default color codes mentioned above can be overridden here.'),
      '#open' => TRUE,
    );

    //  Select node types.
    $url = Url::fromUri('internal:/admin/config/search/autocomplete-search-config');
    $render_link = Link::fromTextAndUrl('settings', $url);
    $form['fieldset_override_defaults']['fieldset_content_type'] = array(
      '#type' => 'details',
      '#title' => $this->t('Content types'),
      '#description' => $this->t('Color codes to highlight content type names in the autocomplete searchbox. The following fields come from what you enable on the @settings page. Enter a color code, for example #FFFFFF', array('@settings' => $render_link->toString())),
      '#open' => FALSE,
    );

    $contentTypes = \Drupal::service('entity.manager')
      ->getStorage('node_type')
      ->loadMultiple();
    foreach ($contentTypes as $name) {
      $form['fieldset_override_defaults']['fieldset_content_type']['fieldset_' . $name->id()] = array(
        '#type' => 'details',
        '#title' => $this->t($name->id()),
        '#open' => FALSE,
      );
      $form['fieldset_override_defaults']['fieldset_content_type']['fieldset_' . $name->id()]['autocomplete_searchbox_bgcolor_' . $name->id()] = array(
        '#type' => 'textfield',
        '#default_value' => $config->get('autocomplete_searchbox_bgcolor_' . $name->id(), ''),
        '#title' => $this->t('Background color'),
      );
      $form['fieldset_override_defaults']['fieldset_content_type']['fieldset_' . $name->id()]['autocomplete_searchbox_fcolor_' . $name->id()] = array(
        '#type' => 'textfield',
        '#default_value' => $config->get('autocomplete_searchbox_fcolor_' . $name->id(), ''),
        '#title' => $this->t('Text color'),
      );
    }

    //  Select terms.
    $form['fieldset_override_defaults']['fieldset_term'] = array(
      '#type' => 'details',
      '#title' => $this->t('Terms'),
      '#description' => $this->t('Color codes to highlight category names in the autocomplete searchbox. The following fields come from what you enable on the @settings page. Enter a color code, for example #FFFFFF', array('@settings' => $render_link->toString())),
      '#open' => FALSE,
    );

    $vids = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();
    foreach ($vids as $vid) {
      $container = \Drupal::getContainer();
      $terms = $container->get('entity.manager')
        ->getStorage('taxonomy_term')
        ->loadTree($vid->id());
      if (!empty($terms)) {
        foreach ($terms as $name) {
          $form['fieldset_override_defaults']['fieldset_term']['fieldset_' . $name->name] = array(
            '#type' => 'details',
            '#title' => t($name->name),
            '#open' => FALSE,
          );
          $form['fieldset_override_defaults']['fieldset_term']['fieldset_' . $name->name]['autocomplete_searchbox_bgcolor_' . $name->name] = array(
            '#type' => 'textfield',
            '#default_value' => $config->get('autocomplete_searchbox_bgcolor_' . $name->name, ''),
            '#title' => $this->t('Background color'),
          );
          $form['fieldset_override_defaults']['fieldset_term']['fieldset_' . $name->name]['autocomplete_searchbox_fcolor_' . $name->name] = array(
            '#type' => 'textfield',
            '#default_value' => $config->get('autocomplete_searchbox_fcolor_' . $name->name, ''),
            '#default_value' => $config->get('autocomplete_searchbox_fcolor_' . $name->name, ''),
            '#title' => $this->t('Text color'),
          );
        }
      }
    }

    // Select users.
    $form['fieldset_override_defaults']['fieldset_user'] = array(
      '#type' => 'details',
      '#title' => $this->t('Users'),
      '#description' => $this->t('Color codes to highlight user entity name in the autocomplete searchbox. The following fields come from what you enable on the @settings page. Enter a color code, for example #FFFFFF', array('@settings' => $render_link->toString())),
      '#open' => FALSE,
    );
    $form['fieldset_override_defaults']['fieldset_user']['autocomplete_searchbox_user_bgcolor'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('autocomplete_searchbox_user_bgcolor', ''),
      '#title' => $this->t('Background color'),
    );
    $form['fieldset_override_defaults']['fieldset_user']['autocomplete_searchbox_user_fcolor'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('autocomplete_searchbox_user_fcolor', ''),
      '#title' => $this->t('Text color'),
    );

    $form['#attached']['library'][] = 'autocomplete_searchbox/autocomplete.search.color';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('autocomplete_searchbox.settings');

    // Save form values.
    $form_values = $form_state->getValues();
    foreach ($form_values as $key => $value) {
      $config->set($key, $form_state->getValue($key))
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
