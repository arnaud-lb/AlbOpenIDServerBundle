# AlbOpenIDServerBundle

OpenID Provider bundle.

## Installation

### Step1: Download AlbOpenIDServerBundle

#### Using the vendors script

Add the following lines in your deps file:

```
[AlbOpenIDServerBundle]
    git=git://github.com/arnaud-lb/AlbOpenIDServerBundle.git
    target=bundles/Alb/AlbOpenIDServerBundle
    
[php-openid]
    git=git://github.com/openid/php-openid.git
    target=openid/php-openid
```

Now, run the vendors script to download the bundle:

``` sh
$ php bin/vendors install
```

#### Using submodules

If you prefer instead to use git submodules, then run the following:

``` sh
$ git submodule add git://github.com/arnaud-lb/AlbOpenIDServerBundle.git vendor/bundles/Alb/AlbOpenIDServerBundle
$ git submodule add git://github.com/openid/php-openid.git vendor/openid/php-openid
$ git submodule update --init
```

#### Using composer

TODO

### Step2: Configure the Autoloader

You can skip this step if you have installed the bundle using composer.

Add the ``Alb`` namespace to your autoloader:

``` php
<?php

// app/autoload.php

$loader->registerNamespaces(array(
    // ...
    'Alb' => __DIR__.'/../vendor/',
));
```

### Step3: Configure the include path

php-openid relies on his classes to be in the include path:

``` php
<?php

/// app/autoload.php

...

set_include_path(
    get_include_path()
    . PATH_SEPARATOR 
    . __DIR__ . '/../vendor/openid/php-openid'
);

...
```

### Step4: Enable the bundle

Finally, enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Alb\OpenIDServerBundle\AlbOpenIDServerBundle(),
    );
}
```

## Creating an Adapter

The bundle relies on an adapter for things that may be specific to your application. The adapter must implement `Alb\OpenIDServerBundle\Adapter\AdapterInterface`.

Here is a simple implementation:

``` php
<?php

namespace <your_namespace>;

use Alb\OpenIDServerBundle\Adapter\AdapterInterface;

class Adapter implements AdapterInterface
{
    public function getUserUnique($user)
    {
        return $user->getId();
    }
}
```

Declare a service using this class:

``` yaml
# app/config/config.yml

services:
    my_open_id_server_adapter:
        class:  <your_namespace>\Adapter
```

## Configuring

### Bundle configuration

Add this to app/config/config.yml:

``` yaml
# app/config/config.yml

alb_open_id_server_bundle:
    service:
        adapter: my_open_id_server_adapter
```

### Routing

Add this to app/config/routing.yml:

``` yaml
# app/config/routing.yml
alb_open_id_server:
    resource: "@AlbOpenIDServerBundle/Resources/config/routing.xml"
    prefix: /openid
```

## Usage

The OpenID endpoint is at /openid (depending on the routes prefix)

## TODO

- Add tests
- More documentation

