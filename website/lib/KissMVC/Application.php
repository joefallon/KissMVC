<?php declare(strict_types=1);
/**
 * Copyright (c) 2015-2025 Joseph Fallon <joseph.t.fallon@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace KissMVC;

use RuntimeException;

/**
 * Lightweight application bootstrapper for KissMVC.
 */
class Application
{
    /**
     * Registry: application configuration and shared objects.
     */
    protected static ?array $config = null;

    /**
     * Load a PHP configuration file.
     *
     * The file can either return an array or assign an array to $config.
     * New values are merged into the existing registry so later loads override
     * earlier keys.
     */
    public static function loadConfiguration(string $configFilePath): void
    {
        unset($config);

        $returned = require $configFilePath;

        if(is_array($returned))
        {
            $newConfig = $returned;
        }
        elseif(isset($config) && is_array($config))
        {
            $newConfig = $config;
        }
        else
        {
            $msg = 'Configuration file "%s" must return an array or assign $config';
            throw new RuntimeException(sprintf($msg, $configFilePath));
        }

        if(self::$config !== null)
        {
            self::$config = array_merge(self::$config, $newConfig);
        }
        else
        {
            self::$config = $newConfig;
        }

        unset($returned, $newConfig, $config);
    }

    /**
     * Get a value from the registry. Returns null when the key is missing.
     *
     * @return mixed|null
     */
    public static function getRegistryItem(string $registryItemName): mixed
    {
        return self::$config[$registryItemName] ?? null;
    }

    /**
     * Store a value in the registry under the given name.
     *
     * @param mixed $registryItem
     */
    public static function setRegistryItem(string $registryItemName, mixed $registryItem): void
    {
        if(self::$config === null)
        {
            self::$config = [];
        }

        self::$config[$registryItemName] = $registryItem;
    }

    /**
     * Run the application.
     */
    public static function run(ApplicationRunnerOptions|ApplicationBuilder|null $options = null): void
    {
        if($options instanceof ApplicationBuilder)
        {
            $runner = $options->build();
            $runner->run();

            return;
        }

        $runner = new ApplicationRunner($options);
        $runner->run();
    }
}
