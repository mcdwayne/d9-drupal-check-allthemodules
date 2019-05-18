Commerce Shipping Stepped By Item
=================================

This module provides a shipping method for Commerce Shipping which allows a set
of different prices for different numbers of items. For example:

- Up to 5 items: €5
- 5 to 10 items: €8
- 10 to 20 items: €10

Setup
=====

Add a shipping method at admin/commerce/config/shipping-methods and select the
'Stepped rate by item quantity' plugin.

Enter different rate steps into the table. Each row represents the rate up to
the quantity, so for the above example, enter:

- Quantity, Price
- 5, 5
- 10, 8
- 20, 10

The final row will also apply for quantities over its quantity value.

Rows are automatically sorted into ascending order by quantity when the shipping
method is saved.
