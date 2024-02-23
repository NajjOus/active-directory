<?php

require_once __DIR__ . '/../../vendor/autoload.php';

session_start();

$config = [
    'authentication' => [
        'ad' => [
            'client_id' => '',
            'client_secret' => '',
            'enabled' => '1',
            'directory' => ''
        ]
    ]
];

$request = new \Laminas\Http\PhpEnvironment\Request();

$ad = new \Magium\ActiveDirectory\ActiveDirectory(
    new \Magium\Configuration\Config\Repository\ArrayConfigurationRepository($config),
    \Laminas\Psr7Bridge\Psr7ServerRequest::fromLaminas(new \Laminas\Http\PhpEnvironment\Request())
);

$entity = $ad->authenticate();

echo $entity->getName() . '<Br />';
echo $entity->getOid() . '<Br />';
echo $entity->getPreferredUsername() . '<Br />';
