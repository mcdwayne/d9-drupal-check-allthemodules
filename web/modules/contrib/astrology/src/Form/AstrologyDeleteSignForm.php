<?php

namespace Drupal\astrology\Form;

use Drupal\astrology\Controller\UtilityController;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Url;

/**
 * Class AstrologyDeleteSignForm.
 */
class AstrologyDeleteSignForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'astrology_delete_sign';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this sign?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('astrology.list_astrology_sign', ['astrology_id' => $this->astrology_id]);
  }

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Drupal\Core\Database\Connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Class constructor.
   */
  public function __construct(Connection $connection, ConfigFactoryInterface $config_factory) {
    $this->connection = $connection;
    $this->config = $config_factory;
    $this->utility = new UtilityController($this->connection, $this->config);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $astrology_id = NULL, $sign_id = NULL) {

    $signs = $this->utility->getAstrologySignArray($sign_id, $astrology_id);
    if ($astrology_id == 1 || $signs['astrology_id'] !== $astrology_id) {
      drupal_set_message($this->t("This sign can not be deleted"), 'error');
      throw new AccessDeniedHttpException();
    }
    $form['#title'] = $this->getQuestion();
    $this->astrology_id = $astrology_id;
    $this->sign_id = $sign_id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Delete all text for particular sign id, before deleting sign.
    $this->connection->delete('astrology_text')
      ->condition('astrology_sign_id', $this->sign_id, '=')
      ->execute();

    // Finally deleting sign.
    $this->connection->delete('astrology_signs')
      ->condition('id', $this->sign_id, '=')
      ->condition('astrology_id', $this->astrology_id, '=')
      ->execute();
    $form_state->setRedirect('astrology.list_astrology_sign', ['astrology_id' => $this->astrology_id]);
    drupal_set_message($this->t("Sing deleted."));

    // Invalidate astrology block cache on sing delete.
    UtilityController::invalidateAstrologyBlockCache();
  }

}
