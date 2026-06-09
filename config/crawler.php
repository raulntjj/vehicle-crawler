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
    | Localização padrão para busca de anúncios
    |--------------------------------------------------------------------------
    |
    | Define o estado e cidade padrão (slug no formato "uf-cidade") para
    | as requisições de crawler. Ex: "sp-sao-paulo", "mg-belo-horizonte".
    |
    */
    'default_location' => 'sp-sao-paulo',
];

