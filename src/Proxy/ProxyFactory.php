<?php

namespace Michel\PaperORM\Proxy;

final class ProxyFactory
{
    private static bool $registered = false;

    public static function registerAutoloader(): void
    {
        if (self::$registered) {
            return;
        }

        spl_autoload_register(function ($class) {
            if (strpos($class, 'Proxy_') === 0) {
                $original = str_replace('_', '\\', substr($class, 6));
                self::generate($original, $class);
            }
        });

        self::$registered = true;
    }

    private static function generate(string $original, string $proxyClass): void
    {
        if (!class_exists($original)) {
            throw new \RuntimeException("Cannot create proxy: original class {$original} does not exist.");
        }

        if (!class_exists($proxyClass)) {
            eval("
                class $proxyClass extends \\$original implements \\Michel\\PaperORM\\Proxy\\ProxyInterface {
                    use \\Michel\\PaperORM\\Proxy\\ProxyInitializedTrait;
                }
            ");
        }
    }

    public static function create(string $original): object
    {
        $sanitized = str_replace('\\', '_', $original);
        $proxyClass = 'Proxy_' . $sanitized;

        if (!class_exists($proxyClass)) {
            self::generate($original, $proxyClass);
        }

        return new $proxyClass();
    }
}
