<?php

namespace CodeSoup\Pumpkin\Core;

// Exit if accessed directly
defined('WPINC') || die;

class PageConfig
{
    private static $instance;
    /** @var array<string, mixed> */
    private static $config;

    private function __construct()
    {
        self::$config = array(
            'SHOW_NAVBAR'       => true,
            'SHOW_NAVIGATION'   => true,
            'SHOW_FOOTER'       => true,
            'LOGO_STYLE'        => 'default',
            'NAVBAR_DARK'       => false,
            'NAVBAR_STICKY'     => true,
            'ENABLE_JQUERY'     => true,
            'IOS_THEME_COLOR'   => '',
        );
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(?string $key = null): mixed
    {
        if ($key)
        {
            $key = strtoupper($key);
            return isset(self::$config[$key])
                ? self::$config[$key]
                : null;
        }

        return self::$config;
    }

    public function set(string|array $key, mixed $value = null): void
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                self::$config[$k] = $v;
            }
        } else {
            self::$config[$key] = $value;
        }
    }
    
    /**
     * Static method to get configuration
     *
     * @param string|null $key The configuration key to retrieve
     * @return mixed The configuration value or all config
     */
    public static function getConfig(?string $key = null): mixed
    {
        $instance = self::getInstance();
        return $instance->get($key);
    }
    
    /**
     * Static method to set configuration
     *
     * @param string|array $key The configuration key or array of keys and values
     * @param mixed|null $value The value to set if key is a string
     * @return void
     */
    public static function setConfig(string|array $key, mixed $value = null): void
    {
        $instance = self::getInstance();
        $instance->set($key, $value);
    }
}