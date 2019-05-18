<?php

namespace Drupal\activity\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Delete base form.
 */
abstract class DeleteForm extends FormBase {

  /**
   * The connection to the database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  protected $pathArgs;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Constructs an object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The connection to the database.
   * @param \Drupal\Core\Path\CurrentPathStack $currentPath
   *   The current path.
   */
  public function __construct(Connection $database, CurrentPathStack $currentPath) {
    $this->database = $database;
    $this->currentPath = $currentPath;
    $this->pathArgs = $path_args = explode('/', $this->currentPath->getPath());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('path.current')
    );
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getFormId();

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $label = '') {
    $form['delete_activities'] = [
      '#type' => 'label',
      '#title' => '',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Delete'),
    ];
    $form['cancel_delete'] = [
      '#title' => $this->t('Cancel'),
      '#type' => 'link',
      '#url' => Url::fromUri('internal:/admin/activity'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function submitForm(array &$form, FormStateInterface $form_state);

}
