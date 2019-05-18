<?php

namespace Drupal\inmail\MIME;

/**
 * A service that provides decomposition features for a message.
 *
 * @ingroup processing
 */
class MimeMessageDecomposition implements MimeMessageDecompositionInterface {

  /**
   * {@inheritdoc}
   */
  public function getEntities(MimeEntityInterface $entity, $current_path = '') {
    $entities = [];
    // Add the original message as the first element.
    if ($current_path === '') {
      $entities['~'] = $entity;
    }

    // In case the message is a multipart entity recurse into the parts.
    if ($entity instanceof MimeMultipartEntity) {
      foreach ($entity->getParts() as $index => $message_part) {
        // Build an entity path: "{current_path} + underscore + {index}".
        $path = trim($current_path . '_' . $index, '_');
        $entities[$path] = $message_part;
        $entities += $this->getEntities($message_part, $path);
      }
    }
    elseif ($current_path !== '') {
      $entities[$current_path] = $entity;
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityByPath(MimeEntityInterface $message, $path) {
    $path_levels = explode('_', $path);

    if (count($path_levels) > 1) {
      $message_part_index = reset($path_levels);
      unset($path_levels[$message_part_index]);
      return $this->getEntityByPath($message->getPart($message_part_index), implode('_', $path_levels));
    }

    return $message->getPart($path);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntitiesByType(MimeEntityInterface $entity, array $types) {
    $filtered_entities = [];
    foreach ($this->getEntities($entity) as $path => $entity) {
      if (in_array($entity->getType(), $types)) {
        $filtered_entities[$entity->getType()][$path] = $entity;
      }
    }

    return $filtered_entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildAttachment($path, MimeEntityInterface $attachment, $download_url = NULL) {
    $type = $attachment->getContentType()['type'];
    $content_type = $type . '/' . $attachment->getContentType()['subtype'];
    $filename = !empty($attachment->getContentType()['parameters']['name']) ? $attachment->getContentType()['parameters']['name'] : $content_type;
    $encoding = $attachment->getContentTransferEncoding();
    $content = $attachment->getBody();
    $filesize = inmail_message_get_attachment_file_size($content, $encoding);

    // Create a build array.
    $build = [
      'type' => $type,
      'content_type' => $content_type,
      'filename' => $filename,
      'encoding' => $encoding,
      'content' => $content,
      'filesize' => $filesize,
    ];

    // In case there is an implementation of the download attachment
    // integration, extend the URL with the attachment path. This path is used
    // to find a corresponding MIME entity.
    /** @var \Drupal\Core\Url $download_url */
    if ($download_url) {
      $build['url'] = $download_url->setRouteParameter('path', $path)->toString();
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getBodyPaths(MimeMessageInterface $message) {
    // Filter entities by text/plain and text/html types.
    $body_entities = $this->getEntitiesByType($message, ['text/plain', 'text/html']);

    // Get the first occurrence of plain and HTML entities.
    return [
      'plain' => isset($body_entities['text/plain']) ? key($body_entities['text/plain']) : NULL,
      'html' => isset($body_entities['text/html']) ? key($body_entities['text/html']) : NULL,
    ];
  }

}
