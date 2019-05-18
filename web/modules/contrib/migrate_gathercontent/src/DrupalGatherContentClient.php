<?php

namespace Drupal\migrate_gathercontent;

use Cheppers\GatherContent\GatherContentClient;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Crypt;
use GuzzleHttp\ClientInterface;

// I'll probably need to strip out that module and brave the open seas alone.
/**
 * Extends the GatherContentClient class with Drupal specific functionality.
 */
class DrupalGatherContentClient extends GatherContentClient {

  /**
   * {@inheritdoc}
   */
  public function __construct(ClientInterface $client) {
    parent::__construct($client);
    $this->setCredentials();
    $this->cache = \Drupal::service('cache.data');
  }

  /**
   * Put the authentication config into client.
   */
  public function setCredentials() {
    $config = \Drupal::config('migrate_gathercontent.settings');
    $this->setEmail($config->get('gathercontent_email') ?: '');
    $this->setApiKey($config->get('gathercontent_api_key') ?: '');
  }

  /**
   * Retrieve the account id of the given account.
   *
   * If none given, retrieve the first account by default.
   */
  public static function getAccountId() {
    return \Drupal::config('migrate_gathercontent.settings')
      ->get('gathercontent_account');
  }

  /**
   * Retrieve all the active projects.
   */
  public function getActiveProjects($account_id) {
    $projects = $this->projectsGet($account_id);

    foreach ($projects as $id => $project) {
      if (!$project->active) {
        unset($projects[$id]);
      }
    }

    return $projects;
  }

  /**
   * Returns a formatted array with the template ID's as a key.
   *
   * @param int $project_id
   *   Project ID.
   *
   * @return array
   *   Return array.
   */
  public function getTemplatesOptionArray($project_id) {
    $formatted = [];
    $templates = $this->templatesGet($project_id);

    foreach ($templates as $id => $template) {
      $formatted[$id] = $template->name;
    }

    return $formatted;
  }

  /**
   * Generate cache ID.
   *
   * @param string $prefix
   *   The unique cache prefix.
   * @param array $args
   *   An array of request parameters.
   *
   * @return string
   *   The full cache id string.
   */
  private function generateCacheId($prefix, array $args) {
    if (is_array($args)) {
      foreach ($args as $key => $value) {
        $args[$key] = Unicode::strtolower($value);
      }
    }
    $string = implode(':', $args);
    $cache_key = Crypt::hashBase64(serialize($string));
    return $prefix . ':' . $cache_key;
  }

  /**
   * {@inheritdoc}
   */
  public function itemFilesGet($item_id) {
    $cid = $this->generateCacheId('migrate_gathercontent:files', ['item_id' => $item_id]);
    $data = $this->cache->get($cid);

    if (!empty($data)) {
      return $data->data;
    }
    else {
      $data = parent::itemFilesGet($item_id);
      $this->cache->set($cid, $data);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function itemsGet($project_id) {
    $data = [];
    $cid = $this->generateCacheId('migrate_gathercontent:items', ['project_id' => $project_id]);
    // TODO: Fix caching, seems to be causing a bug that breaks import.
    $items = $this->cache->get($cid);

    if (!empty($items)) {
      $items = $items->data;
    }
    else {
      $items = parent::itemsGet($project_id);
      $this->cache->set($cid, $items);
    }

    if (!empty($template_id)) {
      foreach ($items as $item) {
        if ($item->templateId == $template_id) {
          $data[] = $item;
        }
      }
    }
    else {
      $data = $items;
    }

    return $data;
  }

  /**
   * Fetches items based on project and template id.
   *
   * @param integer $project_id
   * @param integer $template_id
   * @return array
   */
  public function itemsGetTemplate($project_id, $template_id) {
    $data = [];
    $cid = $this->generateCacheId('migrate_gathercontent:items', ['project_id' => $project_id, 'template_id' => $template_id]);
    // TODO: Fix caching, seems to be causing a bug that breaks import.
    $items = $this->cache->get($cid);

    if (!empty($items)) {
      $data = $items->data;
    }
    else {
      $items = parent::itemsGet($project_id);

      if (!empty($template_id)) {
        foreach ($items as $item) {
          if ($item->templateId == $template_id) {
            $data[] = $item;
          }
        }
      }
      $this->cache->set($cid, $data);
    }

    return $data;
  }

  /**
   * Returns a normalized item array.
   *
   * @param $item_id
   *
   * @return array
   */
  public function itemGetFormatted($item_id) {

    // Get full item object.
    $item = parent::itemGet($item_id);

    // Get any files related to this content
    // TODO: This is extremely expensive. We need a better way to do this.
    $files = $this->itemFilesGet($item_id);

    $data = [];
    $data['id'] = $item->id;
    $data['name'] = $item->name;
    $data['status'] = $item->status->name;
    $data['templateid'] = $item->templateId;
    $data['projectid'] = $item->projectId;

    foreach ($item->config as $tab) {
      foreach ($tab->elements as $field) {
        $value = [];

        // Setting standard field values.
        $data['fields'][$field->id] = [
          'id' => $field->id,
          'type' => $field->type,
          'label' => $field->label,
        ];

        // The value can change depending on context.
        // Set the value to the file id if applicable.
        if ($field->type == 'files') {
          foreach ($files as $file) {
            if ($file->field == $field->id) {
              $value[] = [
                'id' => $file->id,
                'url' => $file->url,
                'filename' => $file->fileName,
                'created' => $file->createdAt,
                'changed' => $file->updatedAt,
              ];
            }
          }
        }
        // Radio select list.
        else if ($field->type == 'choice_radio') {
            foreach ($field->options as $option) {
                if ($option['selected']) {
                    $value = $option['label'];
                }
            }

        }
        // Checkbox select list.
        else if ($field->type == 'choice_checkbox') {
            foreach ($field->options as $option) {
                if ($option['selected']) {
                    $value[] = $option['label'];
                }
            }
        }
        // Default to value
        elseif (isset($field->value)) {
          // GatherContent sometimes injects a zero-width space character (#8203).
          $value = preg_replace( '/[\x{200B}-\x{200D}]/u', '', $field->value);
        }

        $data['fields'][$field->id]['value'] = $value;
      }
    }
    return $data;
  }

  /**
   * Returns the response body.
   *
   * @param bool $json_decoded
   *   If TRUE the method will return the body json_decoded.
   *
   * @return \Psr\Http\Message\StreamInterface
   *   Response body.
   */
  public function getBody($json_decoded = FALSE) {
    $body = $this->getResponse()->getBody();

    if ($json_decoded) {
      return \GuzzleHttp\json_decode($body);
    }

    return $body;
  }

}
