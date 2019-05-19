<?php

namespace Drupal\stacks\Widget;

use Drupal\Core;
use Drupal\stacks\Widget\WidgetData;

/**
 * Class WidgetFieldHandlers
 */
class WidgetFieldHandlers {

  private $field_type;
  private $field;
  private $value;
  private $delta;

  /**
   * Returns the render array based on field type.
   *
   * @param $field_type
   * @param $field
   * @param $value
   * @param $delta
   */
  function __construct($field_type, $field, $value, $delta) {
    // Set the values
    $this->field_type = $field_type;
    $this->field = $field;
    $this->value = $value;
    $this->delta = $delta;
  }

  /**
   *  Function to be used for unsupported fields
   *
   * @param  [string] $error Error message to render.
   */
  protected function unsupported($error) {
    // @see \Drupal\Core\Render\Element\StatusMessages::renderMessages()
    return [
      '#theme' => 'status_messages',
      // @todo Improve when https://www.drupal.org/node/2278383 lands.
      '#message_list' => [
        'error' => [$error],
      ],
      '#status_headings' => [
        'error' => t('Error message'),
      ],
      // This is just for the odd case in which you could need this on your
      // twig templates. i.e. if you are just iterating fields
      '#stacks_unsupported' => TRUE,
    ];
  }

  /**
   * Returns render array based on field type.
   */
  public function getRenderArray() {
    $method = 'field_' . $this->field_type;
    if (!method_exists($this, $method)) {
      // Debug,
      $field_name = $this->field->getName();
      $field_type_plugin = \Drupal::service('plugin.manager.field.field_type')->getDefinition($this->field_type);
      return $this->unsupported(t("<em>@type (Module: @module)</em> field type for %fieldname is not currently supported by Stacks.", [
        '@method' => $method,
        '@type' => $this->field_type,
        '%fieldname' => $field_name,
        '@module' => $field_type_plugin['provider'],
      ]));
    }

    return $this->$method();
  }

  /**
   * Returns the value of the string.
   *
   * This returns a simple #markup render array, when there is a 'value'.
   * {{ fields.field_name }}
   *
   * If a format value is sent with this field value, we will respect that so
   * the html get processed with that text format.
   */
  private function string() {
    if (isset($this->value['value']) && isset($this->value['format'])) {
      return [
        '#type' => 'processed_text',
        '#text' => $this->value['value'],
        '#format' => $this->value['format'],
      ];
    }
    
    return [
      '#markup' => isset($this->value['value']) ? $this->value['value'] : '',
    ];
  }

  /**
   * Returns a number value.
   *
   * This returns the value under the "num" key in the render array.
   * {{ fields.field_name.num }}
   */
  private function num() {
    return [
      'num' => isset($this->value['value']) ? $this->value['value'] : '',
    ];
  }

  /**
   * Returns an image.
   *
   * Returns image.url and image.alt. To output a specific image style, send
   * this array to a custom twig extension.
   * {{ fields.image_field|image('large', 'test_class') }}
   *
   * Use {{ fields.image_field.url }} to access the original url of the image.
   */
  private function field_image() {
    if (!$this->field->entity) {
      return [];
    }

    $uri = $this->field[$this->delta]->entity->getFileUri();

    return [
      'url' => file_create_url($uri),
      'title' => isset($this->field[$this->delta]->title) ? $this->field[$this->delta]->title : '',
      'alt' => isset($this->field[$this->delta]->alt) ? $this->field[$this->delta]->alt : '',
      'uri' => $uri,
    ];
  }

  /**
   * Returns an embed video values.
   *
   * Returns video.url, video.id (YouTube), video.player
   *
   * {{ fields.field_test_video.url }}
   * {{ fields.field_test_video.id }}
   * {{ fields.field_test_video.player }}
   */
  private function field_video_embed_field() {
    // @TODO Make this more flexible
    if (!isset($this->value['value'])) {
      return [];
    }

    $id = explode('?v=', $this->value['value']);
    $player = '';

    if (count($id) == 1) {
      $id = 0;
    }
    else {
      $id = $id[1];
      $player = '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . $id . '?rel=0&enablejsapi=1" frameborder="0" allowfullscreen></iframe>';
    }

    return [
      'url' => $this->value['value'],
      'id' => $id,
      'player' => t($player),
    ];
  }

  /**
   * Returns a file.
   *
   * Returns URL of the file.
   * {{ fields.field_test_file.url }}
   */
  private function field_file() {
    if (!$this->field[$this->delta]->entity) {
      return [];
    }

    $uri = $this->field[$this->delta]->entity->getFileUri();

    return [
      'url' => file_create_url($uri),
      'description' => (isset($this->value['description']) ? $this->value['description'] : ''),
      'uri' => $uri,
    ];
  }


  /**
   * Returns a link value.
   *
   * Returns link.title and link.url. Also returns link.html with the render
   * array for the full link html.
   *
   * {{ fields.field_test_link.title }}
   * {{ fields.field_test_link.url }}
   * {{ fields.field_test_link.html }}
   */
  private function field_link() {
    $uri = $this->value['uri'];

    if (empty($uri)) {
      return [];
    }

    $url_object = Core\Url::fromUri($uri);
    if (strpos($uri, 0, 7) != 'http://' && strpos($uri, 0, 8) != 'https://') {
      $url = $url_object->toString();
    }
    else {
      $url = $uri;
    }

    return [
      'title' => $this->value['title'],
      'url' => $url,
      'html' => Core\Link::fromTextAndUrl($this->value['title'], $url_object)
        ->toRenderable(),
    ];
  }

  /**
   * Returns a datetime value.
   *
   * Returns the value of the string. Use it like this:
   * {{ fields.field_test_date.markup|date("m/d/Y") }}
   */
  private function field_datetime() {
    return [
      'date' => isset($this->value['value']) ? $this->value['value'] : '',
    ];
  }

  /**
   * Returns a boolean value (true or false).
   *
   * {% if fields.field_test_boolean.checked %}
   * {% endif %}
   */
  private function field_boolean() {
    return [
      'checked' => ($this->value['value'] == "1") ? TRUE : FALSE,
    ];
  }

  /**
   * Returns an entity reference.
   *
   * Output a reference field that only allows one value:
   * {{ fields.field_test_content.entity|view_mode('teaser') }}
   *
   * If your content reference field has multiple values, loop through it like
   * normal:
   * {% for row in fields.field_test_content %}
   *   {{ row.entity|view_mode('teaser') }}
   * {% endfor %}
   */
  private function field_entity_reference() {
    // Make sure not to reference possible orphaned entity references
    $referenced_entities = $this->field->referencedEntities();
    if (empty($referenced_entities)) {
      return [];
    }

    $entity = $referenced_entities[$this->delta];

    // Is this a contact form entity reference?
    if ($entity->getEntityTypeId() == 'contact_form') {

      // Add the form as a variable to the entity reference.
      // This allows the form to be printed like this: {{ fields.field_select_contact_form.form }}
      $message = \Drupal::entityTypeManager()
        ->getStorage('contact_message')
        ->create([
          'contact_form' => $entity->id(),
        ]);
      $form = \Drupal::service('entity.form_builder')->getForm($message);

      return ['form' => $form];
    }

    if (!method_exists($entity, 'getFields')) {
      return [];
    }

    // Return all of the fields for this entity.
    $get_field_data = new WidgetData();
    $data = ['entity_id' => $entity->id()];

    if (method_exists($entity, 'getType')) {
      $data['bundle'] = $entity->getType();
    }

    $data['label'] = $entity->label();

    $data['entity_type'] = $entity->getEntityTypeId();

    $field_exceptions = WidgetData::fieldExceptionsDefault();
    foreach ($entity->getFields() as $key => &$field) {
      // Don't include the fields/properties we should ignore by default.
      if (in_array($key, $field_exceptions)) {
        continue;
      }

      $get_field_data->fieldValues($key, $field, $data);
    }

    return $data;
  }

  /**
   * Do not support a widget field within a widget, also if connected through
   * an entity reference.
   *
   * @todo Rethink this.
   */
  private function field_stacks_type() {
    return [];
  }

  /**
   * These methods all use empty string.
   */
  private function field_path() {
    return '';
  }

  /**
   * These methods all use empty string.
   */
  private function field_metatag() {
    return '';
  }

  /**
   * These methods all use $this->string().
   */
  private function field_string() {
    return $this->string();
  }

  private function field_list_string() {
    return $this->string();
  }

  private function field_text() {
    return $this->string();
  }

  private function field_text_long() {
    return $this->string();
  }

  private function field_text_with_summary() {
    return $this->string();
  }

  private function field_string_long() {
    return $this->string();
  }

  private function field_email() {
    return $this->string();
  }

  /**
   * These methods all use $this->num().
   */
  private function field_list_integer() {
    return $this->num();
  }

  private function field_list_float() {
    return $this->num();
  }

  private function field_integer() {
    return $this->num();
  }

  private function field_decimal() {
    return $this->num();
  }

  private function field_float() {
    return $this->num();
  }

}
