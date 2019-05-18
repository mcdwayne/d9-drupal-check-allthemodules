<?php

/**
 * @file
 * The "Slug syncing" form.
 */

namespace Drupal\desk_net\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\desk_net\Controller\ModuleSettings;
use Drupal\desk_net\Collection\NoticesCollection;
use Drupal\desk_net\PageTemplate\PageTemplate;

class DeskNetSlugSyncing extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'desk_net_slug_syncing';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $link_slug_feature = \Drupal\Core\Link::fromTextAndUrl(t('slug feature'), \Drupal\Core\Url::fromUri("http://support.desk-net.com/hc/en-us/articles/115003545092", array('attributes' => array('target' => '_blank'))))->toString();

    // Slug field Desk-Net.
    $desk_net_slug['slug']['id'] = 'slug_syncing';
    $desk_net_slug['slug']['name'] = t('Slug (Desk-Net)');

    // Set values.
    $desk_net_slug_value['desk_net_slug_permalink_val']['id'] = 'url_alias';
    $desk_net_slug_value['desk_net_slug_permalink_val']['name'] = t('URL Alias');
    $desk_net_slug_value['desk_net_slug_headline_val']['id'] = 'title';
    $desk_net_slug_value['desk_net_slug_headline_val']['name'] = t('Title');

    $html = '<h2>' . t('Slug Syncing') . '</h2>';
    $html .= '<p>';
    $html .= t('Desk-Net syncs the Title, SEO Title and the URL Alias with the @link in Desk-Net. Please define what field should be primarily synced to Desk-Net in case of conflicts.', array('@link' => $link_slug_feature));
    $html .= '</p>';

    $form['html'] = array(
      '#markup' => $html,
    );

    $form['desk_net_slug_syncing'] = PageTemplate::desk_net_matching_page_template($desk_net_slug, $desk_net_slug_value, 'slug', '', 'url_alias');

    $form['#validate'][] = 'desk_net_form_matching_validation';

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
    $form['#submit'][] = 'desk_net_form_submit';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValues())) {
      foreach ($form_state->getValues() as $key => $value) {
        if ($key != 'form_id' && $key != 'op' && $key != 'form_token' &&
            $key != 'form_build_id' && $key != 'submit'
        ) {
          // Save value.
          ModuleSettings::variableSet($key, $value);
        }
      }
      drupal_set_message(NoticesCollection::getNotice(13), 'status');
    }
  }
}