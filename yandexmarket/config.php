<?php

return [
    'shop_name' => 'Comp-Uter',
    'company' => 'ИП Михайловский Виталий Геннадьевич',

    'base_url' => 'https://comp-uter.ru',

    // The feed is rebuilt on request, but not more often than once per minute.
    // Increase to 3600 if you want hourly refresh.
    'cache_ttl_seconds' => 60,

    'currency_id' => 'RUR',
    'default_category_id' => 1,
    'categories' => [
        1 => 'Процессоры Intel Xeon',
        2 => 'Жесткие диски Seagate',
    ],

    'utm' => 'utm_source=yandexmarket&utm_medium=cpc&utm_campaign=xeon_feed',
];
