<?php

declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'u664715641_aspal';
const DB_USER = 'u664715641_aspal';
const DB_PASS = 'Aspalemulsi2025@';

function getPDO(): PDO
{
    static $connection;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $connection;
    } catch (PDOException $exception) {
        if ((int) $exception->getCode() !== 1049) {
            throw $exception;
        }

        $serverDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
        $serverPdo = new PDO($serverDsn, DB_USER, DB_PASS, $options);
        $serverPdo->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            DB_NAME
        ));

        $connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $connection;
    }
}


