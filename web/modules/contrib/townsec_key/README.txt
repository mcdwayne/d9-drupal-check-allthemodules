Welcome to the Townsend Security Key Connection module!

This module provides integration with Townsend Security's Alliance Key
Manager (AKM) to allow secure, remote key management and NIST-certified,
off-site encryption.


To Install:

1. Add the Townsend Security Key Connection and Key modules as dependencies
to your project.
  > composer require drupal/townsec_key
  > composer require drupal/key

2. If you plan to use the AKM for encryption, either locally or remotely,
add the Encrypt module as a dependency. You will also need a module that
provides an encryption method, such as Real AES.

 > composer require drupal/encrypt
  > composer require drupal/real_aes

3. Install the modules via the module installation page at
/admin/modules.


To Configure:

1. Add an AKM server at /admin/config/system/townsec-key/add. Enter the
server name, host name or IP address, and location of the required
certificates.

IMPORTANT - keep your authentication certificates OUTSIDE the web root
directory, accessible only to the server via your linux permissions. This is
important to prevent unauthorized access to your key management server.
It defeats the purpose to have remote key retrieval if you leave the
authentication in an easy to reach space.

2. Add a key at /admin/config/system/keys/add. Select the Townsend AKM
key provider and enter the name of the key on the AKM server.

3. Add an encryption profile at /admin/config/system/encryption/profiles/add.
Select your preferred encryption method and the key that was created in step
two.
