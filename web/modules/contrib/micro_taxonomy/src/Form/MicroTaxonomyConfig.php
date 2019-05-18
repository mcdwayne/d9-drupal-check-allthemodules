<?php

namespace Drupal\micro_taxonomy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\micro_site\Entity\Site;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\micro_site\Entity\SiteType;

class MicroTaxonomyConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['micro_taxonomy.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'micro_taxonomy_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $vocabularies = Vocabulary::loadMultiple();
    $config = $this->config('micro_taxonomy.settings');
    $options = [];

    /** @var \Drupal\taxonomy\VocabularyInterface $vocabulary */
    foreach ($vocabularies as $vocabulary) {
      // We don't want to configure vocabularies dedicated to site entity.
      $site_id = $vocabulary->getThirdPartySetting('micro_taxonomy', 'site_id', '');
      if ($site_id) {
        continue;
      }
      $options[$vocabulary->id()] = $vocabulary->label();
    }

    $form['vocabularies'] = [
      '#title' => t('The vocabularies we can associate with a site entity. This setting can be override per site type.'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $config->get('vocabularies'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('micro_taxonomy.settings');
    $config->set('vocabularies', array_filter($form_state->getValue('vocabularies')));
    $config->save();

    // We need to build custom dynamic routes and menus.
    drupal_flush_all_caches();
  }

}
