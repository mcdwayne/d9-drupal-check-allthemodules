<?php

namespace Drupal\google_image_sitemap\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form before clearing out the logs.
 */
class GoogleImageSitemapDeleteConfirmForm extends ConfirmFormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;
  private $sitemapId;

  /**
   * Constructs a new DblogClearLogConfirmForm.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_image_sitemap_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this sitemap?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('google_image_sitemap.list');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $sitemapId = NULL) {
    $this->sitemapId = $sitemapId;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = $this->connection->select('google_image_sitemap', 'gis')->fields('gis', ['sid', 'node_type'])->condition('sid', $this->sitemapId);
    $result = $query->execute()->fetch();
    if (!empty($result)) {
      $this->connection->delete('google_image_sitemap')->condition('sid', $this->sitemapId)->execute();
      $filename = $result->node_type == 'all' ? 'google_image_sitemap.xml' : 'sitemap_' . $result->node_type . '.xml';
      $path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://") . '/google_image_sitemap/' . $filename;
      if (file_exists($path)) {
        file_unmanaged_delete($path);
        drupal_set_message($this->t("Sitemap [@xml_file] deleted successfully!", ['@xml_file' => $filename]));
      }
      else {
        drupal_set_message($this->t("Sitemap deleted successfully!"));
      }
      $form_state->setRedirectUrl($this->getCancelUrl());
      return;
    }
    else {
      throw new NotFoundHttpException();
    }
  }

}
