<?php
/**
 * @file
 * Contains \Drupal\pp_taxonomy_manager\Form\PPTaxonomyManagerDisconnectForm.
 */

namespace Drupal\pp_taxonomy_manager\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\pp_taxonomy_manager\Entity\PPTaxonomyManagerConfig;
use Drupal\pp_taxonomy_manager\PPTaxonomyManager;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The confirmation-form for disconnecting a Drupal taxonomy from a taxonomy on
 * a PP server.
 */
class PPTaxonomyManagerDisconnectForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pp_taxonomy_manager_disconnect_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param PPTaxonomyManagerConfig $config
   *   The configuration of the PoolParty Taxonomy manager.
   * @param Vocabulary $taxonomy
   *   The taxonomy to use.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $config = NULL, $taxonomy = NULL) {
    // Check if concept scheme URI is given and is a url.
    // Check if taxonomy exists.
    if ($taxonomy === FALSE) {
      drupal_set_message(t('The selected taxonomy does not exists.'), 'error');
      return new RedirectResponse(Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()))->toString());
    }

    $settings = $config->getConfig();
    if ($settings['root_level'] == 'project' && !isset($settings['taxonomies'][$taxonomy->id()])) {
      drupal_set_message(t('The selected taxonomy is not yet connected to PoolParty.'), 'error');
      return new RedirectResponse(Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()))->toString());
    }

    // Check if the taxonomy is connected with a concept scheme.
    if (!isset($settings['taxonomies'][$taxonomy->id()])) {
      drupal_set_message(t('The taxonomy %taxonomy is not connected, please export the taxonomy first.', array('%taxonomy' => $taxonomy->label())), 'error');
      return new RedirectResponse(Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()))->toString());
    }

    $description = t('The connection between the taxonomy %taxonomy and the PoolParty server will be deleted.', array('%taxonomy' => $taxonomy->label()));
    $description .= '<br />' . t('Please wait until the disconnection is finished.');

    $form['description'] = array(
      '#prefix' => '<p>',
      '#markup' => $description,
      '#suffix' => '</p>',
    );

    $form['save'] = array(
      '#type' => 'submit',
      '#value' => t('Disconnect taxonomy'),
    );
    $form['cancel'] = array(
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id())),
      '#suffix' => '</div>',
    );

    $form_state->set('config', $config);
    $form_state->set('taxonomy', $taxonomy);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var PPTaxonomyManagerConfig $config */
    $config = $form_state->get('config');
    /** @var Vocabulary $taxonomy */
    $taxonomy = $form_state->get('taxonomy');
    $manager = PPTaxonomyManager::getInstance($config);

    // Delete the connection.
    $manager->deleteConnection($taxonomy->id());

    // Delete all the logs and hash data.
    $manager->deleteSyncData($taxonomy->id());

    $connection = $config->getConnection();
    drupal_set_message(t('The connection between the Drupal taxonomy %taxonomy and the PoolParty server %server has been deleted successfully.', array(
      '%taxonomy' => $taxonomy->label(),
      '%server' => $connection->getTitle(),
    )));

    $form_state->setRedirect('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()));
  }
}
?>