Commerce Product Add On
=================

With Commerce Product Add On you can make product variations be offered for up-selling on another product's Add to cart form. 

The way it works is by adding a product entity reference field to a product type. Selecting products A and B for product C will then make all variations of A and B appear in the Add to cart form of all product variations of C. 

## Setup

1. Install the module.

2. Add a product entity reference field to your product type. 

3. Go to the 'Manage display' tab of the product type.
  * Select 'Add to cart form with add-ons' for the Variations field
  * Configure the formatter settings of the Variations field and select your product entity reference field. 
  * You probably want to hide your product entity reference field.
  * Save the display. 
