<?php

namespace Drupal\bookkeeping\Plugin\Field\FieldFormatter;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\bookkeeping\Plugin\Field\FieldType\BookkeepingEntryItem;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'bookkeeping_entry_table' formatter.
 *
 * @FieldFormatter(
 *   id = "bookkeeping_entry_table",
 *   label = @Translation("Table"),
 *   field_types = {"bookkeeping_entry"}
 * )
 */
class BookkeepingEntryTableFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * Constructs a new BookkeepingEntryTableFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, CurrencyFormatterInterface $currency_formatter) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currencyFormatter = $currency_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('commerce_price.currency_formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $table = [
      '#type' => 'table',
      '#header' => [
        'delta' => '#',
        'account' => $this->t('Account'),
        'debit' => $this->t('Debit'),
        'credit' => $this->t('Credit'),
      ],
    ];

    foreach ($items as $delta => $item) {
      $row = [
        'delta' => $delta + 1,
        'account' => $item->entity->label(),
        'debit' => '',
        'credit' => '',
      ];

      $formatted_amount = $this->currencyFormatter
        ->format($item->amount, $item->currency_code);
      if ($item->type == BookkeepingEntryItem::TYPE_DEBIT) {
        $row['debit'] = $formatted_amount;
      }
      else {
        $row['credit'] = $formatted_amount;
      }

      $table['#rows'][$delta] = $row;
    }

    return [$table];
  }

}
