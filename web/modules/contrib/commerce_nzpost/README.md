Drupal Commerce
===============

Commerce NZ Post provides NZ Post API results for your
Drupal Commerce 2.x e-commerce site on Drupal 8.

- This module uses the default packer, which puts all items into a single box, this won't work for anything beyond pretty basic shipping. It needs a custom packer.

- It does not check for total package value at any point, this may leave you over the value limit for the selected option.


Please report bugs in the [issue queue](https://www.drupal.org/project/issues/commerce_nzpost?version=8.x).

## Requirements

* A NZ Post shipping account from [developer@nzpost.co.nz](developer@nzpost.co.nz)
* Drupal 8
* Drupal Commerce 2.x
* Commerce Shipping 2.x

## Installation

Use [Composer](https://getcomposer.org/) to get Commerce NZ Post and all of its
dependencies installed on your Drupal 8 site:

```
composer require drupal/commerce_nzpost
```

Then simply enable the "NZ Post (Commerce Shipping)" module and visit 
`Commerce > Configuration > Shipping Methods` to configure NZ Post.
