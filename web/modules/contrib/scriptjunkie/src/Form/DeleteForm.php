<?php

namespace Drupal\scriptjunkie\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\scriptjunkie\ScriptJunkieStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete a Script Junkie.
 */
class DeleteForm extends ConfirmFormBase {

  /**
   * The Script Junkie storage service.
   *
   * @var \Drupal\scriptjunkie\ScriptJunkieStorageInterface
   */
  protected $scriptJunkieStorage;

  /**
   * The Script Junkie being deleted.
   *
   * @var array $scriptJunkie
   */
  protected $scriptJunkie;

  /**
   * Constructs a \Drupal\path\Form\DeleteForm object.
   *
   * @param \Drupal\scriptjunkie\ScriptJunkieStorageInterface $scriptjunkie_storage
   *   The scriptjunkie storage service.
   */
  public function __construct(ScriptJunkieStorageInterface $scriptjunkie_storage) {
    $this->scriptJunkieStorage = $scriptjunkie_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('scriptjunkie.scriptjunkie_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'script_junkie_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete Script %title?', array('%title' => $this->scriptJunkie['name']));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('scriptjunkie.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $sid = NULL) {
    $this->scriptJunkie = $this->scriptJunkieStorage->load(array('sid' => $sid));

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->scriptJunkieStorage->delete(array('sid' => $this->scriptJunkie['sid']));

    $form_state->setRedirect('scriptjunkie.settings');
  }

}
