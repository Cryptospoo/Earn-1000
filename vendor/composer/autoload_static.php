<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit05839c5b9f262e74337a9e67fb105586
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PayPalHttp\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PayPalHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/paypal/paypalhttp/lib/PayPalHttp',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'PayPalHttp\\Curl' => __DIR__ . '/..' . '/paypal/paypalhttp/lib/PayPalHttp/Curl.php',
        'PayPalHttp\\Encoder' => __DIR__ . '/..' . '/paypal/paypalhttp/lib/PayPalHttp/Encoder.php',
        'PayPalHttp\\Environment' => __DIR__ . '/..' . '/paypal/paypalhttp/lib/PayPalHttp/Environment.php',
        'PayPalHttp\\HttpClient' => __DIR__ . '/..' . '/paypal/paypalhttp/lib/PayPalHttp/HttpClient.php',
        'PayPalHttp\\HttpException' => __DIR__ . '/..' . '/paypal/paypalhttp/lib/PayPalHttp/HttpException.php',
        'PayPalHttp\\HttpRequest' => __DIR__ . '/..' . '/paypal/paypalhttp/lib/PayPalHttp/HttpRequest.php',
        'PayPalHttp\\HttpResponse' => __DIR__ . '/..' . '/paypal/paypalhttp/lib/PayPalHttp/HttpResponse.php',
        'PayPalHttp\\IOException' => __DIR__ . '/..' . '/paypal/paypalhttp/lib/PayPalHttp/IOException.php',
        'PayPalHttp\\Injector' => __DIR__ . '/..' . '/paypal/paypalhttp/lib/PayPalHttp/Injector.php',
        'PayPalHttp\\Serializer' => __DIR__ . '/..' . '/paypal/paypalhttp/lib/PayPalHttp/Serializer.php',
        'PayPalHttp\\Serializer\\Form' => __DIR__ . '/..' . '/paypal/paypalhttp/lib/PayPalHttp/Serializer/Form.php',
        'PayPalHttp\\Serializer\\FormPart' => __DIR__ . '/..' . '/paypal/paypalhttp/lib/PayPalHttp/Serializer/FormPart.php',
        'PayPalHttp\\Serializer\\Json' => __DIR__ . '/..' . '/paypal/paypalhttp/lib/PayPalHttp/Serializer/Json.php',
        'PayPalHttp\\Serializer\\Multipart' => __DIR__ . '/..' . '/paypal/paypalhttp/lib/PayPalHttp/Serializer/Multipart.php',
        'PayPalHttp\\Serializer\\Text' => __DIR__ . '/..' . '/paypal/paypalhttp/lib/PayPalHttp/Serializer/Text.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit05839c5b9f262e74337a9e67fb105586::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit05839c5b9f262e74337a9e67fb105586::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit05839c5b9f262e74337a9e67fb105586::$classMap;

        }, null, ClassLoader::class);
    }
}
