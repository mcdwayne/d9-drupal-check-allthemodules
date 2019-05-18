<?php

/**
 * @file
 * Contains \Drupal\searchcloud_block\Form\SearchCloudBlockAdminForm.
 */

namespace Drupal\searchcloud_block\Form;

class SearchCloudBlockAdminForm extends SearchCloudBlockFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'searchcloud_block_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('searchcloud_block.settings');

    $form['info'] = array(
      '#markup' => $this->t('These are the settings available for the Searchcloud block module.'),
    );

    $form['overridepath'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Path'),
      '#description'   => t('Override the search path prefix (i.e. when a view is used as search page target)'),
      '#size'          => 30,
      '#default_value' => $config->get('overridepath'),
    );
    $form['useparam']     = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Use a parameter for the searchterm'),
      '#default_value' => $config->get('useparam'),
    );
    $form['paramname']    = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Parameter name'),
      '#size'          => 30,
      '#default_value' => $config->get('paramname'),
      '#states'        => array(
        'invisible' => array(
          ':input[name="searchcloud_block_useparam"]' => array('checked' => FALSE),
        ),
      ),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $values = $form_state['values'];

    $config = $this->configFactory->get('searchcloud_block.settings');
    $config->set('overridepath', $values['overridepath'])
      ->set('useparam', $values['useparam'])
      ->set('paramname', $values['paramname'])->save();
  }

}
