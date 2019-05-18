<?php

namespace Drupal\fillpdf\Service;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\fillpdf\Entity\FillPdfForm;
use Drupal\fillpdf\FillPdfLinkManipulatorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 */
class FillPdfLinkManipulator implements FillPdfLinkManipulatorInterface {

  /**
   * {@inheritdoc}
   *
   * @todo: Maybe this should return a FillPdfLinkContext object or something?
   *   Guess it depends on how much I end up needing to change it.
   */
  public function parseRequest(Request $request) {
    // @todo: Use Url::fromRequest when/if it lands in core. See https://www.drupal.org/node/2605530
    $path = $request->getUri();
    $request_url = $this->createUrlFromString($path);

    return static::parseLink($request_url);
  }

  /**
   * Creates a URL object from an internal path or external URL.
   *
   * @param string $url
   *   The internal path or external URL string.
   *
   * @return \Drupal\Core\Url
   *   A Url object representing the URL string.
   *
   * @see FillPdfLinkManipulatorInterface::parseUrlString()
   */
  protected function createUrlFromString($url) {
    $url_parts = UrlHelper::parse($url);
    $path = $url_parts['path'];
    $query = $url_parts['query'];

    $link = Url::fromUri($path, ['query' => $query]);
    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public static function parseLink(Url $link) {
    $query = $link->getOption('query');

    if (!$query) {
      throw new \InvalidArgumentException("This link doesn't specify a query string, so failing.");
    }

    $request_context = [
      'entity_ids' => [],
      'fid' => NULL,
      'sample' => FALSE,
      'force_download' => FALSE,
      'flatten' => TRUE,
    ];

    if (!empty($query['fid'])) {
      $request_context['fid'] = $query['fid'];
    }
    else {
      throw new \InvalidArgumentException('No FillPdfForm was specified in the query string, so failing.');
    }

    if (isset($query['download']) && filter_var($query['download'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === TRUE) {
      $request_context['force_download'] = TRUE;
    }

    if (isset($query['flatten']) && $query['flatten'] !== '' && filter_var($query['flatten'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === FALSE) {
      $request_context['flatten'] = FALSE;
    }

    // Early return if PDF is just to be populated with sample data.
    if (isset($query['sample']) && filter_var($query['sample'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === TRUE) {
      $request_context['sample'] = TRUE;
      return $request_context;
    }

    if (!empty($query['entity_type'])) {
      $request_context['entity_type'] = $query['entity_type'];
    }

    if (!empty($query['entity_id']) || !empty($query['entity_ids'])) {
      $entity_ids = (!empty($query['entity_id']) ? [$query['entity_id']] : $query['entity_ids']);

      // Re-key entity IDs so they can be loaded easily with loadMultiple().
      // If we have type information, add it to the types array, and remove it
      // in order to make sure we only store the ID in the entity_ids key.
      foreach ($entity_ids as $entity_id) {
        $entity_id_parts = explode(':', $entity_id);

        if (count($entity_id_parts) == 2) {
          $entity_type = $entity_id_parts[0];
          $entity_id = $entity_id_parts[1];
        }
        elseif (!empty($request_context['entity_type'])) {
          $entity_type = $request_context['entity_type'];
        }
        else {
          $entity_type = 'node';
        }
        $request_context['entity_ids'] += [
          $entity_type => [],
        ];

        $request_context['entity_ids'][$entity_type][$entity_id] = $entity_id;
      }
    }
    else {
      // Populate defaults.
      $fillpdf_form = FillPdfForm::load($request_context['fid']);

      if (!$fillpdf_form) {
        throw new \InvalidArgumentException("The requested FillPdfForm doesn't exist, so failing.");
      }

      $default_entity_id = $fillpdf_form->default_entity_id->value;
      if ($default_entity_id) {
        $default_entity_type = $fillpdf_form->default_entity_type->value;
        if (empty($default_entity_type)) {
          $default_entity_type = 'node';
        }

        $request_context['entity_ids'] = [
          $default_entity_type => [$default_entity_id => $default_entity_id],
        ];
      }
    }

    // We've processed the shorthand forms, so unset them.
    unset($request_context['entity_id'], $request_context['entity_type']);

    return $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public function parseUrlString($url) {
    $link = $this->createUrlFromString($url);
    return static::parseLink($link);
  }

  /**
   * {@inheritdoc}
   */
  public function generateLink(array $parameters) {
    $query = [];

    if (!isset($parameters['fid'])) {
      throw new \InvalidArgumentException("The $parameters argument must contain the fid key (the FillPdfForm's ID).");
    }

    $query['fid'] = $parameters['fid'];

    // Only set the following properties if they're not at their default values.
    // This makes the resulting Url a bit cleaner.
    // Structure:
    //   '<key in context array>' => [
    //     ['<key in query string>', <default system value>]
    //     ...
    //   ]
    // @todo: Create a value object for FillPdfMergeContext and get the defaults
    // here from that.
    $parameter_info = [
      'sample' => ['sample', FALSE],
      'force_download' => ['download', FALSE],
      'flatten' => ['flatten', TRUE],
    ];
    foreach ($parameter_info as $context_key => $info) {
      $query_key = $info[0];
      $parameter_default = $info[1];
      if (isset($parameters[$context_key]) && $parameters[$context_key] != $parameter_default) {
        $query[$query_key] = $parameters[$context_key];
      }
    }

    // $query['entity_ids'] contains entity IDs indexed by entity type.
    // Collapse these into the entity_type:entity_id format.
    $query['entity_ids'] = [];
    if (!empty($parameters['entity_ids'])) {
      $entity_info = $parameters['entity_ids'];
      foreach ($entity_info as $entity_type => $entity_ids) {
        foreach ($entity_ids as $entity_id) {
          $query['entity_ids'][] = "{$entity_type}:{$entity_id}";
        }
      }
    }

    $fillpdf_link = Url::fromRoute('fillpdf.populate_pdf',
      [],
      ['query' => $query]);

    return $fillpdf_link;
  }

}
