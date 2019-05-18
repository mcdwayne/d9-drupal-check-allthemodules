<?php

namespace Drupal\modal_config\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\modal_config\ModalConfigStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Builds the modal config deletion form.
 */
class ModalConfigDeleteForm extends EntityDeleteForm {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The modal config storage.
   *
   * @var \Drupal\modal_config\ModalConfigStorage
   */
  protected $storage;

  /**
   * Constructs a ModalConfigDeleteForm object.
   */
  public function __construct(Connection $database, ModalConfigStorage $storage) {
    $this->database = $database;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity.manager')->getStorage('modal_config')
    );
  }

}
