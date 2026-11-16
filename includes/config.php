<?php
return [
    'base_url' => 'http://localhost/php-blog/',
    'db_dsn'   => 'mysql:host=localhost;dbname=php_blog;charset=utf8mb4',
    'db_user'  => 'root',
    'db_pass'  => '', // оставь пустым, если пароль не установлен

    // --- Параметры загрузки медиа ---
    'upload' => [
        'images_dir'      => __DIR__ . '/../uploads/images', // папка для картинок
        'videos_dir'      => __DIR__ . '/../uploads/videos', // папка для видео
        'max_image_size'  => 5 * 1024 * 1024,  // 5 МБ
        'max_video_size'  => 50 * 1024 * 1024  // 50 МБ
    ]
];
