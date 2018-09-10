PhpFastCache for Drupal
====================

The PhpFastCache module makes use of PhpFastCache library.
It's main goal is to improve Drupal's performances by
adding a new cache backend system depending your needs

DEPENDENCIES
------------
- phpfastcache/phpfastcache
- php7

CONFIGURATION
-------------

1. Enable PhpFastCache module in:\
admin/modules

2. You'll now find a PhpFastCache tab in the "Configuration > Development" menu\
admin/config/development/phpfastcache

3. Settings up the driver you need and it's options (if needed)

4. Alter your settings.php to add this line:\
```php
$settings['cache']['default'] = 'cache.backend.phpfastcache';
```

KNOWN ISSUES
-------------
Plenty ATM, but it's still in development :)