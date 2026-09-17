<?php

return [
    // Часовой пояс вуза для расписания и «текущей пары».
    'timezone' => env('MINIAPP_TIMEZONE', 'Europe/Saratov'),

    // Первый день семестра: от него считаются числитель (нечётные недели) и знаменатель.
    'semester_start' => env('MINIAPP_SEMESTER_START', '2026-09-01'),

    // Slug вуза, к которому автоматически привязываются новые пользователи MAX на время пилота.
    // Пусто — доступ только студентам из списка участников.
    'enroll_organization' => env('MINIAPP_ENROLL_ORGANIZATION'),

    // Показывать бейдж «Демо-данные», пока в системе тестовый контент.
    'demo_data' => (bool) env('MINIAPP_DEMO_DATA', true),

    // Вход демо-студентом без MAX. Только для локальной проверки, в production всегда выключен.
    'demo_login' => env('MINIAPP_DEMO_LOGIN', false) && env('APP_ENV') !== 'production',
];
