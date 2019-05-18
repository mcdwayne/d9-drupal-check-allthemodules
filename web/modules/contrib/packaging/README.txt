About
=====
This module provides heuristic algorithms for splitting shippable orders into
physical packages. The intent is that shipping quotes modules will use these
algorithms in lieu of coding their own packaging routines. New algorithms may
easily be provided using the plugin architecture of this module.


Motivation
==========
Packaging has until now has been implemented independently by each and every
different shipping quote module.

Abstracting packaging out into its own module accomplishes six things:

1) Code duplication is eliminated. No longer will USPS, UPS, FedEX,
   Canada Post, etc. all have their own slightly-different methods of
   packaging orders.
2) Packaging is done uniformly between different quote methods. "All-in-one"
   will now package an order exactly the same way for USPS as it does for UPS.
3) Quote methods that don't currently use packaging, such as uc_flatrate, now
   have the option to use it. So uc_flatrate may now charge a flatrate per
   package, if for example the order ships to more than one destination or
   originates from more than one warehouse.
4) Automated test rountines are integrated. This is difficult to do in a
   shopping cart module when packaging is part of the shipping quotes because
   shipping quotes modules may use external web services which are not
   available to the testbot. As a standalone module, Packaging implements unit
   tests for algorithms used for every shipping module.
5) Packaging strategies are extensible using a plugin mechanism. If the
   built-in packaging strategies don't suit you, it's easy to write your own
   strategy and share your strategy with others.
6) Architecture enables development of new features. Packaging strategies can
   be computationally difficult: by abstracting out the packaging strategy this
   module provides a simple mechanism to develop and test new features.


Default strategies
==================

All default strategies are in the namespace Drupal\packaging\Plugin\Strategy.

PackageAllInOne.php
PackageLastFit.php
PackageNextFit.php
  Roughly correspond to the Ubercart "all-in-one" packaging method. Code was
  taken from uc_fedex. Unifies the two different implementations provided by
  uc_ups and uc_usps and fixes many bugs with both implementations.
  Specifically, this method properly handles varying product weight units,
  makes better use of available weight, allows configuration of maximum allowed
  weight. The results are independent of the order of products in the cart.

PackageEachInOwn.php
  Corresponds to the Ubercart "each-in-own" packaging method, for backwards
  compatibility.

PackageOnePackage.php
  Puts all products into one package. For backwards compatibility with shipping
  quote methods that didn't do packaging, like uc_weightquote and uc_flatrate.

PackageAverageWeight.php
  Number of packages is determined by order total weight divided by package
  maximum weight.

PackageByVolume.php
  Same as the all-in-one strategy, but using volume rather than weight as the
  way to break an order into packages.

PackageAverageVolume.php
  Number of packages is determined by order total volume divided by package
  maximum volume.

PackageByKey.php
  Products with common "keys" are all put into the same package. Keys are any
  user-defined property attached to the Product object. One example would be to
  attach taxonomy terms to the products and designate the term as a key. Then
  all products with the same set of terms would be put into the same package.

ALL the default strategies work with multiple origin addresses and multiple
destination addresses. ALL the default strategies may be applied to the entire
order or to just a subset of the order's product, allowing you to "mix and
match" packaging strategies on a per-product basis. This is useful when you
have certain products that must ship separately, must be drop-shipped, or
must be shipped in case lots.


Defining your own strategy
==========================
An example of a module that creates a new packaging strategy,
packaging_test.module, may be found in the tests subdirectory. Use this
working example as a guide when writing your own module.

Creating your own Strategy plugin is very similar to creating a Block in
Drupal 8. Should you encounter difficulties, reading any of the documentation
on how to create Blocks should be very helpful.

Here are the minimum necessary steps:
1) Create a new module for your strategy.
2) Create a new class file for your strategy and put it in the subdirectory
   src/Plugin/Strategy under your module's base directory.
   This class must implement \Drupal\lib\packaging\Strategy.
3) Add a @Strategy annotation to allow your class to be discovered. An
   annotation is a comment in the doxygen comment block for your class. The
   @Strategy annotation for the hypothetical class "MyStrategy" in the module
   "mymodule" would look something like this:
   /**
    * @Strategy(
    *   id = 'mymodule_mystrategy',
    *   admin_label = @Translation("My own Strategy class", context = "Packaging")
    * )
    */
    class MyStrategy implements Strategy {
      ...
    }
3) Implement the Strategy::packageProducts() and Strategy::getDescription()
   methods in your class.
4) Modules that uses strategies (e.g. Ubercart or Commerce) will now be able
   to use your strategy in the same way as built-in strategies.
5) I strongly suggest that if you implement your own strategy you also create
   your own test cases, using as examples the test cases for the built-in
   packaging methods. Test cases will ensure that your strategy does what you
   think it should do.

Again, the packaging_test module is simple, working example which demonstrates
how do this. The packaging module itself comes with 9 built-in strategies
that also serve as examples.


Troubleshooting
===============
Extensive tests are provided for each of the default strategies to ensure that
they work under all possible combinations of settings. If you believe you have
found a bug, the first step is to examine the test cases and make sure your
code operates the same way as the test cases. Bug reports that provide tests
to demonstrate the reported error will be given first priority in the issue
queue.
