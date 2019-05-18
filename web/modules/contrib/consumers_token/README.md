# Consumers Token

When working in a decoupled environment with a several applications each site should have own site name.
This module provides token a token [consumers:current-name] for consumers name replacement depending on what consumer has requested the token. 
For example we can set this token to the default metatags title and each application will be getting own title

## Usage

After installing the module visit `/admin/config/services/consumers` to
register consumers with different names.

## Security

Note that the module DOES NOT escape any potentially vulnerable symbols from the returning strings,
leaving this job for consumers to handle.
