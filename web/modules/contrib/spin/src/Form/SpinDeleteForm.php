<?php

namespace Drupal\spin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\spin\SpinStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Spin admin form.
 */
class SpinDeleteForm extends FormBase {
  protected $logger;

  /**
   * Constructs a form object with dependencies.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   The logging manager.
   */
  public function __construct(LoggerChannelFactory $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('logger.factory'));
  }

  /**
   * The spin profile admin form.
   *
   * @param array $form
   *   A drupal form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal form state object.
   * @param int $sid
   *   The spin ID.
   *
   * @return array
   *   A Drupal form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $sid = 0) {
    $form['sid'] = [
      '#type'  => 'hidden',
      '#value' => $sid ? $sid : '',
    ];
    $form['warning'] = [
      '#type'   => 'markup',
      '#markup' => '<p>' . $this->t('Delete the "@label" profile? This action cannot be undone.', ['@label' => SpinStorage::getLabel($sid)]) . '</p>',
    ];
    $form['actions']['submit'] = [
      '#type'   => 'submit',
      '#value'  => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * The form ID.
   *
   * @return string
   *   The form ID.
   */
  public function getFormId() {
    return 'spin_delete';
  }

  /**
   * Delete a spin profile.
   *
   * @param array $form
   *   A drupal form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal form state object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    SpinStorage::deleteSpin($form_state->getValue('sid'));

    $this->logger->get('spin')->notice('Spin profile %sid deleted.', ['%sid' => $form_state->getValue('sid')]);

    drupal_set_message($this->t('Spin profile %sid deleted.', ['%sid' => $form_state->getValue('sid')]));

    $form_state->setRedirect('spin.list');
  }

  /**
   * Validation function for the spin profile delete form.
   *
   * @param array $form
   *   A drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal FormStateInterface object.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (SpinStorage::getName($form_state->getValue('sid')) == 'default') {
      $form_state->setErrorByName('sid', $this->t('Default profiles cannot be deleted.'));
    }
  }

}
