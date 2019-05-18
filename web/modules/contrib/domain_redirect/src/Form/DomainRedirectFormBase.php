<?php

/**
 * @file
 * Contains Drupal\domain_redirect\Form\DomainRedirectFormBase.
 */

namespace Drupal\domain_redirect\Form;

use Drupal\Core\Url;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for domain redirect forms.
 */
class DomainRedirectFormBase extends ContentEntityForm {

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(QueryFactory $query_factory) {
    $this->entityQueryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.query'));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Get the basic actins from the base class.
    $actions = parent::actions($form, $form_state);

    // Change the submit button text.
    $actions['submit']['#value'] = $this->t('Save');

    // Cancel link.
    $actions['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('entity.domain_redirect.list'),
    ];

    // Return the result.
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Extract the redirect.
    $redirect = $this->getEntity();

    // Check for a duplicate based on the domain.
    $query = $this->entityQueryFactory->get('domain_redirect')
      ->condition('redirect_domain', $form_state->getValue(['redirect_domain', 0]));

    // Check if the redirect has been assigned an ID.
    if ($redirect->id()) {
      // Exclude it from the search.
      $query->condition('drid', $redirect->id(), '<>');
    }

    // Execute the query and check for results.
    if ($query->execute()) {
      $form_state->setErrorByName('redirect_domain', $this->t('The domain is already in use.'));
    }

    // @todo: Validate domain has no odd characters?

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Save the entity.
    if (($return = $this->getEntity()->save()) == SAVED_UPDATED) {
      drupal_set_message($this->t('The redirect has been updated.'));
    }
    else {
      drupal_set_message($this->t('The redirect has been added.'));
    }

    // Redirect the user back to the list.
    $form_state->setRedirect('entity.domain_redirect.list');

    // @todo: Should/can we clear the page cache so the target domain
    // starts redirecting immediately?

    return $return;
  }

}
