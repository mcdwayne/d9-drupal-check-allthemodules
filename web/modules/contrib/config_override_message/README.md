# Config override message

The Config override message module allows developers to display custom messages
on admin paths where the configuration is overridden.

This module addresses the scenario where a site builder attempts to update 
some configuration  and does not know that the configuration is being 
overridden via code.  

## Configuring

To display a custom message you need to add the below properties to your 
exported and overridden configuration YAML files.

    # The custom message to be displayed.
    _config_override_message: 'This is the custom message.'
    # THe paths where the custom message should be displayed.
    _config_override_paths: 
      - '/path-one'
      - '/path-two'
      
Also, make sure that users who should see these messages are assigned 
the 'view config override message' permission.
    
## Notes

- Config override messages are not translatable.     
