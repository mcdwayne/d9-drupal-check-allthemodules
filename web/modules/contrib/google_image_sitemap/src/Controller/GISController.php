<?php

namespace Drupal\google_image_sitemap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Default controller for the google_image_sitemap module.
 */
class GISController extends ControllerBase {

  const GOOGLE_IMAGE_SITEMAP_ADMIN_PATH = '/admin/config/search/google_image_sitemap';

  protected $db;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database) {
    $this->db = $database;
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
   * Function to get available image sitemap.
   */
  public function getList() {
    $output = '';
    $header = [
      $this->t('S.NO.'),
      $this->t('SITEMAP URL'),
      $this->t('CONTENT TYPE'),
      $this->t('LAST UPDATED'),
      $this->t('Edit'),
      $this->t('Sitemap'),
    ];

    $query = $this->db->select('google_image_sitemap', 'g')
      ->fields('g');
    $result = $query->execute();
    $counter = 0;
    $rows = [];
    while ($gis_obj = $result->fetchObject()) {
      $is_exist = file_exists(\Drupal::service('file_system')->realpath(file_default_scheme() . "://") . '/google_image_sitemap/sitemap_' . $gis_obj->node_type . '.xml');
      $build_url = 'admin/config/search/google_image_sitemap/' . $gis_obj->sid . '/build';
      $generate_text = $this->t('Generate Sitemap');
      if ($is_exist) {
        $access_url = 'sites/default/files/google_image_sitemap/sitemap_' . $gis_obj->node_type . '.xml';
        $url = Url::fromUri('internal:/' . $access_url);
        $link_options = [
          'attributes' => [
            'title' => $this->t('Open sitemap'),
          ],
        ];
        $url->setOptions($link_options);
        $generate_text = $this->t('Re Generate Sitemap');
      }
      $build_link = Link::fromTextAndUrl($generate_text, url::fromUri('internal:/' . $build_url));
      $edit = 'admin/config/search/google_image_sitemap/' . $gis_obj->sid . '/edit';

      // Rows of table.
      $rows[] = [
        ++$counter,
        $build_link,
        $gis_obj->node_type,
        empty($gis_obj->last_updated) ? '-' : date('d-M-Y ', $gis_obj->last_updated),
        Link::fromTextAndUrl($this->t('Edit'), Url::fromUri('internal:/' . $edit))->toString(),
        $is_exist ? Link::fromTextAndUrl($this->t('Url'), $url)->toString() : $this->t('Url'),
      ];
    }

    $output = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#caption' => Link::fromTextAndUrl($this->t('Add a new sitemap'), Url::fromUserInput(static::GOOGLE_IMAGE_SITEMAP_ADMIN_PATH . '/add')),
      '#empty' => $this->t('No sitemap available.'),
    ];
    return $output;
  }

  /**
   * Function to generate image sitemap.
   */
  public function googleImageSitemapBuild($sitemap_id) {
    $query = $this->db->select('google_image_sitemap', 'g')
      ->fields('g')
      ->condition('sid', $sitemap_id);
    $result = $query->execute()->fetchObject();
    $filename = 'google_image_sitemap.xml';
    if (!empty($result)) {
      $query = $this->db->select('node_field_data', 'nfd');
      $query->fields('nfd', ['nid', 'title']);
      $query->fields('f', ['uri']);

      $query->innerJoin('file_usage', 'fu', "nfd.nid = fu.id");
      $query->innerJoin('file_managed', 'f', "fu.fid = f.fid");
      $query->condition('f.filemime', [
        'image/png',
        'image/jpg',
        'image/gif',
        'image/jpeg',
      ], 'IN');
      if ($result->node_type != 'all') {
        $query->condition('nfd.type', $result->node_type);
        $filename = 'google_image_sitemap_' . $result->node_type . '.xml';
      }
      $query->orderBy('nfd.nid', 'DESC');
      $nodes = $query->execute()->fetchAll();
      if (!empty($nodes)) {
        $output = '<?xml version="1.0" encoding="UTF-8"?>';
        $output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
                  xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
        foreach ($nodes as $node) {
          $output .= '<url><loc>' . Url::fromUri('internal:/node/' . $node->nid, ['absolute' => TRUE])->toString() . '</loc>
                     <image:image>
                       <image:loc>' . file_create_url($node->uri) . '</image:loc>
                       <image:title>' . $node->title . '</image:title>
                       <image:caption>' . $node->title . '</image:caption>
                       <image:license>' . $result->license . '</image:license>
                     </image:image></url>';
        }
        $output .= '</urlset>';

        // File build path.
        $path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://") . '/google_image_sitemap';
        if (!is_dir($path)) {
          \Drupal::service('file_system')->mkdir($path);
        }

        if ($file = file_unmanaged_save_data($output, $path . '/' . $filename, FILE_EXISTS_REPLACE)) {
          $this->db->update('google_image_sitemap')
            ->fields(['last_updated' => \Drupal::time()->getRequestTime()])
            ->condition('sid', $sitemap_id, '=')
            ->execute();
          drupal_set_message($this->t("Sitemap created successfully!"));
        }
      }
      else {
        drupal_set_message($this->t("No Images found!"));
      }
      global $base_url;
      $redirect = new RedirectResponse($base_url . '/' . GISController::GOOGLE_IMAGE_SITEMAP_ADMIN_PATH);
      $redirect->send();
    }
  }

  /**
   * Function to edit image sitemap.
   */
  public function editSitemap($sitemap_id) {
    $query = $this->db->select('google_image_sitemap', 'g')
      ->fields('g', ['sid', 'node_type', 'license'])
      ->condition('sid', $sitemap_id, '=');
    $result = $query->execute()->fetchObject();
    if (!empty($result)) {
      $form = \Drupal::formBuilder()->getForm('Drupal\google_image_sitemap\Form\GoogleImageSitemapCreateForm', $result);
      return $form;
    }
    else {
      throw new NotFoundHttpException();
    }
  }

}
