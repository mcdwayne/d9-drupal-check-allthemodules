<?php

namespace Drupal\probo\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\ConfigFormBase;
use GuzzleHttp\Exception\ConnectException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\probo\Controller\ProboAssetController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Class ProboRepositoryController.
 */
class ProboRepositoryController extends ControllerBase {

  /**
   * display_repositories.
   *
   * @return array
   *   Return render array of a table of elements that make up the list
   *   of available repositories or an empty list.
   */
  public function display_repositories() {
    $query = \Drupal::database()->select('probo_repositories', 'pr')
      ->fields('pr', ['rid', 'owner', 'repository', 'token', 'roles'])
      ->condition('active', TRUE)
      ->orderBy('owner', 'ASC')
      ->orderBy('repository', 'ASC');
    $repositories = $query->execute()->fetchAllAssoc('rid');

    $header = [
      [
        'data' => 'Repository',
        'class' => 'probo-purple-dark probo-text-soft-peach',
      ],
      [
        'data' => 'Disc Space',
        'class' => 'probo-purple-dark probo-text-soft-peach center',
      ],
      [
        'data' => 'Active Builds',
        'class' => 'probo-purple-dark probo-text-soft-peach center',
      ],
    ];

    $rows = [];
    foreach ($repositories as $repository) {
      $permission = FALSE;
      $roles = unserialize($repository->roles);
      foreach($roles as $role) {
        $allowed = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id())->hasRole($role);
        if ($allowed == TRUE) {
          $permission = TRUE;
        }
      }

      if ($permission === FALSE) {
        continue;
      }

      $build_link = '/probo/' . $repository->rid;
      $query = \Drupal::database()->select('probo_builds', 'pb')
        ->fields('pb', ['id', 'rid', 'build_size'])
        ->condition('rid', $repository->rid);
      $active_builds = $query->execute()->fetchAllAssoc('id');
      $builds = $active_builds;
      $active_builds = count($active_builds);

      $size = 0;
      foreach ($builds as $active_build) {
        $size += $active_build->build_size;
      }
      $build_size = $size/(1024*1024);
      if ($build_size > 1000) {
        $build_size = $build_size / 1024;
        $build_unit = "GB";
      }
      else {
        $build_unit = "MB";
      }

      $row = [
        [
          'data' => $repository->repository,
          'class' => 'td-repository',
          'onclick' => "window.location.href='" . $build_link . "'",
        ],
        [
          'data' => number_format($build_size, 2) . ' ' . $build_unit,
          'class' => 'td-active-builds center',
          'onclick' => "window.location.href='" . $build_link . "'",
        ],
        [
          'data' => $active_builds,
          'class' => 'td-active-builds center',
          'onclick' => "window.location.href='" . $build_link . "'",
        ],
      ];
      $rows[] = $row;
    }

    return [
      '#type' => 'table',
      '#attributes' => ['class' => ['table table-striped']],
      '#prefix' => NULL,
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => 'THERE ARE NO REPOSITORIES CURRENTLY ASSIGNED.',
    ];
  }

  /**
   * repository_builds.
   *
   * @param int
   *  The repository id to view the builds for.
   *
   * @return array
   *   Return render array for our ReactJS component and the interface for
   *   maintaining the assets for this repository.
   */
  public function repository_builds($rid) {
    $config = $this->config('probo.probosettings');
    $probo_base_url = $config->get('base_url');

    $asset_table = NULL;
    $user = \Drupal::currentUser();
    $has_asset_permission = $user->hasPermission('access probo assets');
    if ($has_asset_permission === TRUE) {
      $query = \Drupal::database()->select('probo_assets', 'pa');
      $query->fields('pa', ['aid', 'rid', 'filename']);
      $query->addField('pr', 'owner');
      $query->addField('pr', 'repository');
      $query->leftJoin('probo_repositories', 'pr', 'pr.rid = pa.rid');
      $query->condition('pa.rid', $rid, '=');
      $query->orderBy('pr.owner', 'ASC');
      $query->orderBy('pr.repository', 'ASC');    
      $assets = $query->execute()->fetchAllAssoc('aid');

      $header = [
        [
          'data' => $this->t('Asset File Name'),
          'class' => 'probo-purple-dark probo-text-soft-peach repository bold',
        ],
        [
          'data' => '',
          'class' => 'probo-purple-dark probo-text-soft-peach repository right-text',
        ],
        [
          'data' => '',
          'class' => 'probo-purple-dark probo-text-soft-peach repository right-text',
        ],
      ];

      $rows = [];
      foreach ($assets as $asset) {
        $delete = Link::fromTextAndUrl($this->t('Delete'), Url::fromRoute('probo.probo_asset_delete', ['aid' => $asset->aid, 'rid' => $rid]))->toString();
        $download = Link::fromTextAndUrl($this->t('Download'), Url::fromRoute('probo.probo_asset_download', ['aid' => $asset->aid]))->toString();
        $row = [
          [
            'data' => $asset->filename,
          ],
          [
            'data' => new FormattableMarkup($delete, []),
            'class' => 'right-text',
          ],
          [ 
            'data' => new FormattableMarkup($download, []),
            'class' => 'right-text',
          ],
        ];
        $rows[] = $row;
      }

      $footer = [];
      $add_new = '<p align="right">' . Link::fromTextAndUrl(t('Add New Asset'), Url::fromRoute('probo.probo_asset_add',['rid' => $rid]))->toString() . '</p>';
    
      $asset_table = [
        '#attributes' => ['class' => ['table table-striped']],
        '#suffix' => $add_new,
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#footer' => $footer,
        '#empty' => 'THERE ARE NO ASSETS UPLOADED FOR THIS REPOSITORY.',
      ];
    }

    return [
      '#theme' => 'probo_reactjs',
      '#url' => $probo_base_url,
      '#rid' => $rid,
      '#asset_table' => $asset_table,
    ];
  }

  /**
   * admin_display_repositories.
   *
   * @return array
   *   Return render array of a table of elements that make up the list
   *   of available repositories or an empty list.
   */
   public function admin_display_repositories() {
    $query = \Drupal::database()->select('probo_repositories', 'pr')
      ->fields('pr', ['rid', 'owner', 'repository', 'token'])
      ->condition('active', TRUE)
      ->orderBy('owner', 'ASC')
      ->orderBy('repository', 'ASC');
    $repositories = $query->execute()->fetchAllAssoc('rid');

    $header = [
      [
        'data' => $this->t('Owner'),
      ],
      [
        'data' => $this->t('Repository'),
      ],
      [
        'data' => $this->t('Token'),
        'style' => 'text-align: center',
      ],
      [
        'data' => $this->t('Edit'),
        'style' => 'text-align: center',
      ],
      [
        'data' => $this->t('Delete'),
        'style' => 'text-align: center',
      ],
    ];

    $rows = [];
    foreach ($repositories as $repository) {
      $edit = Link::fromTextAndUrl(t('Edit'), Url::fromRoute('probo.admin_config_system_probo_repositories_update', ['rid' => $repository->rid]))->toString();
      $delete = Link::fromTextAndUrl(t('Delete'), Url::fromRoute('probo.admin_config_system_probo_repositories_delete', ['rid' => $repository->rid]))->toString();
      $row = [
        [
          'data' => $repository->owner,
          'style' => '',
        ],
        [
          'data' => $repository->repository,
          'style' => '',
        ],
        [
          'data' => $repository->token,
          'style' => 'text-align: center;',
        ],
        [
          'data' => $edit,
          'style' => 'text-align: center;',
        ],
        [
          'data' => $delete,
          'style' => 'text-align: center;',
        ],
      ];
      $rows[] = $row;
    }

    $link = '<p align="right">' . Link::fromTextAndUrl(t('Add Bucket/Repository'), Url::fromRoute('probo.admin_config_system_probo_repositories_add_new'))->toString() . '</p>';
    
    return [
      '#prefix' => $link,
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => 'THERE ARE NO REPOSITORIES.',
    ];
  }

  /**
   * admin_delete_repository().
   *
   * You cannot technically delete a repository/bucket. But we can remove all
   * of it's assets and mark it as deleted. If we re-create it, we will just 
   * re-enable it. Deleting doesn't really delete. It just sets the delete flag
   * to 1 to hide it from the interface. However, it does actually delete all
   * of the assets associated with that bucket.
   *
   * @param int
   *   The repository id to remove all of the assets from and mark as deleted.
   */
  public function admin_delete_repository($rid) {
    $config = $this->config('probo.probosettings');
    $client = \Drupal::httpClient();

    // First step is to remove all of the assets from the associated bucket/repo.
    $query = \Drupal::database()->select('probo_assets', 'pa');
    $query->fields('pa', ['aid', 'rid', 'filename', 'fileid']);
    $query->addField('pr', 'owner');
    $query->addField('pr', 'repository');
    $query->addField('pr', 'token');
    $query->join('probo_repositories', 'pr', 'pr.rid = pa.rid');
    $query->condition('pa.rid', $rid);
    $assets = $query->execute()->fetchAllAssoc('aid');
    foreach ($assets as $asset) {
      try {
        $buffer = $client->delete($config->get('asset_receiver_url_port') . '/buckets/' . $asset->owner . '-' . $asset->repository . '/assets/' . $asset->filename);
        $body = $buffer->getBody();
      }
      catch (ConnectException $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'Failed to connect')) {
          drupal_set_message('Unable to connect to ' . $config->get('asset_receiver_url_port'). ' - please check server or setting', 'error');
          return new RedirectResponse(Url::fromRoute('probo.admin_config_system_probo_repositories')->toString());
        }
      }
      drupal_set_message($body . ': ' . $asset->filename . ' successfully removed.');
    }

    // Mark the bucket/repo as inactive
    $query = \Drupal::database()->update('probo_repositories');
    $query->fields(['active' => 0]);
    $query->condition('rid', $rid);
    $query->execute();

    drupal_set_message('Bucket/repository has been successfully removed.');
    return new RedirectResponse(Url::fromRoute('probo.admin_config_system_probo_repositories')->toString());
  }

  /**
   * delete_asset($aid)
   * Remove an asset from the asset handler.
   *
   * @param int $aid
   *   The id of the asset we are removing.
   * @return RedirectResponse
   *   Redirect to the list of assets.
   */
  public function delete_asset($aid, $rid) {
    $client = \Drupal::httpClient();
    $config = $this->config('probo.probosettings');
    $asset_receiver_url = $config->get('asset_receiver_url_port');
    $asset_receiver_token = $config->get('asset_receiver_token');

    $params = (!empty($asset_receiver_token)) ? ['headers' => ['Authorization' => 'Bearer ' . $asset_receiver_token]] : [];

    // Get the filename, owner/organization and repository for deleting the asset.
    $query = \Drupal::database()->select('probo_assets', 'pa');
    $query->addField('pa', 'filename');
    $query->addField('pr', 'owner');
    $query->addField('pr', 'repository');
    $query->orderBy('pr.owner', 'ASC');
    $query->orderBy('pr.repository', 'ASC');
    $query->join('probo_repositories', 'pr', 'pr.rid = pa.rid');
    $query->condition('pa.aid', $aid);
    $assets = $query->execute()->fetchAllAssoc('rid');
    $assets = array_pop($assets);

    try {
      $response = $client->request('DELETE', $asset_receiver_url . '/buckets/' . $assets->owner . '-' . $assets->repository . '/assets/ ' . $assets->filename, $params);
      $buffer = $response->getBody();
    }
    catch (ConnectException $e) {
      $msg = $e->getMessage();
      if (strpos($msg, 'Failed to connect')) {
        drupal_set_message('Unable to connect to ' . $config->get('asset_receiver_url_port'). ' - please check server or setting', 'error');
        return new RedirectResponse(Url::fromRoute('probo.repository_builds')->toString());
      }
    }

    // Remove the reference from the table.
    $query = \Drupal::database()->delete('probo_assets')
      ->condition('aid', $aid)
      ->execute();

    drupal_set_message('The ' . $asset->filename . ' in the ' . $assets->owner . '-' . $assets->repository . ' bucket has been successfully deleted.');
    return new RedirectResponse(Url::fromRoute('probo.repository_builds', ['rid' => $rid])->toString());
  }

  /**
   * download_asset($aid)
   * Make a call to the asset received daemon and get the file and deliver it to the user.
   *
   * @param int $aid
   *   The id of the asset we are downloading.
   * @return RedirectResponse
   *   Redirect to the URL on the asset manager to begin the download.
   */
  public function download_asset($aid) {
    $config = $this->config('probo.probosettings');

    // Get the filename, owner/organization and repository for deleting the asset.
    $query = \Drupal::database()->select('probo_assets', 'pa');
    $query->addField('pa', 'filename');
    $query->addField('pr', 'owner');
    $query->addField('pr', 'repository');
    $query->orderBy('pr.owner', 'ASC');
    $query->orderBy('pr.repository', 'ASC');
    $query->join('probo_repositories', 'pr', 'pr.rid = pa.rid');
    $query->condition('pa.aid', $aid);
    $assets = $query->execute()->fetchAllAssoc('rid');
    $assets = array_pop($assets);

    // Construct the URL and then redirect to it to begint the download.
    $url = $config->get('asset_receiver_url_port') . '/asset/' . $assets->owner . '-' . $assets->repository . '/' . $assets->filename;
    return new TrustedRedirectResponse($url);
  }
}