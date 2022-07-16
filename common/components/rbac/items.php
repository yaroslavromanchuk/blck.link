<?php
return [
    'user' => [
        'type' => 1,
        'description' => 'Пользователь',
    ],
    'manager' => [
        'type' => 1,
        'description' => 'Менеджер',
        'children' => [
            'user',
        ],
    ],
    'moder' => [
        'type' => 1,
        'description' => 'Модератор',
        'children' => [
            'manager',
        ],
    ],
    'admin' => [
        'type' => 1,
        'description' => 'Администратор',
        'children' => [
            'moder',
        ],
    ],
];
