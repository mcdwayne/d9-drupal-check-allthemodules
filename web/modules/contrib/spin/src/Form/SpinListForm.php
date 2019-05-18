<?php

namespace Drupal\spin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Drupal\spin\SpinStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Spin admin form.
 */
class SpinListForm extends FormBase {
  protected $redirect;

  /**
   * Constructs a form object with dependencies.
   *
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect
   *   The redirect manager.
   */
  public function __construct(RedirectDestinationInterface $redirect) {
    $this->redirect = $redirect;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('redirect.destination'));
  }

  /**
   * The spin profile admin form.
   *
   * @param array $form
   *   A drupal form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal form state object.
   *
   * @return array
   *   A Drupal form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $data = [
      '#type'    => 'table',
      '#caption' => $this->t('Slideshow Profiles'),
      '#rows'    => $this->getTableRows(SpinStorage::getList('slideshow')),
      '#header'  => [
        $this->t('Profile'),
        $this->t('Type'),
        $this->t('Delete'),
        $this->t('Edit'),
      ],
    ];
    $form['add_slideshow_link'] = [
      '#type'   => 'markup',
      '#markup' => '<p>' . $this->getAddButton('slideshow', $this->t('Add Slideshow Profile')) . '</p>',
    ];
    $form['add_spin_link'] = [
      '#type'   => 'markup',
      '#markup' => '<p>' . $this->getAddButton('spin', $this->t('Add Spin Profile')) . '</p>',
    ];
    $form['list_slideshow'] = $data;

    $data['#caption'] = $this->t('Spin Profiles');
    $data['#rows'] = $this->getTableRows(SpinStorage::getList('spin'));

    $form['list_spin'] = $data;

    return $form;
  }

  /**
   * The form ID.
   *
   * @return string
   *   The form ID.
   */
  public function getFormId() {
    return 'spin_list';
  }

  /**
   * Dumy submit function.
   *
   * @param array $form
   *   A drupal form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal form state object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Dumy validation function.
   *
   * @param array $form
   *   A drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal FormStateInterface object.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Build an add button.
   *
   * @param string $type
   *   The type of profile.
   * @param string $label
   *   The profile label.
   *
   * @return string
   *   Add button HTML.
   */
  protected function getAddButton($type, $label) {
    $args = ['op' => 'add', 'type' => $type, 'sid' => 0];
    $classes = ['button', 'button-action', 'button--primary', 'button--small'];
    $opts = ['query' => $this->redirect->getAsArray()];

    $url = Url::fromRoute('spin.admin', $args, $opts);
    $link = Link::fromTextAndUrl($label, $url)->toRenderable();
    $link['#attributes']['class'] = $classes;

    return render($link);
  }

  /**
   * Build profile table row data.
   *
   * @param array $profiles
   *   An array of profile objects.
   *
   * @return array
   *   An array of table rows.
   */
  protected function getTableRows(array $profiles) {
    $opts = ['query' => $this->redirect->getAsArray()];
    $rows = [];

    foreach ($profiles as $obj) {
      $args = ['op' => 'edit', 'type' => $obj->type, 'sid' => $obj->sid];

      $rows[] = [
        $obj->label,
        $obj->type,
        ($obj->name == 'default') ? '-' : Link::fromTextAndUrl($this->t('Delete'), Url::fromRoute('spin.delete', ['sid' => $obj->sid])),
        Link::fromTextAndUrl($this->t('Edit'), Url::fromRoute('spin.admin', $args, $opts)),
      ];
    }
    return $rows;
  }

}
