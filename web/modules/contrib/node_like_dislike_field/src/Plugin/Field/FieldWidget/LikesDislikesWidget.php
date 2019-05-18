<?php

namespace Drupal\node_like_dislike_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Database\Connection;

/**
 * Plugin implementation of the 'likes_dislikes_default_widget' widget.
 *
 * @FieldWidget(
 *   id = "likes_dislikes_default_widget",
 *   label = @Translation("Like dislike widget"),
 *   field_types = {
 *     "likes_dislikes"
 *   }
 * )
 */
class LikesDislikesWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  protected $tempStore;
  protected $fieldDefinition;
  protected $currentPath;
  protected $database;
  protected $minScale = 1;
  protected $maxScale = 15;

  /**
   * A render array with flagplus banners (if any applicable).
   *
   * @param string $plugin_id
   *   Returns the plugin id.
   * @param mixed $plugin_definition
   *   Returns the plugin definition.
   * @param Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Returns the field definition.
   * @param array $settings
   *   A render array with flagplus banners (if any applicable).
   * @param array $third_party_settings
   *   A render array with flagplus banners (if any applicable).
   * @param Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   Returns the temporary store factory.
   * @param Drupal\Core\Path\CurrentPathStack $current_path
   *   Returns the current path.
   * @param Drupal\Core\Database\Connection $database
   *   Returns the database connection.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, PrivateTempStoreFactory $temp_store_factory, CurrentPathStack $current_path, Connection $database) {

    // For "mymodule_name," any unique namespace will do.
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->tempStore = $temp_store_factory->get('node_like_dislike_field');
    $this->fieldDefinition = $field_definition;
    $this->currentPath = $current_path;
    $this->database = $database;
  }

  /**
   * A render array with flagplus banners (if any applicable).
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Returns the container variable.
   * @param array $configuration
   *   A render array with flagplus banners (if any applicable).
   * @param string $plugin_id
   *   Returns the plugin id.
   * @param mixed $plugin_definition
   *   Returns mixed plugin definition.
   *
   * @return \static
   *   returns user.private_tempstore, path.current, database.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('user.private_tempstore'),
      $container->get('path.current')  ,
      $container->get('database')
    );
  }

  /**
   * Overrides formElement function.
   *
   * @param Drupal\Core\Field\FieldItemListInterface $items
   *   Contains the listInterface items for object.
   * @param mixed $delta
   *   Contains $delta value for node.
   * @param array $element
   *   Contains $element value for node.
   * @param array $form
   *   Contains the build-form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   Contains the formstate.
   *
   * @return array
   *   A render array with flagplus banners (if any applicable).
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $date_def = date('Y/m/d');
    $data1 = $items[$delta]->likes;
    $data2 = $items[$delta]->dislikes;
    $element = [];
    $element['likes'] = [
      '#title' => $this->t('Likes'),
      '#type' => 'hidden',
      '#default_value' => isset($items[$delta]->likes) ? $items[$delta]->likes : 0,
      '#min' => 0,
    ];

    $element['dislikes'] = [
      '#title' => $this->t('Dislikes'),
      '#type' => 'hidden',
      '#default_value' => isset($items[$delta]->dislikes) ? $items[$delta]->dislikes : 0,
      '#min' => 0,
    ];

    $element['label1'] = [
      '#type' => 'details',
      '#title' => $this->t('Statistics Showing Total No. of Likes and Dislikes'),
      '#attributes' => ['class' => 'label1'],
    ];
    $element['label1']['graph-con'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'graph'],
    ];
    $element['label2'] = [
      '#type' => 'details',
      '#title' => $this->t('Statistics Showing User Visit in maximum @maxScale days', ['@maxScale' => $this->maxScale]),
      '#attributes' => ['class' => 'label1'],
    ];
    $element['label2']['date'] = [
      '#type' => 'date',
      '#title' => $this->t('Select Date'),
      '#description' => $this->t('Select the scale date(Records will be shown of past @maxScale days(max) from selected date)', ['@maxScale' => $this->maxScale]),
      '#attributes' => ['type' => 'date', 'min' => '-25 years', 'max' => 'now'],
      '#default_value' => $date_def,
      '#date_date_format' => 'Y/m/d',
    ];
    $element['label2']['scale'] = [
      '#type' => 'select',
      '#options' => array_combine(range($this->minScale, $this->maxScale), range($this->minScale, $this->maxScale)),
      '#title' => $this->t('Select Scale'),
      '#description' => $this->t('Select the scale(@minScale - @maxScale)', ['@minScale' => $this->minScale, '@maxScale' => $this->maxScale]),
      '#default_value' => 10,
    ];

    $element['label2']['go'] = [
      '#type' => 'submit',
      '#value' => $this->t('Enter'),
      '#ajax' => [
        'callback' => [$this, 'submitajax'],
        'event' => 'click',
        'progress' => ['type' => 'throbber', 'message' => NULL],
      ],
    ];
    $element['label2']['graph-con1'] = [
      '#type' => 'container',
      '#prefix' => '<div id="graphResult"></div>',
      '#suffix' => '<div id="graphResult1"></div>',
    ];

    $element['label2']['graph-con2'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'graph1'],
      '#suffix' => '<div id="htmlcom"></div>',
    ];

    $form['#attached']['library'][] = 'node_like_dislike_field/node_like_dislike_field';
    $form['#attached']['drupalSettings']['node_like_dislike_field'] = [
      'first' => $data1,
      'second' => $data2,
    ];

    return $element;
  }

  /**
   * Ajax callback function on button click.
   *
   * @param mixed[] $form
   *   Returns the build form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   Returns the current formstate.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   A render array with flagplus banners (if any applicable).
   */
  public function submitajax(array &$form, FormStateInterface $form_state) {
    $field_name = $form_state->getTriggeringElement()['#array_parents'][0];
    $response = new AjaxResponse();
    $current_path = $this->currentPath->getPath();
    $code = explode('/', $current_path);
    $db = $this->database;
    $selectdate = $form_state->getValues()[$field_name][0]["label2"]["date"];
    $scalecount = $form_state->getValues()[$field_name][0]["label2"]["scale"];
    $scalecount = ($scalecount > $this->maxScale) ? $this->maxScale : $scalecount;
    $today = strtotime($selectdate);
    $last = strtotime("-" . $scalecount . "day", $today);
    $query1 = $db->select('like_count', 'x');
    $query1->fields('x', ['date_timestamp', 'likes']);
    $and = db_and();
    $and->condition('nid', $code[2]);
    $and->condition('date_timestamp', [$last, $today], 'BETWEEN');
    $query1->condition($and);
    $result = $query1->execute()->fetchAll();
    $query2 = $db->select('like_count', 'x');
    $query2->fields('x', ['date_timestamp', 'dislikes']);
    $and->condition('nid', $code[2]);
    $and->condition('date_timestamp', [$last, $today], 'BETWEEN');
    $query2->condition($and);
    $result2 = $query2->execute()->fetchAll();
    $count = 0;
    $date_count = 0;
    $dis_count = 0;
    $d_c = 0;
    while ($count != $scalecount) {
      $d = strtotime("-" . $count . " day", $today);
      if ($result[$date_count]->date_timestamp == $d) {
        $result[$date_count]->date_timestamp = date('d M Y', (int) $result[$date_count]->date_timestamp);
        $myarray[] = $result[$date_count];
        $date_count++;
      }
      else {
        $new = new \stdClass();
        $new->date_timestamp = date('d M Y', $d);
        $new->likes = "0";
        $myarray[] = $new;
      }
      $count++;
    }
    while ($dis_count != $scalecount) {
      $d = strtotime("-" . $dis_count . " day", $today);
      if ($result2[$d_c]->date_timestamp == $d) {
        $result2[$d_c]->date_timestamp = date('d M Y', (int) $result2[$d_c]->date_timestamp);
        $new = new \stdClass();
        $new->date_timestamp = $result2[$d_c]->date_timestamp;
        $new->likes = $result2[$d_c]->dislikes;
        $myarray2[] = $new;
        $d_c++;
      }
      else {
        $new = new \stdClass();
        $new->date_timestamp = date('d M Y', $d);
        $new->likes = "0";
        $myarray2[] = $new;
      }
      $dis_count++;
    }
    $text = '<ul class = "coordinates">
          <li class = "a">
              <em> Likes</em></li>
          <li class = "b">
              <em> Dislikes</em></li >
         </ul>';
    $response->addCommand(new RemoveCommand('#graphResult > div'));
    $response->addCommand(new RemoveCommand('#graphResult1 > div'));
    $response->addCommand(new AppendCommand('#graphResult', json_encode($myarray)));
    $response->addCommand(new AppendCommand('#graphResult1', json_encode($myarray2)));
    $response->addCommand(new HtmlCommand('#htmlcom', $text));
    return $response;
  }

}
