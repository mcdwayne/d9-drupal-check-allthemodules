# HashiCorp Vault Drupal Integration

This site covers setup and developer docs for the following Drupal projects:

* [drupal.org/project/vault](https://www.drupal.org/project/vault)
* [drupal.org/project/vault_key_kv](https://www.drupal.org/project/vault_key_kv)
* [drupal.org/project/encrypt_vault_transit](https://www.drupal.org/project/encrypt_vault_transit)
* [drupal.org/project/vault_key_aws](https://www.drupal.org/project/vault_key_aws)
* [drupal.org/project/vault_auth_token](https://www.drupal.org/project/vault_auth_token)

## What is Vault?

> Vault is a tool for securely accessing secrets. A secret is anything that you want to tightly control access to, such as API keys, passwords, or certificates.

Vault is an open source project, and has some excellent documentation and introduction resources.

* [What is Vault?](https://www.vaultproject.io/intro/index.html)
* [Common Use Cases](https://www.vaultproject.io/intro/use-cases.html)
* [Getting Started](https://www.vaultproject.io/intro/getting-started/install.html)
* [GitHub](https://github.com/hashicorp/vault)

## Why Vault with Drupal?

**Unparalleled Feature-Set**

Vault has a huge range of features. Just to name a few:

* Encrypted key/value storage
* Encryption-as-a-service
* Automatic rotation of credentials
* Revokation of credentials
* Audit logging for compliance and intrusion detection 

**Free and Open Source Software**

The Drupal community has produced some excellent tooling to abstract [secret storage](https://www.drupal.org/project/key) and [encryption](https://www.drupal.org/project/encrypt). However there are issues with the ecosystem of tools which leverage these abstractions to perform the cryptographic functions.

* Most of the existing integrations are for commercial services
* The FOSS options are difficult to operate in a secure manner


![HashiCorp Vault](/gitbook/images/vault-logo.png)