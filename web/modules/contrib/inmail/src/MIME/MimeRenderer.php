<?php

namespace Drupal\inmail\MIME;

use Drupal\Component\Utility\Xss;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Service class for rendering a MIME entity.
 *
 * @ingroup mime
 */
class MimeRenderer {

  use StringTranslationTrait;

  /**
   * Renders an entity.
   *
   * Multipart entities are rendered recursively.
   *
   * @param \Drupal\inmail\MIME\MimeEntityInterface $entity
   *   The entity to render.
   *
   * @return array
   *   A renderable array of the entity.
   */
  public function renderEntity(MimeEntityInterface $entity, $index = NULL) {
    // Enclose entity in a <details> element.
    $output = array(
      '#type' => 'details',
      '#title' => $this->t('(No subject)'),
      '#open' => TRUE,
    );

    // Set the details summary.
    if ($subject = $entity->getHeader()->getFieldBody('Subject')) {
      $output['#title'] = htmlentities($subject);
    }
    elseif (!empty($index)) {
      $output['#title'] = $this->t('Part @index', ['@index' => $index]);
    }
    else {
      $output['#title'] = $this->t('(No subject)');
    }

    // Render header.
    $output['header'] = $this->renderHeaderFields($entity, ['From', 'To', 'Subject', 'Content-Type']);
    // The Content-Type header needs some cleaning.
    $content_type = $entity->getContentType();
    $output['header']['content_type']['#markup'] = $content_type['type'] . '/' . $content_type['subtype'];

    if ($entity instanceof MimeMultipartEntity) {
      foreach ($entity->getParts() as $part_index => $part) {
        $output['parts'][] = $this->renderEntity($part, $part_index + 1);
      }
    }
    else {
      $output['body'] = $this->renderBody($entity);
    }

    return $output;
  }

  /**
   * Renders each existing header field of the given set.
   */
  public function renderHeaderFields(MimeEntityInterface $entity, array $field_names) {
    $headers = array();
    foreach ($field_names as $field_name) {
      if ($entity->getHeader()->getFieldBody($field_name)) {
        $field_name_clean = str_replace('-', '_', strtolower($field_name));
        $headers[$field_name_clean] = $this->renderHeaderField($entity, $field_name);
      }
    }
    return $headers;
  }

  /**
   * Renders a single header field.
   */
  public function renderHeaderField(MimeEntityInterface $entity, $field_name) {
    $field_body = $entity->getHeader()->getFieldBody($field_name);
    return array(
      '#type' => 'item',
      '#title' => $this->t($field_name),
      '#markup' => htmlentities($field_body),
    );
  }

  /**
   * Renders the body of a non-multipart entity.
   */
  public function renderBody(MimeEntityInterface $entity) {
    $content_type = $entity->getContentType();
    switch ($content_type['type']) {
      case 'text':
        if ($content_type['subtype'] == 'html') {
          // Content-Type: text/html
          return array('#markup' => Xss::filter($entity->getDecodedBody(), $this->getAllowedHtmlTags()));
        }
        // Content-Type: text/*
        return array(
          '#prefix' => '<pre>',
          '#markup' => htmlentities($entity->getDecodedBody()),
          '#suffix' => '</pre>',
        );

      default:
        return $this->renderHeaderFields($entity, ['Content-Id']);
    }
  }

  /**
   * Returns HTML tags allowed for rendering.
   *
   * @return string[]
   *   A list of HTML element names.
   */
  public function getAllowedHtmlTags() {
    return array(
      // Tags in Xss::filter() default parameters.
      'a', 'em', 'strong', 'cite', 'blockquote', 'code', 'ul', 'ol', 'li', 'dl', 'dt', 'dd',
      // Additionally allowed tags.
      'table', 'tr', 'th', 'td', 'img', 'style',
    );
  }

}
