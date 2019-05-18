<?php

/**
 * @file
 * Contains \Drupal\packaging\Tests\PackagingUnitTest.
 *
 * @author Tim Rohaly.    <http://drupal.org/user/202830>
 */

namespace Drupal\packaging\Tests;

use Drupal\simpletest\KernelTestBase;

use Drupal\packaging\Context;
use Drupal\packaging\Package;
use Drupal\packaging\Product;
use Drupal\packaging\Strategy;

use Drupal\packaging\Plugin\Strategy\PackageAllInOne;
use Drupal\packaging\Plugin\Strategy\PackageAverageWeight;
use Drupal\packaging\Plugin\Strategy\PackageAverageVolume;
use Drupal\packaging\Plugin\Strategy\PackageByKey;
use Drupal\packaging\Plugin\Strategy\PackageByVolume;
use Drupal\packaging\Plugin\Strategy\PackageLastFit;
use Drupal\packaging\Plugin\Strategy\PackageNextFit;
use Drupal\packaging\Plugin\Strategy\PackageEachInOwn;
use Drupal\packaging\Plugin\Strategy\PackageOnePackage;

use Drupal\packaging_test\Plugin\Strategy\PackageCustomStrategy;


/**
 * Tests packaging strategy plugins.
 *
 * @group Packaging
 */
class PackagingUnitTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('packaging');

  /**
   * Array of Product objects for use by the packaging tests.
   *
   * @var array
   */
  protected $products = array();


  /**
   * Overrides KernelTestBase::setUp().
   */
  function setUp() {
    parent::setUp();

    // Create test products for packaging.
    $this->products = $this->createTestProducts();
    //debug($this->products);
  }

  /**
   * Test "All-in-One First fit" packaging, where all products are put into
   * one package subject only to package maximum weight.  If maximum weight
   * is exceeded, a new package is created.
   */
  function testPackageAllInOne() {
    $this->pass(t('Testing PackageAllInOne strategy'));

    $context = new Context();
    $context->setMaximumPackageWeight(15)
            ->setDefaultWeightUnits('LB')
            ->setWeightMarkupFunction('packaging_weight_markup')
            ->setStrategy(new PackageAllInOne());

    $packages = $context->packageProducts($this->products);

    //debug($packages);

    // Product aggregates.
    $product_aggregate = $this->getProductAggregate($context, $this->products);

    // Package aggregates.
    $package_aggregate = $this->getPackageAggregate($context, $packages);

    // Test to see if the strategy produced the expected total weight.
    $this->assertTrue(
      abs((float) $package_aggregate->getWeight() - (float) $product_aggregate->getWeight()) < 0.0001,
      t('Total weight of packages equals total weight of products.')
    );

    // Test to see if the strategy produced the expected weight markup.
    $this->assertTrue(
      abs((float) $package_aggregate->getShipWeight() - (float) packaging_weight_markup((float) $product_aggregate->getWeight())) < 0.0001,
      t('Ship weight of packages equals total weight of products with markup.')
    );

    // Actual number of boxes.
    $actual_number_of_packages = count($packages);

    // Calculate minimum possible number of boxes.
    $minimum_number_of_packages = ceil($product_aggregate->getWeight() / $context->getMaximumPackageWeight());

    // Test to see if the strategy produced the expected number of boxes.
    $this->assertTrue(
      ($actual_number_of_packages >= $minimum_number_of_packages),
      t('Number of packages made %made is greater than or equal to minimum possible number %min.', array('%made' => $actual_number_of_packages, '%min' => $minimum_number_of_packages))
    );

    // Calculate maximum possible number of boxes.
    $maximum_number_of_packages = 0;
    foreach ($this->products as $product) {
      $maximum_number_of_packages += $product->getQuantity();
    }

    // Test to see if the strategy produced the expected number of boxes.
    $this->assertTrue(
      ($actual_number_of_packages <= $maximum_number_of_packages),
      t('Number of packages made %made is less than or equal to maximum possible number %max.', array('%made' => $actual_number_of_packages, '%max' => $maximum_number_of_packages))
    );

    // Test different origin addresses

    // Test different destination addresses

    // Test effect of maximum package weight

    // Test effect of package quantity

    // Test effect of weight markup
  }

  /**
   * Test "All-in-One Last fit" packaging, where all products are put into
   * one package subject only to package maximum weight.  If maximum weight
   * is exceeded, a new package is created.
   */
  function testPackageLastFit() {
    $this->pass(t('Testing PackageLastFit strategy'));

    $context = new Context();
    $context->setMaximumPackageWeight(15);
    $context->setDefaultWeightUnits('LB');
    $context->setWeightMarkupFunction('packaging_weight_markup');
    $context->setStrategy(new PackageLastFit());

    $packages = $context->packageProducts($this->products);

    //debug($packages);

    // Product aggregates.
    $product_aggregate = $this->getProductAggregate($context, $this->products);

    // Package aggregates.
    $package_aggregate = $this->getPackageAggregate($context, $packages);

    // Test to see if the strategy produced the expected total weight.
    $this->assertTrue(
      abs((float) $package_aggregate->getWeight() - (float) $product_aggregate->getWeight()) < 0.0001,
      t('Total weight of packages equals total weight of products.')
    );

    // Test to see if the strategy produced the expected weight markup.
    $this->assertTrue(
      abs((float) $package_aggregate->getShipWeight() - (float) packaging_weight_markup((float) $product_aggregate->getWeight())) < 0.0001,
      t('Ship weight of packages equals total weight of products with markup.')
    );

    // Actual number of boxes.
    $actual_number_of_packages = count($packages);

    // Calculate minimum possible number of boxes.
    $minimum_number_of_packages = ceil($product_aggregate->getWeight() / $context->getMaximumPackageWeight());

    // Test to see if the strategy produced the expected number of boxes.
    $this->assertTrue(
      ($actual_number_of_packages >= $minimum_number_of_packages),
      t('Number of packages made %made is greater than or equal to minimum possible number %min.', array('%made' => $actual_number_of_packages, '%min' => $minimum_number_of_packages))
    );

    // Calculate maximum possible number of boxes.
    $maximum_number_of_packages = 0;
    foreach ($this->products as $product) {
      $maximum_number_of_packages += $product->getQuantity();
    }

    // Test to see if the strategy produced the expected number of boxes.
    $this->assertTrue(
      ($actual_number_of_packages <= $maximum_number_of_packages),
      t('Number of packages made %made is less than or equal to maximum possible number %max.', array('%made' => $actual_number_of_packages, '%max' => $maximum_number_of_packages))
    );

    // Test different origin addresses

    // Test different destination addresses

    // Test effect of maximum package weight

    // Test effect of package quantity

    // Test effect of weight markup
  }

  /**
   * Test "All-in-One Next fit" packaging, where all products are put into
   * one package subject only to package maximum weight.  If maximum weight
   * is exceeded, a new package is created.
   */
  function testPackageNextFit() {
    $this->pass(t('Testing PackageNextFit strategy'));

    $context = new Context();
    $context->setMaximumPackageWeight(15);
    $context->setDefaultWeightUnits('LB');
    $context->setWeightMarkupFunction('packaging_weight_markup');
    $context->setStrategy(new PackageNextFit());

    $packages = $context->packageProducts($this->products);

    //debug($packages);

    // Product aggregates.
    $product_aggregate = $this->getProductAggregate($context, $this->products);

    // Package aggregates.
    $package_aggregate = $this->getPackageAggregate($context, $packages);

    // Test to see if the strategy produced the expected total weight.
    $this->assertTrue(
      abs((float) $package_aggregate->getWeight() - (float) $product_aggregate->getWeight()) < 0.0001,
      t('Total weight of packages equals total weight of products.')
    );

    // Test to see if the strategy produced the expected weight markup.
    $this->assertTrue(
      abs((float) $package_aggregate->getShipWeight() - (float) packaging_weight_markup((float) $product_aggregate->getWeight())) < 0.0001,
      t('Ship weight of packages equals total weight of products with markup.')
    );

    // Actual number of boxes.
    $actual_number_of_packages = count($packages);

    // Calculate minimum possible number of boxes.
    $minimum_number_of_packages = ceil($product_aggregate->getWeight() / $context->getMaximumPackageWeight());

    // Test to see if the strategy produced the expected number of boxes.
    $this->assertTrue(
      ($actual_number_of_packages >= $minimum_number_of_packages),
      t('Number of packages made %made is greater than or equal to minimum possible number %min.', array('%made' => $actual_number_of_packages, '%min' => $minimum_number_of_packages))
    );

    // Calculate maximum possible number of boxes.
    $maximum_number_of_packages = 0;
    foreach ($this->products as $product) {
      $maximum_number_of_packages += $product->getQuantity();
    }

    // Test to see if the strategy produced the expected number of boxes.
    $this->assertTrue(
      ($actual_number_of_packages <= $maximum_number_of_packages),
      t('Number of packages made %made is less than or equal to maximum possible number %max.', array('%made' => $actual_number_of_packages, '%max' => $maximum_number_of_packages))
    );

    // Test different origin addresses

    // Test different destination addresses

    // Test effect of maximum package weight

    // Test effect of package quantity

    // Test effect of weight markup
  }

  /**
   * Test Each in Own packaging.
   */
  function testPackageEachInOwn() {
    $this->pass(t('Testing PackageEachInOwn strategy'));

    $context = new Context();
    $context->setMaximumPackageWeight(15);
    $context->setDefaultWeightUnits('LB');
    $context->setWeightMarkupFunction('packaging_weight_markup');
    $context->setStrategy(new PackageEachInOwn());

    $packages = $context->packageProducts($this->products);

    //debug($packages);

    // Product aggregates.
    $product_aggregate = $this->getProductAggregate($context, $this->products);

    // Package aggregates.
    $package_aggregate = $this->getPackageAggregate($context, $packages);

    // Test to see if the strategy produced the expected total weight.
    $this->assertTrue(
      abs((float) $package_aggregate->getWeight() - (float) $product_aggregate->getWeight()) < 0.0001,
      t('Total weight of packages equals total weight of products.')
    );

    // Test to see if the strategy produced the expected weight markup.
    $this->assertTrue(
      abs((float) $package_aggregate->getShipWeight() - (float) packaging_weight_markup((float) $product_aggregate->getWeight())) < 0.0001,
      t('Ship weight of packages equals total weight of products with markup.')
    );

    // Actual number of boxes.
    $actual_number_of_packages = count($packages);

    // Calculate expected number of boxes.
    $expected_number_of_packages = 0;
    foreach ($this->products as $product) {
      $expected_number_of_packages += ceil($product->getQuantity() / $product->getPackageQuantity());
    }

    // Test to see if the strategy produced the expected number of boxes.
    $this->assertTrue(
      ($actual_number_of_packages == $expected_number_of_packages),
      t('Number of packages equals expectation.')
    );
  }

  /**
   * Test One package strategy.
   */
  function testPackageOnePackage() {
    $this->pass(t('Testing PackageOnePackage strategy'));

    $context = new Context();
    $context->setMaximumPackageWeight(15);
    $context->setDefaultWeightUnits('LB');
    $context->setWeightMarkupFunction('packaging_weight_markup');
    $context->setStrategy(new PackageOnePackage());

    $packages = $context->packageProducts($this->products);

    //debug($packages);

    // Product aggregates.
    $product_aggregate = $this->getProductAggregate($context, $this->products);

    // Package aggregates.
    $package_aggregate = $this->getPackageAggregate($context, $packages);

    // Test to see if the strategy produced the expected total weight.
    $this->assertTrue(
      abs($package_aggregate->getWeight() - $product_aggregate->getWeight()) < 0.0001,
      t('Total weight of packages equals total weight of products.')
    );

    // Test to see if the strategy produced the expected weight markup.
    $this->assertTrue(
      abs($package_aggregate->getShipWeight() - packaging_weight_markup($product_aggregate->getWeight())) < 0.001,
      t('Ship weight of packages equals total weight of products with markup.')
    );

    // Actual number of boxes.
    $actual_number_of_packages = count($packages);

    // Calculate expected number of boxes.
    $expected_number_of_packages = 1;

    // Test to see if the strategy produced the expected number of boxes.
    $this->assertTrue(
      ($actual_number_of_packages == $expected_number_of_packages),
      t('Number of packages equals expectation.')
    );
  }

  /**
   * Test "By volume" packaging, where all products are put into one package
   * subject only to package maximum volume.  If maximum volume is exceeded,
   * a new package is created.
   */
  function testPackageByVolume() {
    $this->pass(t('Testing PackageByVolume strategy'));

    $context = new Context();
    $context->setMaximumPackageVolume(50);
    $context->setDefaultLengthUnits('IN');
    $context->setDefaultWeightUnits('LB');
    $context->setWeightMarkupFunction('packaging_weight_markup');
    $context->setStrategy(new PackageByVolume());

    $packages = $context->packageProducts($this->products);

    //debug($packages);

    // Product aggregates.
    $product_aggregate = $this->getProductAggregate($context, $this->products);

    // Package aggregates.
    $package_aggregate = $this->getPackageAggregate($context, $packages);

    // Test to see if the strategy produced the expected total weight.
    $this->assertTrue(
      abs((float) $package_aggregate->getWeight() - (float) $product_aggregate->getWeight()) < 0.0001,
      t('Total weight of packages equals total weight of products.')
    );

    // Test to see if the strategy produced the expected weight markup.
    $this->assertTrue(
      abs((float) $package_aggregate->getShipWeight() - (float) packaging_weight_markup((float) $product_aggregate->getWeight())) < 0.0001,
      t('Ship weight of packages equals total weight of products with markup.')
    );

    // Actual number of boxes.
    $actual_number_of_packages = count($packages);

    // Calculate minimum possible number of boxes.
    $minimum_number_of_packages = ceil($product_aggregate->getVolume() / $context->getMaximumPackageVolume());

    // Test to see if the strategy produced the expected number of boxes.
    $this->assertTrue(
      ($actual_number_of_packages >= $minimum_number_of_packages),
      t('Number of packages made %made is greater than or equal to minimum possible number %min.', array('%made' => $actual_number_of_packages, '%min' => $minimum_number_of_packages))
    );

    // Calculate maximum possible number of boxes.
    $maximum_number_of_packages = 0;
    foreach ($this->products as $product) {
      $maximum_number_of_packages += $product->getQuantity();
    }

    // Test to see if the strategy produced the expected number of boxes.
    $this->assertTrue(
      ($actual_number_of_packages <= $maximum_number_of_packages),
      t('Number of packages made %made is less than or equal to maximum possible number %max.', array('%made' => $actual_number_of_packages, '%max' => $maximum_number_of_packages))
    );

    // Test different origin addresses

    // Test different destination addresses

    // Test effect of maximum package weight

    // Test effect of package quantity

    // Test effect of weight markup
  }

  /**
   * Test package by average weight strategy.
   */
  function testPackageAverageWeight() {
    $this->pass(t('Testing PackageAverageWeight strategy'));

    $context = new Context();
    $context->setMaximumPackageWeight(15);
    $context->setDefaultWeightUnits('LB');
    $context->setWeightMarkupFunction('packaging_weight_markup');
    $context->setStrategy(new PackageAverageWeight());

    $packages = $context->packageProducts($this->products);

    // Product aggregates.
    $product_aggregate = $this->getProductAggregate($context, $this->products);

    // Package aggregates.
    $package_aggregate = $this->getPackageAggregate($context, $packages);

    // Test to see if the strategy produced the expected total weight.
    $this->assertTrue(
      abs($package_aggregate->getWeight() - $product_aggregate->getWeight()) < 0.0001,
      t('Total weight of packages equals total weight of products.')
    );

    // Test to see if the strategy produced the expected weight markup.
    $this->assertTrue(
      abs($package_aggregate->getShipWeight() - packaging_weight_markup($product_aggregate->getWeight())) < 0.0001,
      t('Ship weight of packages equals total weight of products with markup.')
    );

    // Actual number of boxes.
    $actual_number_of_packages = count($packages);

    // Calculate expected number of boxes.
    $expected_number_of_packages = ceil($product_aggregate->getWeight() / $context->getMaximumPackageWeight());

    // Test to see if the strategy produced the expected number of boxes.
    $this->assertTrue(
      ($actual_number_of_packages == $expected_number_of_packages),
      t('Number of packages equals expectation.')
    );
  }

  /**
   * Test package by average volume strategy.
   */
  function testPackageAverageVolume() {
    $this->pass(t('Testing PackageAverageVolume strategy'));

    $context = new Context();
    $context->setMaximumPackageVolume(50);
    $context->setDefaultLengthUnits('IN');
    $context->setDefaultWeightUnits('LB');
    $context->setWeightMarkupFunction('packaging_weight_markup');
    $context->setStrategy(new PackageAverageVolume());

    $packages = $context->packageProducts($this->products);

    // Product aggregates.
    $product_aggregate = $this->getProductAggregate($context, $this->products);

    // Package aggregates.
    $package_aggregate = $this->getPackageAggregate($context, $packages);

    // Test to see if the strategy produced the expected total weight.
    $this->assertTrue(
      abs($package_aggregate->getWeight() - $product_aggregate->getWeight()) < 0.0001,
      t('Total weight of packages equals total weight of products.')
    );

    // Test to see if the strategy produced the expected weight markup.
    $this->assertTrue(
      abs($package_aggregate->getShipWeight() - packaging_weight_markup($product_aggregate->getWeight())) < 0.0001,
      t('Ship weight of packages equals total weight of products with markup.')
    );

    // Actual number of boxes.
    $actual_number_of_packages = count($packages);

    // Calculate expected number of boxes.
    $expected_number_of_packages = ceil($product_aggregate->getVolume() / $context->getMaximumPackageVolume());

    // Test to see if the strategy produced the expected number of boxes.
    $this->assertTrue(
      ($actual_number_of_packages == $expected_number_of_packages),
      t('Number of packages equals expectation.')
    );

  }

  /**
   * Test package by key strategy.
   */
  function testPackageByKey() {
    $this->pass(t('Testing PackageByKey strategy'));

    // We need test products with special keys to test this strategy.
    $keyarray = array('from' => "Sammamish, WA", 'to' => "Baltimore, MD");

    // Designate keys BEFORE we create Product objects.
    Context::designateKeys(array_keys($keyarray));

    $this->products = $this->createTestProducts($keyarray);

    //debug($this->products);

    $context = new Context();
    $context->setMaximumPackageWeight(15);
    $context->setDefaultWeightUnits('LB');
    $context->setWeightMarkupFunction('packaging_weight_markup');
    $context->setStrategy(new PackageByKey());

    $packages = $context->packageProducts($this->products);

    // Product aggregates.
    $product_aggregate = $this->getProductAggregate($context, $this->products);
    //debug($product_aggregate);

    // Package aggregates.
    $package_aggregate = $this->getPackageAggregate($context, $packages);
    //debug($package_aggregate);

    // Test to see if the strategy produced the expected total weight.
    $this->assertTrue(
      abs($package_aggregate->getWeight() - $product_aggregate->getWeight()) < 0.0001,
      t('Total weight of packages equals total weight of products.')
    );

    // Test to see if the strategy produced the expected weight markup.
    $this->assertTrue(
      abs($package_aggregate->getShipWeight() - packaging_weight_markup($product_aggregate->getWeight())) < 0.0001,
      t('Ship weight of packages equals total weight of products with markup.')
    );

    // Actual number of boxes.
    $actual_number_of_packages = count($packages);

    // Calculate expected number of boxes.

    // This is the same algorithm used in PackageByKey, so it's not a surprise
    // it will calculate the same number of packages. It would be nice to have
    // an independent way to calculate the number of packages expected, because
    // that would be a more meaningful test.

    $keynames = Context::getKeys();

    $hashtable = array();
    foreach ($this->products as $index => $product) {
      $hash = '';
      foreach ($keynames as $key) {
        $hash .= $product->$key;
      }
      $hashtable[$hash][] = $index;
    }
    $expected_number_of_packages = count($hashtable);


    // Test to see if the strategy produced the expected number of boxes.
    $this->assertTrue(
      ($actual_number_of_packages == $expected_number_of_packages),
      t('Number of packages equals expectation.')
    );
  }

  /**
   * Test custom strategy.
   */
  function testPackageCustomStrategy() {
    $this->pass(t('Testing PackageCustomStrategy strategy'));

    // Test plugin system to see that any custom plugin gets picked up.
    // PackageCustomStrategy is defined by the packaging_test module.
    //$strategy = packaging_get_instance('custom');

    $context = new Context();
    $context->setMaximumPackageWeight(15);
    $context->setDefaultWeightUnits('LB');
    $context->setWeightMarkupFunction('packaging_weight_markup');
    //$context->setStrategy($strategy);
    $context->setStrategy(new PackageCustomStrategy());

    $packages = $context->packageProducts($this->products);


    // Product aggregates.
    $product_aggregate = $this->getProductAggregate($context, $this->products);

    // Package aggregates.
    $package_aggregate = $this->getPackageAggregate($context, $packages);

    // Test to see if the strategy produced the expected total weight.
    $this->assertTrue(
      abs($package_aggregate->getWeight() - $product_aggregate->getWeight()) < 0.0001,
      t('Total weight of packages equals total weight of products.')
    );

    // Test to see if the strategy produced the expected weight markup.
    $this->assertTrue(
      abs($package_aggregate->getShipWeight() - packaging_weight_markup($product_aggregate->getWeight())) < 0.001,
      t('Ship weight of packages equals total weight of products with markup.')
    );

    // Actual number of boxes.
    $actual_number_of_packages = count($packages);

    // Calculate expected number of boxes.
    $expected_number_of_packages = 23;

    // Test to see if the strategy produced the expected number of boxes.
    $this->assertTrue(
      ($actual_number_of_packages == $expected_number_of_packages),
      t('Number of packages equals expectation.')
    );

  }


  /****************************************************************************
   * Utility Functions                                                        *
   ****************************************************************************/


  /**
   * Returns a Product object containing aggregate information.
   */
  function getProductAggregate(Context $context, array $products) {
    $product_aggregate = new Product();
    $product_aggregate->setWeightUnits($context->getDefaultWeightUnits());
    $product_aggregate->setLengthUnits($context->getDefaultLengthUnits());
    foreach ($this->products as $product) {
      $product_aggregate->setWeight($product_aggregate->getWeight() + $product->getQuantity() * $product->getWeight() * packaging_weight_conversion($product->getWeightUnits(), $context->getDefaultWeightUnits()));
      $product_aggregate->setVolume($product_aggregate->getVolume() + $product->getQuantity() * $product->getVolume() * packaging_length_conversion($product->getLengthUnits(), $context->getDefaultLengthUnits()));
      $product_aggregate->setQuantity($product_aggregate->getQuantity() + $product->getQuantity());
    }
    $this->pass(t('Total weight of products = %total %units.', array('%total' => $product_aggregate->getWeight(), '%units' => $context->getDefaultWeightUnits())));

    return $product_aggregate;
  }

  /**
   * Returns a Package object containing aggregate information.
   */
  function getPackageAggregate(Context $context, array $packages) {
    $package_aggregate = new Package();
    $package_aggregate->setWeightUnits($context->getDefaultWeightUnits());
    $package_aggregate->setLengthUnits($context->getDefaultLengthUnits());
    foreach ($packages as $package) {
      $package_aggregate->setWeight($package_aggregate->getWeight() + $package->getWeight());
      $package_aggregate->setShipWeight($package_aggregate->getShipWeight() + $package->getShipWeight());
      $package_aggregate->setVolume($package_aggregate->getVolume() + $package->getVolume());
      $package_aggregate->setQuantity($package_aggregate->getQuantity() + $package->getQuantity());
    }
    $this->pass(t('Total package weight = %total %units.', array('%total' => $package_aggregate->getWeight(), '%units' => $context->getDefaultWeightUnits())));
    $this->pass(t('Total package weight after weight markup = %total %units.', array('%total' => $package_aggregate->getShipWeight(), '%units' => $context->getDefaultWeightUnits())));
    $this->pass(t('Total ship weight of packages = %total %units.', array('%total' => $package_aggregate->getShipWeight(), '%units' => $context->getDefaultWeightUnits())));

    return $package_aggregate;
  }

  /**
   * Creates a new product.
   *
   * @param $keyarray
   *   Array containing $key => $value pairs to add as product properties.
   */
  function createTestProducts(array $keyarray = array()) {
    $products = array();
    $number = mt_rand(5, 10);
    for ($i = 0; $i < $number; $i++) {
      $products[] = Product::constructFromUbercartProduct($this->createProduct($keyarray));
    }
    return $products;
  }

  /**
   * Creates a new product.
   */
  function createProduct($product = array()) {
    // Set the default required fields.
    $weight_units = array('lb', 'kg', 'oz', 'g');
    // Feet are too big!  Need to re-do this so volumes aren't
    // ridiculous if ft are chosen. Until then, comment it out.
    //$length_units = array('in', 'ft', 'cm', 'mm');
    $length_units = array('in', 'cm', 'mm');
    $product += array(
      'type' => 'product',
      'model' => $this->randomString(8),
      'list_price' => mt_rand(1, 9999),
      'cost' => mt_rand(1, 9999),
      'sell_price' => mt_rand(1, 9999),
      'weight' => mt_rand(1, 5),
      'weight_units' => array_rand(array_flip($weight_units)),
      'length' => mt_rand(1, 4),
      'width' => mt_rand(1, 4),
      'height' => mt_rand(1, 4),
      'length_units' => array_rand(array_flip($length_units)),
      'pkg_qty' => mt_rand(1, 10),
      'default_qty' => 1,
      'qty' => mt_rand(1, 5),
      'ordering' => mt_rand(-25, 25),
      'shippable' => TRUE,
    );

    return (object) $product;
  }
}
