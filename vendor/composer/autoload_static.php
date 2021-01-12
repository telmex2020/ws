<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInite0cf5e823b4313c1e33a6c99fa3d9079
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Phroute\\Phroute\\' => 16,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Phroute\\Phroute\\' => 
        array (
            0 => __DIR__ . '/..' . '/phroute/phroute/src/Phroute',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInite0cf5e823b4313c1e33a6c99fa3d9079::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInite0cf5e823b4313c1e33a6c99fa3d9079::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
