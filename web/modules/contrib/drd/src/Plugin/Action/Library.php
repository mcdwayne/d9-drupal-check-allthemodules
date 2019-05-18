<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;
use Drupal\drd\HttpRequest;

/**
 * Provides a 'Library' action.
 *
 * @Action(
 *  id = "drd_action_library",
 *  label = @Translation("Library"),
 *  type = "drd_domain",
 * )
 */
class Library extends BaseEntityRemote implements BaseConfigurableInterface {

  /**
   * {@inheritdoc}
   */
  protected function setDefaultArguments() {
    parent::setDefaultArguments();
    $this->arguments['source'] = 'official';
  }

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $domain) {
    static $buildCompleted;

    if ($this->arguments['source'] == 'official') {
      $archive = 'drd-' . HttpRequest::getVersion() . '.phar';
      $this->arguments['url'] = 'http://cgit.drupalcode.org/drd_agent_lib/plain/' . $archive;
    }
    else {
      if (empty($buildCompleted)) {
        // Re-build PHAR.
        \Drupal::service('drd.library.build')->build($this->arguments);
        $buildCompleted = TRUE;
      }
    }

    return parent::executeAction($domain);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['drd_action_library_source'] = [
      '#type' => 'select',
      '#title' => t('Mode'),
      '#default_value' => 'official',
      '#options' => [
        'official' => t('Published version from drupal.org'),
        'local' => t('Local development version'),
        'none' => t('None, update build only'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->arguments['source'] = $form_state->getValue('drd_action_library_source');
  }

}
