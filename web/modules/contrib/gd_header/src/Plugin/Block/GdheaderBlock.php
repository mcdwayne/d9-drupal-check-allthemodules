<?php

namespace Drupal\gd_header\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\NodeType;

/**
 * Provides a block with a responsive image as background and page title.
 *
 * @Block(
 *   id = "gdheader",
 *   admin_label = @Translation("GD Header"),
 *   category = @Translation("Theme"),
 * )
 */
class GdheaderBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    // Page title.
    $request = \Drupal::request();
    $route_match = \Drupal::routeMatch();
    $title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());
    $title = is_array($title) ? $title['#markup'] : $title;

    // Markup.
    $markup = '<h1>' . $title . '</h1>';

    // Images.
    if (!empty($config['gdheader_img']) && !empty($config['gdheader_img_style'])) {

      // Number of images.
      $count = count($config['gdheader_img']);

      // Choose a random image.
      $i = rand(0, $count - 1);

      // Image style.
      $style = $config['gdheader_img_style'];

      // Image URL.
      $fid = $config['gdheader_img'][$i];
      $file = File::load($fid);
      if ($file) {
        $path = $file->getFileUri();
        $url = ImageStyle::load($style)->buildUrl($path);
      }
    }

    return array(
      '#markup' => $markup,
      '#attached' => array(
        'library' => array(
          'gd_header/gd-header',
        ),
        'drupalSettings' => array(
          'gd_header' => array(
            'block_img_url' => isset($url) ? $url : '',
          ),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['gdheader_node_type'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Node types'),
      '#description' => t('For each node type, you can choose an image field to use with GD Header. If there is no image in this field for a given node, then the default images you upload below will be used.'),
    );

    // Select node types.
    $node_types = NodeType::loadMultiple();

    foreach ($node_types as $node_type) {

      $options = array();

      $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $node_type->id());
      foreach ($fields as $field_name => $field_definition) {
        if (!empty($field_definition->getTargetBundle())) {
          if ($field_definition->getType() == 'image') {
            $options[$field_name] = $field_definition->getLabel();
          }
        }
      }

      $form['gdheader_node_type'][$node_type->id()] = array(
        '#type' => 'select',
        '#title' => $node_type->label(),
        '#description' => t('Choose an image field.'),
        '#options' => $options,
        '#default_value' => isset($config['gdheader_node_type'][$node_type->id()]) ? $config['gdheader_node_type'][$node_type->id()] : '',
      );
    }

    // Image style.
    $options = array();

    $styles = ImageStyle::loadMultiple();
    foreach ($styles as $style) {
      $options[$style->id()] = $style->getName();
    }

    $form['gdheader_img_style'] = array(
      '#type' => 'select',
      '#title' => t('Image style'),
      '#description' => t('Choose the image style to use.'),
      '#options' => $options,
      '#default_value' => isset($config['gdheader_img_style']) ? $config['gdheader_img_style'] : '',
    );

    $form['gdheader_img'] = array(
      '#type' => 'managed_file',
      '#name' => 'gdheader_img',
      '#default_value' => isset($config['gdheader_img']) ? $config['gdheader_img'] : '',
      '#title' => t('Default images'),
      '#description' => t('Upload images in .jpg, .jpeg or .png'),
      '#upload_validators' => array(
        'file_validate_extensions' => array(
          'jpg jpeg png',
        ),
      ),
      '#upload_location' => 'public://gd_header/',
      '#multiple' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();

    $this->configuration['gdheader_node_type'] = $values['gdheader_node_type'];
    $this->configuration['gdheader_img_style'] = $values['gdheader_img_style'];
    $this->configuration['gdheader_img'] = $values['gdheader_img'];
    $images = $values['gdheader_img'];

    // Set the files permanent and add usages.
    if (isset($images)) {

      foreach ($images as $image) {

        $file = File::load($image);
        if ($file) {

          // If the file is not already permanent:
          if (!($file->isPermanent())) {

            // Then set the file as permanent.
            $file->setPermanent();
            $file->save();
          }

          // If the file has not already an usage for this module and block:
          $file_usage = \Drupal::service('file.usage')->listUsage($file);

          if (isset($file_usage['gd_header'])) {
            if (isset($file_usage['gd_header']['block'])) {
              if (isset($file_usage['gd_header']['block']['gdheader'])) {
                continue;
              }
            }
          }
          else {

            // Then add the file usage.
            \Drupal::service('file.usage')->add($file, 'gd_header', 'block', 'gdheader');
          }
        }
      }
    }

    // Remove usage for all other files (removed with "Remove") button.
    $files_usages = \Drupal::database()
      ->select('file_usage', 'u')
      ->fields('u')
      ->condition('u.module', 'gd_header', '=')
      ->condition('u.type', 'block', '=')
      ->condition('u.id', 'gdheader', '=')
      ->execute();

    $fids_usages_to_delete = array();

    while ($file_usage = $files_usages->fetchAssoc()) {

      // Skip the file if it's really used by gd_header block configuration.
      if (isset($images)) {

        if (in_array($file_usage['fid'], $images)) {
          continue;
        }
        else {
          $fids_usages_to_delete[] = $file_usage['fid'];
        }
      }
      else {
        $fids_usages_to_delete[] = $file_usage['fid'];
      }
    }

    foreach ($fids_usages_to_delete as $fid_usage_to_delete) {
      $file_to_delete = File::load($fid_usage_to_delete);

      // Delete the file usage.
      \Drupal::service('file.usage')->delete($file_to_delete, 'gd_header', 'block', 'gdheader');

      // Set the file as temporary so it can be deleted by Drupal.
      $file_to_delete->setTemporary();
      $file_to_delete->save();
    }
  }

}
