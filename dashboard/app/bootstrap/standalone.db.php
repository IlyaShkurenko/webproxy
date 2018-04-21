<?php

use Doctrine\DBAL\DBALException;

if (isset($app['config.db.proxy.name']) and !empty($app['config.db.proxy.name'])) {
    $app->register(new Silex\Provider\DoctrineServiceProvider(), [
        'dbs.options' => [
            'proxy' => [
                'driver' => 'pdo_mysql',
                'host' => $app['config.db.proxy.host'],
                'dbname' => $app['config.db.proxy.name'],
                'user' => $app['config.db.proxy.user'],
                'password' => $app['config.db.proxy.pass'],
                'charset' => 'utf8'
            ],
            'reseller' => [
                'driver' => 'pdo_mysql',
                'host' => $app['config.db.reseller.host'],
                'dbname' => $app['config.db.reseller.name'],
                'user' => $app['config.db.reseller.user'],
                'password' => $app['config.db.reseller.pass'],
                'charset' => 'utf8'
            ]
        ]
    ]);

    // Don't display DB password
    $app->extend('db', function ($db) {
        try {
            $db->connect();
        }
        catch (PdoException $e) {
            throw new PDOException($e->getMessage(), $e->getCode());
        }
        catch (DBALException $e) {
            throw new DBALException($e->getMessage(), $e->getCode());
        }

        return $db;
    });
}