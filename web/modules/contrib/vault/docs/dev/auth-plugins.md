# Authentication Strategy Plugins

Vault offers a number of authentications strategies for both human and machine users. See their documentation for a list of supported auth methods - https://www.vaultproject.io/docs/auth/index.html

Authentication methods are controlled by implementing a `VaultAuth` plugin in a module.

The crux of this plugin is the `getAuthenticationStrategy()` method which returns an object implementing the `Vault\AuthenticationStrategies\AuthenticationStrategy` interface.

Several examples can be found in the vault-php client library - https://github.com/CSharpRU/vault-php/tree/master/src/AuthenticationStrategies
