# Development Documentation

## How to use wsdata in modules

```
$wsdata  = \Drupal::service('wsdata');
$result = $wsdata->call('wscall name', 'method type');
```


## More complex/complete example on the parameters to be passed to the call method. 
```
$wsdata  = \Drupal::service('wsdata');
$result = $wsdata->call('wscall name', 'method type', 'array of replacement token in the URI', 'data to pass to the request, example post data', 'options', 'token to select', 'tokens');
```
