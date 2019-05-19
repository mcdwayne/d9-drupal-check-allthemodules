# Introduction
SMS Framework gateway module for any simple HTTP GET/POST gateway interface

The rationale for this module is that many (or most) SMS gateway services use
basic HTTP GET or POST requests, the only difference being the names of the
HTTP parameters. This module allows the user to specify the parameter names
for sending and receiving messages, thus alleviating the need to write another
gateway module.

# Configuration

 1. Create a gateway instance (_/admin/config/smsframework/gateways_)
 2. Use 'Simple Gateway' for the plugin type.
 3. After the form saves, fill in each required form field.
 4. Save the form. Your gateway is now configured.
