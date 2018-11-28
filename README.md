# MedelseRefererCookieBundle

The **MedelseRefererCookieBundle** is a Symfony Bundle to save referer into cookie when exists. Than cookie (referer) can be used later.


Features include:

- Compatible Symfony version 3 & 4


## Installation

### Through Composer:

Install the bundle:

```
$ composer require medelse/referer-cookie-bundle
```

### Register the bundle in app/AppKernel.php (Symfony V3):

``` php
// app/AppKernel.php

public function registerBundles()
{
    return array(
        // ...
        new Medelse\RefererCookieBundle\MedelseRefererCookieBundle(),
    );
}
```

### Register the bundle in app/AppKernel.php (Symfony V4):

``` php
// config/bundles.php

return [
    // ...
    Medelse\RefererCookieBundle\MedelseRefererCookieBundle::class => ['all' => true],
];
```

### Parameters :

``` yml
medelse_referer-cookie:
    name: 'referer' #The Name of cookie (default value "referer")
    lifetime: 604800 #The lifetime of cookie in seconds (default 604800 => 7 days)
    path: '/' #The path on the server in which the cookie will be available on (default '/')
    domain: '' #The (sub)domain that the cookie is available to (default '' so use current domain)
    overwrite: true|false #If overwrite all referer values when even one is set in get (default true)
    secure: true|false #Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client (default false)
    httponly: true|false #When TRUE the cookie will be made accessible only through the HTTP protocol (default false)
    auto_init: true|false #If true, run init and create cookie automatically. If false you have to call init manually (default true)
    track_internal_referer: true|false #If true, track on cookie last external and last internal referer (default false)
    internal_domains: [] #Array of domains who match 'internal'. If empty, watch if referer match external list. Wildcard for (sub)domain can be used
    external_domains: [] #Array of domains who match 'external'. If empty, all referes not internal are saved as external. Wildcard for (sub)domain can be used
```

## Usage

### Public service

The service name available is `medelse_referer-cookie.referer-cookie`

### Basic Usage

``` php
$this->get('medelse_referer_cookie.referer_cookie')->init(); // just init - read referer params and cookie and save new values. (optionnal if auto_init config is TRUE or automatically called when call get() method)
$this->get('medelse_referer_cookie.referer_cookie')->get(); // get all cookies as array
$this->get('medelse_referer_cookie.referer_cookie')->get('internal'); // get referer_internal (can use 'internal' or 'external')
$this->get('medelse_referer_cookie.referer_cookie')->get('source'); // get referer_internal
```



## License

This bundle is under the MIT license.