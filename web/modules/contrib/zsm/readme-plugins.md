# Functions to add to the Entity #

## getZSMPluginData() ## 

This should return an array with keys set to data needed for loading the plugin by ZSM. 

Here is an example from the HAProxy plugin module

    public function getZSMPluginData() {
        return array(
            'class' => 'HAProxy',
            'type' => 'core',
            'module' => 'system_monitors.haproxy',
        );
    } 

## getZSMPluginSettings() ## 

This should return an associative array that matches the data format of the ZSM plugin.

    public function getZSMPluginSettings() {
        $data = array();
        
        // Populate the 'data' array here with entity-specific settings //
        
        return $data;
    } 

