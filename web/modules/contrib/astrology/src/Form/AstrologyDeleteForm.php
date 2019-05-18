<?php

namespace Drupal\astrology\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Url;
use Drupal\astrology\Controller\UtilityController;

/**
 * Class AstrologyDeleteSignForm.
 */
class AstrologyDeleteForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'astrology_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this astrology?');
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
    return new Url('astrology.list_astrology');
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
  public function buildForm(array $form, FormStateInterface $form_state, $astrology_id = NULL) {
    if ($astrology_id == 1) {
      drupal_set_message($this->t("This astrology can not be deleted"), 'error');
      throw new AccessDeniedHttpException();
    }
    $form['#title'] = $this->getQuestion();
    $this->astrology_id = $astrology_id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $result = $this->connection->query("SELECT enabled,name FROM {astrology} WHERE id = :id ",
    [':id' => $this->astrology_id]);
    $row = $result->fetchObject();

    // Get all sign ids.
    $sign_ids = $this->connection->query("SELECT id FROM {astrology_signs} WHERE astrology_id = :id ",
    [':id' => $this->astrology_id]);

    // Delete all text for particular sign id.
    while ($ids = $sign_ids->fetchObject()) {
      $this->connection->query("DELETE FROM {astrology_text} WHERE astrology_sign_id = :id",
      [':id' => $ids->id]);
    }

    // Now deleted all sign ids.
    $this->connection->query("DELETE FROM {astrology_signs} WHERE astrology_id = :id",
    [':id' => $this->astrology_id]);

    // Finally delete astrology.
    $result = $this->connection->query("DELETE FROM {astrology} WHERE id = :id",
    [':id' => $this->astrology_id]);
    if ($row->enabled) {
      $this->utility->updateDefaultAstrology($this->astrology_id, $row->enabled, 'delete');
    }
    drupal_set_message($this->t("Astrology %name deleted.", ['%name' => $row->name]));
    $form_state->setRedirect('astrology.list_astrology');
  }

}
