# Secret Engines

Vault's primary appeal is the secret engines it provides. See their documentation for a list of available secret engines - https://www.vaultproject.io/docs/secrets/index.html

There are various ways to implement these secret engines based on what they do.

For example:

* The `vault_key_kv` module adds a key storage provider, allowing arbitrary data to be stored in Vault at a specified path.
* The `vault_key_aws` module adds a key storage provider which requests dynamic AWS credentials from Vault.
* The `encrypt_vault_transit` module provides an encryption method plugin, leveraging Vault's transit secret engine.

