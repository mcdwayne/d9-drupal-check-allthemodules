Commerce Shipping Price Matrix
==============================

The *Commerce Shipping Price Matrix* module provides functionality for calculating shipping costs based on a price matrix.

## Requirements

* [Commerce Shipping](https://www.drupal.org/project/commerce_shipping)

## Setup

1. Install the module.

2. Create a new Shipping Method of type Price Matrix and configure it following the instructions on the screen.

## Features

* Upload a Price Matrix as a CSV file.
* Optionally exclude product variation types or individual product variations from the order subtotal for the purposes of calculating the shipping costs.
* Multi-currency stores are not currently supported. It is assumed that a shipping method's price matrix has the same currency as the order.
