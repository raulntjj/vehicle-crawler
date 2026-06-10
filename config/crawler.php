<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Marcas convencionais do Brasil
    |--------------------------------------------------------------------------
    |
    | Lista de marcas tradicionais do mercado brasileiro para extração automática.
    |
    */
    'brands' => [
        'Chevrolet',
        'Fiat',
        'BYD',
        'Volkswagen',
        'Toyota',
        'Hyundai',
        'Jeep',
        'Renault',
        'Honda',
        'Nissan',
        'Ford',
        'Peugeot',
        'Citroën',
        'Mitsubishi',
        'Caoa Chery',
        'Kia',
        'BMW',
        'Land Rover',
        'Audi',
        'Mercedes-Benz'
    ],

    /*
    |--------------------------------------------------------------------------
    | Delay entre extrações (em segundos)
    |--------------------------------------------------------------------------
    |
    | Tempo de espera entre a extração de diferentes marcas para evitar
    | sobrecarga ou rate limiting nas APIs dos portais.
    |
    */
    'delay_between_brands' => 2,

    /*
    |--------------------------------------------------------------------------
    | Localizações padrão para busca de anúncios
    |--------------------------------------------------------------------------
    |
    | Define os estados e cidades padrão (slugs no formato "uf-cidade") para
    | as requisições de crawler. Ex: "sp-sao-paulo", "mg-belo-horizonte".
    |
    */
    'default_locations' => [
        'mg-belo-horizonte',
        'sp-sao-paulo',
    ],
];

