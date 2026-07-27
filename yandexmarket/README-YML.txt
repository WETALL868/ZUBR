Yandex Market YML feed
======================

Feed URLs after upload to hosting:

  https://comp-uter.ru/yandexmarket.xml
  https://comp-uter.ru/yandexmarket/

The first URL works on Apache hosting through the root .htaccess rewrite rule.
If your hosting uses Nginx and does not read .htaccess, use /yandexmarket/ or ask
hosting support to route /yandexmarket.xml to /yandexmarket/index.php.

Settings:

  yandexmarket/config.php

Important fields:

  base_url           leave empty for automatic domain detection or set your domain
  cache_ttl_seconds  60 means rebuild on request, but not more often than once per minute
                     3600 means rebuild not more often than once per hour
  utm                tracking parameters added to product links

Products:

  yandexmarket/products.php

The feed includes the current 7 Intel Xeon E5 products, prices, pictures,
descriptions and params.
