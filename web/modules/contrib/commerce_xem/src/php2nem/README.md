# php2nem
Simple php class to access NEM (NIS &amp; NCC) api

NEM.php is a simple php class that can be used
to send api calls to NIS (Nem Infrastructure Server)
and NCC (Nem Community Client).

## How to use

```php
#include the required class

require_once 'NEM.php';

#define the initial configuration parameters
#if not defined the defaults will be used
$conf = array('nis_address' => 'go.nem.ninja');

#create an instance using a user defined configuration options
$nem = new NEM($conf);
```

you can also create the nem object with the default options by not passing any
parameter in the constructor call

```php

$nem = new NEM();

```

you can then set the options at later stage in your code if needed with the set_options method:

```php
$nem->set_options($conf);
```

to list all the options or a single option value use the get_options method:
```php
$opt = $nem->get_options();
print_r($opt);
```
the output should look like this:

```
Array
(
    [nis_address] => go.nem.ninja
    [nis_port] => 7890
    [nis_context] => /
    [ncc_address] => 127.0.0.1
    [ncc_port] => 8989
    [ncc_context] => /ncc/api/
)
```

or if called with a key parameter
```php
$opt = $nem->get_options('nis_port');
echo $opt;
```
it returns a single scalar value of the requested option in this case:

```
7890
```

### making requests to NIS (Nem Infrastructure Server)

get request sample:
```php
$res = $nem->nis_get('/node/info');
echo $res;
```

the returned output is in a raw JSON format:
```
{"metaData":{"features":1,"application":null,"networkId":104,"version":"0.6.27-BETA","platform":"Oracle Corporation (1.8.0_05) on Linux"},"endpoint":{"protocol":"http","port":7890,"host":"go.nem.ninja"},"identity":{"name":"[c=#41ce7b]g[\/c][c=#dfa82f]o[c]","public-key":"26a3ac4b24647c77dc87780a95e50cb8d7744966e4569e3ac24e52c532c0cd0d"}}

```

post request sample:
```php
# sample using php associative array

$data = array();
$data['height'] = 10000;

$res = $nem->nis_post('/block/at/public',$data);
echo $res;
```

the parameters to the nis_post or ncc_post methods can be passed also as JSON formatted text:

```php
# sample using JSON formatted text

$data = <<<JSON
{"height":10000}
JSON;

$res = $nem->nis_post('/block/at/public',$data);
echo $res;
```

the returned output is in a raw JSON format:
```
{"timeStamp":708668,"signature":"4cdef7f7e9ced87d8e76e11fe38d37c6626cdb230b2221bb4c084f6bf71f4d187e4d9b353ecbd99937dee6d5f2333866141eed4cd96cdad6d720b382c1f6f502","prevBlockHash":{"data":"88c241cfa57263ced09206e2ccf21bec2c5c9e7f161f2fb0d144dd7da52a927a"},"type":1,"transactions":[{"timeStamp":708650,"amount":10000000,"signature":"8a3a7acd2c8f6d12cc0ec16211ca3bbca7550c443676f35be9ff6bd59eaccabf96af6dfaa7b9c0f0f6f088dcdbd986b4ba6490f5d034cca192593f4e0138cb03","fee":10000000,"recipient":"NALICELGU3IVY4DPJKHYLSSVYFFWYS5QPLYEZDJJ","type":257,"deadline":709250,"message":{"payload":"416c69636520697320646f6e6174696e67203130206e656d206576657279206d696e75746520746f20706f6f7220686172766573746572732e","type":1},"version":1744830465,"signer":"599af9dbc9c36d0cf7d44e4356097d67892aa11e13c7669019f6b42d144a975b"}],"version":1744830465,"signer":"d712788c5d16f7c72dda00c25a7e18f02b992d26d26fc196227b6a72fbb20c9c","height":10000}

```

a user defined handler function can be used in all the methods to handle the response from NIS or NCC

a simple example handler function:
```php
# the function will return the response in raw JSON data as php associative array
$to_php = function($data) {
	$d = json_decode($data,TRUE);
	return $d;	
};


$block = $nem->nis_post('/block/at/public',$data,$to_php);

#you can now use the $block variable further in your php code

echo $block['timeStamp'];   
...
```

### making requests to NCC (Nem Community Client)

get request sample:
```php
$res = $nem->ncc_get('/info/ncc');
echo $res;
```

the returned output is in a raw JSON format:
```
{"metaData":{"currentTime":2913152,"application":"NEM Deploy","startTime":2912577,"version":"0.6.28-BETA","signer":null},"remoteServer":"http:\/\/go.nem.ninja:7890\/","language":"en"}

```

post request sample:
```php
$data = <<<JSON
{
    "data": [{
        "protocol": "http",
        "host": "bob.nem.ninja",
        "port": 7890
    }]
}
JSON;

echo $nem->ncc_post('/network',$data);
```
the returned output is in a raw JSON format:
```
{"meta":{"meta":[{"endpoint":{"protocol":"http","port":7890,"host":"107.179.25.32"},"address":"NALICEROONSJCPHC63F52V6FY3SDMSVAEWH3QUJF","active":1,"version":"0.6.28-BETA","platform":"Oracle Corporation (1.8.0_40) on Linux"},{"endpoint":{"protocol":"http","port":7890,"host":"37.187.70.29"},"address":"ND75VR7ZKB4G45Q4HJPPSEHQKIYYKQXQ7VW4JORS","active":1,"version":"0.6.28-BETA","platform":"Oracle Corporation (1.8.0_40) on Linux"}]},"graph":{"nodes":[{"id":"NALICEROONSJCPHC63F52V6FY3SDMSVAEWH3QUJF","label":"Hi, I am MedAlice2"},{"id":"ND75VR7ZKB4G45Q4HJPPSEHQKIYYKQXQ7VW4JORS","label":"bob.nem.ninja"}],"edges":[{"id":"ND75VR7ZKB4G45Q4HJPPSEHQKIYYKQXQ7VW4JORS-NALICEROONSJCPHC63F52V6FY3SDMSVAEWH3QUJF","source":"ND75VR7ZKB4G45Q4HJPPSEHQKIYYKQXQ7VW4JORS","target":"NALICEROONSJCPHC63F52V6FY3SDMSVAEWH3QUJF"}]}}

```

here as well a user defined custom handler function can be used.
For example a handler function to create a pretty JSON ouput:

```php
#output a pretty formated JSON text
$to_pretty_json = function($data) {
	$d = json_decode($data,TRUE);
	$d = json_encode($d,JSON_PRETTY_PRINT);
	echo $d;
};


$nem->ncc_post('/network',$data,$to_pretty_json);
```

the output should now look like this:

```
{
    "meta": {
        "meta": [
            {
                "endpoint": {
                    "protocol": "http",
                    "port": 7890,
                    "host": "107.179.25.32"
                },
                "address": "NALICEROONSJCPHC63F52V6FY3SDMSVAEWH3QUJF",
                "active": 1,
                "version": "0.6.28-BETA",
                "platform": "Oracle Corporation (1.8.0_40) on Linux"
            },
            {
                "endpoint": {
                    "protocol": "http",
                    "port": 7890,
                    "host": "37.187.70.29"
                },
                "address": "ND75VR7ZKB4G45Q4HJPPSEHQKIYYKQXQ7VW4JORS",
                "active": 1,
                "version": "0.6.28-BETA",
                "platform": "Oracle Corporation (1.8.0_40) on Linux"
            }
        ]
    },
    "graph": {
        "nodes": [
            {
                "id": "NALICEROONSJCPHC63F52V6FY3SDMSVAEWH3QUJF",
                "label": "Hi, I am MedAlice2"
            },
            {
                "id": "ND75VR7ZKB4G45Q4HJPPSEHQKIYYKQXQ7VW4JORS",
                "label": "bob.nem.ninja"
            }
        ],
        "edges": [
            {
                "id": "ND75VR7ZKB4G45Q4HJPPSEHQKIYYKQXQ7VW4JORS-NALICEROONSJCPHC63F52V6FY3SDMSVAEWH3QUJF",
                "source": "ND75VR7ZKB4G45Q4HJPPSEHQKIYYKQXQ7VW4JORS",
                "target": "NALICEROONSJCPHC63F52V6FY3SDMSVAEWH3QUJF"
            }
        ]
    }
}
```

## Documentation

for more detailed information about the NIS and NCC api please visit the following links:

* [NIS API documentation](http://bob.nem.ninja/docs/)
* [NCC API documentation](https://github.com/NewEconomyMovement/NemCommunityClient/blob/master/docs/api.md)
