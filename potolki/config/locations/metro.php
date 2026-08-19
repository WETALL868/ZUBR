<?php
/**
 * Станции метро — этап 3, по умолчанию РАЗДЕЛ ВЫКЛЮЧЕН.
 *
 * Почему так. Страница «натяжные потолки у метро N» полезна только тогда,
 * когда у неё есть собственное содержание: работы поблизости, специфика домов
 * вокруг станции, реальный спрос. Механически размноженные страницы, где
 * меняется только название станции, — это классический дорвей: они не приносят
 * трафика и портят оценку всего сайта.
 *
 * Поэтому:
 *   • раздел включается флагом geo.metro_enabled в config/site.php;
 *   • пока флаг выключен, маршруты /metro/ не создаются вообще;
 *   • даже после включения станции публикуются поштучно, после наполнения.
 *
 * Список ниже — очередь на проработку, а не готовые страницы.
 */

$stations = [
    'aviamotornaya'   => ['Авиамоторная',    'lefortovo'],
    'akademicheskaya' => ['Академическая',   'akademicheskij'],
    'alekseevskaya'   => ['Алексеевская',    'alekseevskij'],
    'babushkinskaya'  => ['Бабушкинская',    'babushkinskij'],
    'kuncevskaya'     => ['Кунцевская',      'kuncevo'],
    'marino'          => ['Марьино',         'marino'],
    'mitino'          => ['Митино',          'mitino'],
    'otradnoe'        => ['Отрадное',        'otradnoe'],
    'strogino'        => ['Строгино',        'strogino'],
    'tekstilshchiki'  => ['Текстильщики',    'tekstilshchiki'],
    'tyoplyj-stan'    => ['Тёплый Стан',     'tyoplyj-stan'],
    'shchukinskaya'   => ['Щукинская',       'shchukino'],
];

$result = [];

foreach ($stations as $slug => [$name, $rajon]) {
    $result['metro-' . $slug] = [
        'type'      => 'metro',
        'name'      => $name,
        'name_rod'  => 'метро ' . $name,
        'name_pred' => 'у метро ' . $name,
        'parent'    => $rajon,
        'served'    => true,
        'stage'     => 3,
        'status'    => 'draft',
        'index'     => false,
        'travel'    => [
            'zone'        => 'free',
            'distance_km' => 0,
            'time'        => 'Замер в день обращения или на следующий день',
            'surcharge'   => null,
        ],
        'housing'   => [],
        'services'  => ['ustanovka', 'teneviye', 'svetovye-linii'],
        'neighbors' => [],
        'cases'     => [],
        'seo' => [
            'title'       => 'Натяжные потолки у метро ' . $name,
            'description' => 'Натяжные потолки у метро ' . $name . ': расчёт стоимости и вызов замерщика.',
            'h1'          => 'Натяжные потолки у метро ' . $name,
            'lead'        => 'Раздел по станциям метро в проработке. Если ваш дом рядом с этой станцией, воспользуйтесь расчётом или страницей своего района — условия и цены одинаковые.',
        ],
        'faq' => [],
    ];
}

return $result;
