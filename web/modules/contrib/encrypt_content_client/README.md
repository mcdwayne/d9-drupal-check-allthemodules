# Drupal client-side encryption module for GSoC 2017

Development log: http://underhat.net/ <br>
Project page on Drupal.org: https://www.drupal.org/project/encrypt_content_client

# Module installation

 - Get the module by running following commands while in the root of your Drupal installation:
   - cd modules
   - git clone -b master git@gitlab.com:marnczarnecki/encrypt_content_client.git

 - Install required libraries:
   - Follow these instructions for building the library - remember about using the --with-ecc option and save the output file as /sites/all/libraries/sjcl.js
   - Download library file from here and save it as /sites/all/libraries/FileSaver.js
 - Grant following permissions:
   - “encrypt content client” - allows users to generate ECC keys, encrypt and decrypt content
   - “encrypt content client settings” - allows admins to change module’s setting including encryption policies
 - Grant following REST resource permissions using REST UI (not enabling them should gracefully limit functionality of the module):
   - Access DELETE on Client encrypted containers resource
   - Access DELETE on Client encrypted fields resource
   - Access DELETE on ECC keys resource
   - Access GET on Client encrypted containers resource
   - Access GET on Client encrypted fields resource
   - Access GET on ECC keys resource
   - Access POST on Client encrypted containers resource
   - Access POST on Client encrypted fields resource
   - Access POST on ECC keys resource


 - Post install steps and settings:
   - Navigate to /client_encryption/policies and set which nodes and fields to encrypt.
   - Generate keys as the admin user, open /user/ecc page.
   - Add a custom block for updating ECC private key.
