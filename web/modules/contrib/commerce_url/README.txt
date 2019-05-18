---SUMMARY---

Commerce Url Hash Module is used to encypt/decrypt the order ID in checkout
Urls.

For a full description visit project page:
https://www.drupal.org/project/commerce_url

Bug reports, feature suggestions and latest developments:
https://www.drupal.org/project/issues/commerce_url

---REQUIREMENTS---

* A clean Drupal 8 installation.
* Commerce module.


---INSTALLATION---

[1] Install the Drupal 8 and commerce module.
[2] Download Commerce Url Hash module
[3] Enable the Commerce Url Hash module.
[4] Now you can go to checkout Urls you will see Order ID is converted to
    hash value.
[5] In this module for encypt/decrypt service is available. So in custom module
    if you want to convert Order ID to hash or hash to Order ID you can use
    service (commerce_url.encrypt_decrypt) available in services.yml
