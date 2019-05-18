# Change Log
All notable changes to this project will be documented in this file.

## [8.x-2.0] 2019.02.15
### Added
- This changelog file
- New configuration fields for handle new logic for generate RSA keys
- Functionality for generate new RSA keys for test and production environment 
- Possibility to define identifier field for the customer
- Payment service production public RSA key
- Possibility to display summary for configuration
- Possibility to display shop public key
- Update hook for create new table
- Logic for prevent against process 2 payment responses at same time.

### Changed
- All payment module labels to the full name.
- Logic for fetch RSA keys - possibility to use default and custom for test environment.
- Remove conflict fields from payment request data
- Add product into refund requests
- Set payment request locale depends on customer language, and if not available then fetch from the configuration
- Updated translations

### Fixed
- Change `public const` into `const` - compatibility with PHP 7.0