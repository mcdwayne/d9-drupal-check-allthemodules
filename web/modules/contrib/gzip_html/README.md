# Gzip HTML

This project gzips your html output if your hoster does not support or does not allow gzip via apache htaccess or nginx configuration.
(For example platform.sh is not allowing gzip. See: https://docs.platform.sh/configuration/app-containers.html#compression)

## Configuration

    Enable it under: `admin/config/development/performance`
 
## WARNING:
This module is not working together with big_pipe enabled.