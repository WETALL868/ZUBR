<?php
/**
 * Районы Москвы — этап 2.
 *
 * Все районы созданы как СТРУКТУРА, а не как готовые посадочные страницы:
 *   status = 'draft', index = false → noindex,follow и никакого sitemap.
 *
 * Это сознательное решение. Страница района имеет право на индексацию только
 * тогда, когда у неё есть собственный смысл: реальные работы поблизости,
 * особенности застройки, свой FAQ. Пока этого нет, публиковать сотни
 * одинаковых текстов с заменой топонима — это дорвей, а не SEO.
 *
 * Как опубликовать район:
 *   1. Заполните housing, lead, faq, cases в этом файле.
 *   2. Поставьте status = 'published', index = true.
 *   3. Запустите php tests/run.php — проверка качества не пропустит
 *      страницу с шаблонным или слишком коротким текстом.
 *
 * Падежная форма выбрана единообразной («в районе Арбат»): она грамматически
 * корректна для любого названия и не создаёт ошибок склонения.
 */

// slug => [название, округ]
$rajony = [
    // ЦАО
    'arbat'                 => ['Арбат', 'cao'],
    'basmannyj'             => ['Басманный', 'cao'],
    'zamoskvoreche'         => ['Замоскворечье', 'cao'],
    'krasnoselskij'         => ['Красносельский', 'cao'],
    'meshchanskij'          => ['Мещанский', 'cao'],
    'presnenskij'           => ['Пресненский', 'cao'],
    'taganskij'             => ['Таганский', 'cao'],
    'tverskoj'              => ['Тверской', 'cao'],
    'hamovniki'             => ['Хамовники', 'cao'],
    'yakimanka'             => ['Якиманка', 'cao'],

    // САО
    'aeroport'              => ['Аэропорт', 'sao'],
    'begovoj'               => ['Беговой', 'sao'],
    'beskudnikovskij'       => ['Бескудниковский', 'sao'],
    'vojkovskij'            => ['Войковский', 'sao'],
    'golovinskij'           => ['Головинский', 'sao'],
    'vostochnoe-degunino'   => ['Восточное Дегунино', 'sao'],
    'zapadnoe-degunino'     => ['Западное Дегунино', 'sao'],
    'dmitrovskij'           => ['Дмитровский', 'sao'],
    'koptevo'               => ['Коптево', 'sao'],
    'levoberezhnyj'         => ['Левобережный', 'sao'],
    'molzhaninovskij'       => ['Молжаниновский', 'sao'],
    'savyolovskij'          => ['Савёловский', 'sao'],
    'sokol'                 => ['Сокол', 'sao'],
    'timiryazevskij'        => ['Тимирязевский', 'sao'],
    'hovrino'               => ['Ховрино', 'sao'],
    'horoshyovskij'         => ['Хорошёвский', 'sao'],

    // СВАО
    'alekseevskij'          => ['Алексеевский', 'svao'],
    'altufevskij'           => ['Алтуфьевский', 'svao'],
    'babushkinskij'         => ['Бабушкинский', 'svao'],
    'bibirevo'              => ['Бибирево', 'svao'],
    'butyrskij'             => ['Бутырский', 'svao'],
    'lianozovo'             => ['Лианозово', 'svao'],
    'losinoostrovskij'      => ['Лосиноостровский', 'svao'],
    'marfino'               => ['Марфино', 'svao'],
    'marina-roshcha'        => ['Марьина Роща', 'svao'],
    'severnoe-medvedkovo'   => ['Северное Медведково', 'svao'],
    'yuzhnoe-medvedkovo'    => ['Южное Медведково', 'svao'],
    'ostankinskij'          => ['Останкинский', 'svao'],
    'otradnoe'              => ['Отрадное', 'svao'],
    'rostokino'             => ['Ростокино', 'svao'],
    'svibovo'               => ['Свиблово', 'svao'],
    'severnyj'              => ['Северный', 'svao'],
    'yaroslavskij'          => ['Ярославский', 'svao'],

    // ВАО
    'bogorodskoe'           => ['Богородское', 'vao'],
    'veshnyaki'             => ['Вешняки', 'vao'],
    'vostochnoe-izmajlovo'  => ['Восточное Измайлово', 'vao'],
    'golyanovo'             => ['Гольяново', 'vao'],
    'ivanovskoe'            => ['Ивановское', 'vao'],
    'izmajlovo'             => ['Измайлово', 'vao'],
    'kosino-uhtomskij'      => ['Косино-Ухтомский', 'vao'],
    'metrogorodok'          => ['Метрогородок', 'vao'],
    'novogireevo'           => ['Новогиреево', 'vao'],
    'novokosino'            => ['Новокосино', 'vao'],
    'perovo'                => ['Перово', 'vao'],
    'preobrazhenskoe'       => ['Преображенское', 'vao'],
    'severnoe-izmajlovo'    => ['Северное Измайлово', 'vao'],
    'sokolinaya-gora'       => ['Соколиная Гора', 'vao'],
    'sokolniki'             => ['Сокольники', 'vao'],

    // ЮВАО
    'vyhino-zhulebino'      => ['Выхино-Жулебино', 'yuvao'],
    'kapotnya'              => ['Капотня', 'yuvao'],
    'kuzminki'              => ['Кузьминки', 'yuvao'],
    'lefortovo'             => ['Лефортово', 'yuvao'],
    'lyublino'              => ['Люблино', 'yuvao'],
    'marino'                => ['Марьино', 'yuvao'],
    'nekrasovka'            => ['Некрасовка', 'yuvao'],
    'nizhegorodskij'        => ['Нижегородский', 'yuvao'],
    'pechatniki'            => ['Печатники', 'yuvao'],
    'ryazanskij'            => ['Рязанский', 'yuvao'],
    'tekstilshchiki'        => ['Текстильщики', 'yuvao'],
    'yuzhnoportovyj'        => ['Южнопортовый', 'yuvao'],

    // ЮАО
    'vostochnoe-biryulyovo' => ['Восточное Бирюлёво', 'yuao'],
    'zapadnoe-biryulyovo'   => ['Западное Бирюлёво', 'yuao'],
    'brateevo'              => ['Братеево', 'yuao'],
    'danilovskij'           => ['Даниловский', 'yuao'],
    'donskoj'               => ['Донской', 'yuao'],
    'zyablikovo'            => ['Зябликово', 'yuao'],
    'moskvoreche-saburovo'  => ['Москворечье-Сабурово', 'yuao'],
    'nagatino-sadovniki'    => ['Нагатино-Садовники', 'yuao'],
    'nagatinskij-zaton'     => ['Нагатинский Затон', 'yuao'],
    'nagornyj'              => ['Нагорный', 'yuao'],
    'orehovo-borisovo-sev'  => ['Орехово-Борисово Северное', 'yuao'],
    'orehovo-borisovo-yuzh' => ['Орехово-Борисово Южное', 'yuao'],
    'caricyno'              => ['Царицыно', 'yuao'],
    'chertanovo-severnoe'   => ['Чертаново Северное', 'yuao'],
    'chertanovo-centralnoe' => ['Чертаново Центральное', 'yuao'],
    'chertanovo-yuzhnoe'    => ['Чертаново Южное', 'yuao'],

    // ЮЗАО
    'akademicheskij'        => ['Академический', 'yuzao'],
    'gagarinskij'           => ['Гагаринский', 'yuzao'],
    'zyuzino'               => ['Зюзино', 'yuzao'],
    'konkovo'               => ['Коньково', 'yuzao'],
    'kotlovka'              => ['Котловка', 'yuzao'],
    'lomonosovskij'         => ['Ломоносовский', 'yuzao'],
    'obruchevskij'          => ['Обручевский', 'yuzao'],
    'severnoe-butovo'       => ['Северное Бутово', 'yuzao'],
    'tyoplyj-stan'          => ['Тёплый Стан', 'yuzao'],
    'cheryomushki'          => ['Черёмушки', 'yuzao'],
    'yuzhnoe-butovo'        => ['Южное Бутово', 'yuzao'],
    'yasenevo'              => ['Ясенево', 'yuzao'],

    // ЗАО
    'vnukovo'               => ['Внуково', 'zao'],
    'dorogomilovo'          => ['Дорогомилово', 'zao'],
    'krylatskoe'            => ['Крылатское', 'zao'],
    'kuncevo'               => ['Кунцево', 'zao'],
    'mozhajskij'            => ['Можайский', 'zao'],
    'novo-peredelkino'      => ['Ново-Переделкино', 'zao'],
    'ochakovo-matveevskoe'  => ['Очаково-Матвеевское', 'zao'],
    'prospekt-vernadskogo'  => ['Проспект Вернадского', 'zao'],
    'ramenki'               => ['Раменки', 'zao'],
    'solncevo'              => ['Солнцево', 'zao'],
    'troparyovo-nikulino'   => ['Тропарёво-Никулино', 'zao'],
    'filyovskij-park'       => ['Филёвский Парк', 'zao'],
    'fili-davydkovo'        => ['Фили-Давыдково', 'zao'],

    // СЗАО
    'kurkino'               => ['Куркино', 'szao'],
    'mitino'                => ['Митино', 'szao'],
    'pokrovskoe-streshnevo' => ['Покровское-Стрешнево', 'szao'],
    'severnoe-tushino'      => ['Северное Тушино', 'szao'],
    'strogino'              => ['Строгино', 'szao'],
    'horoshyovo-mnyovniki'  => ['Хорошёво-Мнёвники', 'szao'],
    'shchukino'             => ['Щукино', 'szao'],
    'yuzhnoe-tushino'       => ['Южное Тушино', 'szao'],
];

$result = [];

foreach ($rajony as $slug => [$name, $okrug]) {
    $result[$slug] = [
        'type'      => 'rajon',
        'name'      => $name,
        'name_rod'  => 'района ' . $name,
        'name_pred' => 'в районе ' . $name,
        'parent'    => $okrug,
        'served'    => true,
        'stage'     => 2,
        'status'    => 'draft',
        'index'     => false,
        'travel'    => [
            'zone'        => 'free',
            'distance_km' => 0,
            'time'        => 'Замер в день обращения или на следующий день',
            'surcharge'   => null,
        ],
        'housing'   => [],
        'services'  => ['ustanovka', 'teneviye', 'svetovye-linii', 'dlya-kvartiry'],
        'neighbors' => [],
        'cases'     => [],
        'seo' => [
            'title'       => 'Натяжные потолки ' . 'в районе ' . $name,
            'description' => 'Натяжные потолки в районе ' . $name . ': расчёт стоимости, бесплатный замер, монтаж под ключ.',
            'h1'          => 'Натяжные потолки в районе ' . $name,
            'lead'        => 'Страница района готовится: добавляем примеры работ поблизости и особенности домов. Расчёт стоимости и вызов замерщика уже работают — район подставляется в заявку автоматически.',
        ],
        'faq' => [],
    ];
}

return $result;
