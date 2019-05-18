<?php

namespace Drupal\Tests\commerce_pado\Functional;

use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\Tests\commerce_cart\Functional\CartBrowserTestBase;

/**
 * Functional test of the formatter and form.
 *
 * @group commerce_pado
 */
class ProductAddonTest extends CartBrowserTestBase {

  use EntityReferenceTestTrait;

  /**
   * The order storage.
   *
   * @var \Drupal\commerce_order\OrderStorage
   */
  protected $orderStorage;

  /**
   * @var \Drupal\commerce\CommerceContentEntityStorage
   */
  protected $productStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_product',
    'commerce_order',
    'commerce_pado',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $product = $this->variation->getProduct();
    $this->createEntityReferenceField(
      $product->getEntityTypeId(),
      $product->bundle(),
      'pado',
      'Addons',
      $product->getEntityTypeId(),
      'default',
      [],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);


    // Use the add-on formatter.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
    $view_display = commerce_get_entity_display($product->getEntityTypeId(), $product->bundle(), 'view');
    $view_display->setComponent('variations', [
      'type' => 'commerce_pado_add_to_cart',
      'label' => 'hidden',
      'settings' => [
        'add_on_field' => 'pado',
        'multiple' => 0,
      ],
    ]);
    $view_display->save();

    $this->orderStorage = $this->container->get('entity_type.manager')->getStorage('commerce_order');
    $this->productStorage = $this->container->get('entity_type.manager')->getStorage('commerce_product');

  }

  public function testAddonOneOption() {
    $add_on_product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'Membership',
      'stores' => [$this->store],
      'body' => ['value' => 'This is a membership addon'],
      'variations' => [
        $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'sku' => 'MEMBERSHIP',
          'price' => [
            'number' => '10.99',
            'currency_code' => 'USD',
          ],
        ]),
      ],
    ]);

    $this->productStorage->resetCache([$this->variation->getProductId()]);
    $product = $this->productStorage->load($this->variation->getProductId());
    $product->get('pado')->appendItem($add_on_product->id());
    $product->save();

    $this->drupalGet($product->toUrl());
    $this->assertSession()->pageTextContains(new FormattableMarkup('Add @product', ['@product' => $add_on_product->label()]));
    $this->assertSession()->checkboxNotChecked(new FormattableMarkup('@product', ['@product' => $add_on_product->label()]));
    $this->submitForm([], 'Add to cart');

    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(1, $this->cart->getItems());
    $this->cartManager->emptyCart($this->cart);

    $this->drupalGet($product->toUrl());
    $this->getSession()->getPage()->checkField(new FormattableMarkup('@product', ['@product' => $add_on_product->label()]));
    $this->submitForm([], 'Add to cart');

    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(2, $this->cart->getItems());
  }

  public function testAddonMultipleVariationOptions() {
    $variation_type = ProductVariationType::load($this->variation->bundle());
    $variation_type->setGenerateTitle(FALSE);
    $variation_type->save();

    $add_on_product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'Membership',
      'stores' => [$this->store],
      'body' => ['value' => 'This is a membership addon'],
      'variations' => [
        $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'title' => 'Tier 1',
          'sku' => 'MEMBERSHIP',
          'price' => [
            'number' => '10.99',
            'currency_code' => 'USD',
          ],
        ]),
        $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'title' => 'Tier 2',
          'sku' => 'MEMBERSHIP',
          'price' => [
            'number' => '14.99',
            'currency_code' => 'USD',
          ],
        ]),
        $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'title' => 'Tier 3',
          'sku' => 'MEMBERSHIP',
          'price' => [
            'number' => '19.99',
            'currency_code' => 'USD',
          ],
        ]),
      ],
    ]);

    $this->productStorage->resetCache([$this->variation->getProductId()]);
    $product = $this->productStorage->load($this->variation->getProductId());
    $product->get('pado')->appendItem($add_on_product->id());
    $product->save();

    $this->drupalGet($product->toUrl());
    $this->assertSession()->selectExists(new FormattableMarkup('Add @product', ['@product' => $add_on_product->label()]));
    $this->submitForm([], 'Add to cart');

    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(1, $this->cart->getItems());
    $this->cartManager->emptyCart($this->cart);

    $this->drupalGet($product->toUrl());
    $this->getSession()->getPage()->selectFieldOption(new FormattableMarkup('Add @product', ['@product' => $add_on_product->label()]), 'Tier 3');
    $this->submitForm([], 'Add to cart');

    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(2, $this->cart->getItems());
  }

  public function testAddonMultipleVariationOptionsAllowMultiple() {
    $variation_type = ProductVariationType::load($this->variation->bundle());
    $variation_type->setGenerateTitle(FALSE);
    $variation_type->save();

    $this->productStorage->resetCache([$this->variation->getProductId()]);
    $product = $this->productStorage->load($this->variation->getProductId());

    // Use the add-on formatter.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
    $view_display = commerce_get_entity_display($product->getEntityTypeId(), $product->bundle(), 'view');
    $view_display->setComponent('variations', [
      'type' => 'commerce_pado_add_to_cart',
      'label' => 'hidden',
      'settings' => [
        'add_on_field' => 'pado',
        'multiple' => 1,
      ],
    ]);
    $view_display->save();

    $add_on_product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'Membership',
      'stores' => [$this->store],
      'body' => ['value' => 'This is a membership addon'],
      'variations' => [
        $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'title' => 'Tier 1',
          'sku' => 'MEMBERSHIP',
          'price' => [
            'number' => '10.99',
            'currency_code' => 'USD',
          ],
        ]),
        $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'title' => 'Tier 2',
          'sku' => 'MEMBERSHIP',
          'price' => [
            'number' => '14.99',
            'currency_code' => 'USD',
          ],
        ]),
        $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'title' => 'Tier 3',
          'sku' => 'MEMBERSHIP',
          'price' => [
            'number' => '19.99',
            'currency_code' => 'USD',
          ],
        ]),
      ],
    ]);

    $product->get('pado')->appendItem($add_on_product->id());
    $product->save();

    $this->drupalGet($product->toUrl());
    $this->assertSession()->pageTextContains(new FormattableMarkup('Add @product', ['@product' => $add_on_product->label()]));
    $this->assertSession()->checkboxNotChecked('Tier 1');
    $this->assertSession()->checkboxNotChecked('Tier 2');
    $this->assertSession()->checkboxNotChecked('Tier 3');
    $this->submitForm([], 'Add to cart');

    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(1, $this->cart->getItems());
    $this->cartManager->emptyCart($this->cart);

    $this->drupalGet($product->toUrl());
    $this->getSession()->getPage()->checkField('Tier 2');
    $this->getSession()->getPage()->checkField('Tier 3');
    $this->submitForm([], 'Add to cart');

    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(3, $this->cart->getItems());
  }

  public function testMultipleAddonProducts() {
    $variation_type = ProductVariationType::load($this->variation->bundle());
    $variation_type->setGenerateTitle(FALSE);
    $variation_type->save();

    $add_on_product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'Membership',
      'stores' => [$this->store],
      'body' => ['value' => 'This is a membership addon'],
      'variations' => [
        $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'title' => 'Tier 1',
          'sku' => 'MEMBERSHIP',
          'price' => [
            'number' => '10.99',
            'currency_code' => 'USD',
          ],
        ]),
        $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'title' => 'Tier 2',
          'sku' => 'MEMBERSHIP',
          'price' => [
            'number' => '14.99',
            'currency_code' => 'USD',
          ],
        ]),
        $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'title' => 'Tier 3',
          'sku' => 'MEMBERSHIP',
          'price' => [
            'number' => '19.99',
            'currency_code' => 'USD',
          ],
        ]),
      ],
    ]);

    $add_on_product_2 = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'USB Stick',
      'stores' => [$this->store],
      'variations' => [
        $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'sku' => 'MEMBERSHIP-USB',
          'title' => 'USB Stick',
          'price' => [
            'number' => '3.99',
            'currency_code' => 'USD',
          ],
        ]),
      ],
    ]);

    $this->productStorage->resetCache([$this->variation->getProductId()]);
    /** @var \Drupal\commerce_product\Entity\Product $product */
    $product = $this->productStorage->load($this->variation->getProductId());
    $product->get('pado')->setValue([
      $add_on_product->id(),
      $add_on_product_2->id()
    ]);
    $product->save();

    $this->drupalGet($product->toUrl());
    $this->assertSession()->selectExists(new FormattableMarkup('Add @product', ['@product' => $add_on_product->label()]));
    $this->assertSession()->pageTextContains(new FormattableMarkup('Add @product', ['@product' => $add_on_product_2->label()]));

    $this->getSession()->getPage()->selectFieldOption(new FormattableMarkup('Add @product', ['@product' => $add_on_product->label()]), 'Tier 2');
    $this->getSession()->getPage()->checkField('USB Stick');

    $this->submitForm([], 'Add to cart');

    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(3, $this->cart->getItems());
  }


}
