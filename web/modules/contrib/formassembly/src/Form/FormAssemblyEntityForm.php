<?php

namespace Drupal\formassembly\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\formassembly\ApiMarkup;
use Drupal\formassembly\Entity\FormAssemblyEntity;
use Gajus\Dindent\Exception\InvalidArgumentException;
use Gajus\Dindent\Exception\RuntimeException;
use Highlight\Highlighter;
use Gajus\Dindent\Indenter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for FormAssembly Form edit forms.
 *
 * @author Shawn P. Duncan <code@sd.shawnduncan.org>
 *
 * Copyright 2019 by Shawn P. Duncan.  This code is
 * released under the GNU General Public License.
 * Which means that it is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 * http://www.gnu.org/licenses/gpl.html
 * @package Drupal\formassembly
 */
class FormAssemblyEntityForm extends ContentEntityForm {

  /**
   * Formassembly markup service.
   *
   * @var \Drupal\formassembly\ApiMarkup
   */
  protected $markup;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL,
    TimeInterface $time = NULL,
    ApiMarkup $markup = NULL
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->markup = $markup;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('formassembly.markup')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    if (!$entity instanceof FormAssemblyEntity) {
      return $form;
    }
    $form = parent::buildForm($form, $form_state);
    $weight = isset($form['query_params']['#weight']) ? $form['query_params']['#weight'] + 1 : 90;
    $token_display = $this->buildTokenElement(
      $weight,
      'The entity type of any path parameter which resolves to an entity will be added to the token types at render.'
    );
    if (!empty($token_display)) {
      $form['token_link'] = $token_display;
    }
    if (
      class_exists('Highlight\Highlighter')
      && $this->markup instanceof ApiMarkup
    ) {
      $markup = $this->markup->getFormMarkup($entity);
      $highlighter = new Highlighter();
      try {
        if (class_exists('Gajus\Dindent\Indenter')) {
          $indenter = new Indenter(['indentation_character' => '  ']);
          $markup = $indenter->indent($markup);
        }
        $highlighted = $highlighter->highlight('html', $markup);
        $form['inspector'] = [
          '#type' => 'details',
          '#title' => $this
            ->t('Inspect form html'),
          '#weight' => $weight + 1,
          'form_html' => [
            '#type' => 'html_tag',
            '#tag' => 'pre',
            '#attributes' => [
              'class' => [
                'hljs',
                $highlighted->language,
              ],
            ],
            '#attached' => [
              'library' => ['formassembly/highlighter'],
            ],
            '#value' => $highlighted->value,
          ],
        ];
      }
      catch (\DomainException $e) {
        $this->getLogger('FormAssembly')->error(
          'The code highlight service failed to render the form html.'
        );
      }
      catch (InvalidArgumentException $e) {
        $this->getLogger('FormAssembly')->error(
          'The code highlight service failed to render the form html.'
        );
      }
      catch (RuntimeException $e) {
        $this->getLogger('FormAssembly')->error(
          'The code highlight service failed to render the form html.'
        );
      }
    }
    return $form;
  }

  /**
   * Helper method to build the token link render array.
   *
   * @param int $weight
   *   The element weight.
   * @param string $description
   *   An optional description.
   *
   * @return array
   *   The render array.
   */
  public function buildTokenElement(
    $weight = 90,
    $description = NULL
  ) {
    if (!$this->moduleHandler->moduleExists('token')) {
      return [];
    }
    // Not injecting the service as it may not exist.
    $token_tree = \Drupal::service('token.tree_builder')->buildAllRenderable([
      'click_insert' => FALSE,
      'show_restricted' => TRUE,
      'show_nested' => FALSE,
    ]);

    $build = [
      '#type' => 'details',
      '#title' => $this
        ->t('All available tokens'),
      '#weight' => $weight,
      'token_tree_link' => $token_tree,
    ];

    if ($description) {
      $build['description'] = [
        '#prefix' => ' ',
        '#markup' => $description,
      ];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()
          ->addMessage(
            $this->t(
              'Created the %label FormAssembly Form.',
              [
                '%label' => $entity->label(),
              ]
            )
          );
        break;

      default:
        $this->messenger()
          ->addMessage(
            $this->t(
              'Saved the %label FormAssembly Form.',
              [
                '%label' => $entity->label(),
              ]
            )
          );
    }
    $form_state->setRedirect(
      'entity.fa_form.canonical',
      ['fa_form' => $entity->id()]
    );
  }

}
