<?php

/**
 * Файл инициализации для запуска тестов
 *
 * Перед запуском сделать composer install
 * http://getcomposer.org/download/
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Для корректной обработки punycode
mb_internal_encoding('utf-8');