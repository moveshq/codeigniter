<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace CodeIgniter;

use CodeIgniter\Cache\FactoriesCache;
use CodeIgniter\CLI\Console;
use CodeIgniter\Config\DotEnv;
use CodeIgniter\Exceptions\FrameworkException;
use Config\Autoload;
use Config\Cache;
use Config\Modules;
use Config\Paths;
use Config\Services;

/**
 * Bootstrap for the application
 *
 * @codeCoverageIgnore
 */
class Boot
{
    /**
     * @used-by public/index.php
     *
     * Context
     *   web:     Invoked by HTTP request
     *   php-cli: Invoked by CLI via `php public/index.php`
     *
     * @return int Exit code.
     */
    public static function bootWeb(Paths $paths): int
    {
        static::loadDotEnv($paths);
        static::defineEnvironment();
        static::loadEnvironmentBootstrap($paths);
        static::definePathConstants($paths);
        if (! defined('APP_NAMESPACE')) {
            static::loadConstants();
        }
        static::loadCommonFunctions();
        static::loadAutoloader();
        static::setExceptionHandler();
        static::checkMissingExtensions();
        static::initializeKint();

        $configCacheEnabled = (new Cache())->configCacheEnabled ?? false;
        if ($configCacheEnabled) {
            $factoriesCache = static::loadConfigCache();
        }

        $app = static::initializeCodeIgniter();
        static::runCodeIgniter($app);

        if ($configCacheEnabled) {
            static::saveConfigCache($factoriesCache);
        }

        // Exits the application, setting the exit code for CLI-based
        // applications that might be watching.
        return EXIT_SUCCESS;
    }

    /**
     * @used-by spark
     *
     * @return int Exit code.
     */
    public static function bootSpark(Paths $paths): int
    {
        static::loadDotEnv($paths);
        static::defineEnvironment();
        static::loadEnvironmentBootstrap($paths);
        static::definePathConstants($paths);
        if (! defined('APP_NAMESPACE')) {
            static::loadConstants();
        }
        static::loadCommonFunctions();
        static::loadAutoloader();
        static::setExceptionHandler();
        static::checkMissingExtensions();
        static::initializeKint();

        static::initializeCodeIgniter();
        $console = static::initializeConsole();

        return static::runCommand($console);
    }

    /**
     * @used-by system/Test/bootstrap.php
     */
    public static function bootTest(Paths $paths): void
    {
        static::loadDotEnv($paths);
        static::loadEnvironmentBootstrap($paths, false);
        static::loadConstants();
        static::loadCommonFunctions();
        static::loadAutoloader();
        static::setExceptionHandler();
        static::checkMissingExtensions();
        static::initializeKint();
    }

    /**
     * Load environment settings from .env files into $_SERVER and $_ENV
     */
    protected static function loadDotEnv(Paths $paths): void
    {
        require_once $paths->systemDirectory . '/Config/DotEnv.php';
        (new DotEnv($paths->appDirectory . '/../'))->load();
    }

    protected static function defineEnvironment(): void
    {
        if (! defined('ENVIRONMENT')) {
            // @phpstan-ignore-next-line
            $env = $_ENV['CI_ENVIRONMENT'] ?? $_SERVER['CI_ENVIRONMENT'] ?? getenv('CI_ENVIRONMENT');

            define('ENVIRONMENT', ($env !== false) ? $env : 'production');
            unset($env);
        }
    }

    protected static function loadEnvironmentBootstrap(Paths $paths, bool $exit = true): void
    {
        if (is_file($paths->appDirectory . '/Config/Boot/' . ENVIRONMENT . '.php')) {
            require_once $paths->appDirectory . '/Config/Boot/' . ENVIRONMENT . '.php';

            return;
        }

        if ($exit) {
            header('HTTP/1.1 503 Service Unavailable.', true, 503);
            echo 'The application environment is not set correctly.';

            exit(EXIT_ERROR);
        }
    }

    /**
     * The path constants provide convenient access to the folders throughout
     * the application. We have to set them up here, so they are available in
     * the config files that are loaded.
     */
    protected static function definePathConstants(Paths $paths): void
    {
        // The path to the application directory.
        if (! defined('APPPATH')) {
            define('APPPATH', realpath(rtrim($paths->appDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
        }

        // The path to the project root directory. Just above APPPATH.
        if (! defined('ROOTPATH')) {
            define('ROOTPATH', realpath(APPPATH . '../') . DIRECTORY_SEPARATOR);
        }

        // The path to the system directory.
        if (! defined('SYSTEMPATH')) {
            define('SYSTEMPATH', realpath(rtrim($paths->systemDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
        }

        // The path to the writable directory.
        if (! defined('WRITEPATH')) {
            define('WRITEPATH', realpath(rtrim($paths->writableDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
        }

        // The path to the tests directory
        if (! defined('TESTPATH')) {
            define('TESTPATH', realpath(rtrim($paths->testsDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
        }
    }

    protected static function loadConstants(): void
    {
        require_once APPPATH . 'Config/Constants.php';
    }

    protected static function loadCommonFunctions(): void
    {
        // Require app/Common.php file if exists.
        if (is_file(APPPATH . 'Common.php')) {
            require_once APPPATH . 'Common.php';
        }

        // Require system/Common.php
        require_once SYSTEMPATH . 'Common.php';
    }

    /**
     * The autoloader allows all the pieces to work together in the framework.
     * We have to load it here, though, so that the config files can use the
     * path constants.
     */
    protected static function loadAutoloader(): void
    {
        if (! class_exists(Autoload::class, false)) {
            require_once SYSTEMPATH . 'Config/AutoloadConfig.php';
            require_once APPPATH . 'Config/Autoload.php';
            require_once SYSTEMPATH . 'Modules/Modules.php';
            require_once APPPATH . 'Config/Modules.php';
        }

        require_once SYSTEMPATH . 'Autoloader/Autoloader.php';
        require_once SYSTEMPATH . 'Config/BaseService.php';
        require_once SYSTEMPATH . 'Config/Services.php';
        require_once APPPATH . 'Config/Services.php';

        // Initialize and register the loader with the SPL autoloader stack.
        Services::autoloader()->initialize(new Autoload(), new Modules())->register();
        Services::autoloader()->loadHelpers();
    }

    protected static function setExceptionHandler(): void
    {
        Services::exceptions()->initialize();
    }

    protected static function checkMissingExtensions(): void
    {
        // Run this check for manual installations
        if (! is_file(COMPOSER_PATH)) {
            $missingExtensions = [];

            foreach ([
                'intl',
                'json',
                'mbstring',
            ] as $extension) {
                if (! extension_loaded($extension)) {
                    $missingExtensions[] = $extension;
                }
            }

            if ($missingExtensions !== []) {
                throw FrameworkException::forMissingExtension(implode(', ', $missingExtensions));
            }

            unset($missingExtensions);
        }
    }

    protected static function initializeKint(): void
    {
        Services::autoloader()->initializeKint(CI_DEBUG);
    }

    protected static function loadConfigCache(): FactoriesCache
    {
        $factoriesCache = new FactoriesCache();
        $factoriesCache->load('config');

        return $factoriesCache;
    }

    /**
     * The CodeIgniter class contains the core functionality to make
     * the application run, and does all the dirty work to get
     * the pieces all working together.
     */
    protected static function initializeCodeIgniter(): CodeIgniter
    {
        $app = Config\Services::codeigniter();
        $app->initialize();
        $context = is_cli() ? 'php-cli' : 'web';
        $app->setContext($context);

        return $app;
    }

    /**
     * Now that everything is set up, it's time to actually fire
     * up the engines and make this app do its thang.
     */
    protected static function runCodeIgniter(CodeIgniter $app): void
    {
        $app->run();
    }

    protected static function saveConfigCache(FactoriesCache $factoriesCache): void
    {
        $factoriesCache->save('config');
    }

    protected static function initializeConsole(): Console
    {
        $console = new Console();

        // Show basic information before we do anything else.
        // @phpstan-ignore-next-line
        if (is_int($suppress = array_search('--no-header', $_SERVER['argv'], true))) {
            unset($_SERVER['argv'][$suppress]); // @phpstan-ignore-line
            $suppress = true;
        }

        $console->showHeader($suppress);

        return $console;
    }

    protected static function runCommand(Console $console): int
    {
        $exit = $console->run();

        return is_int($exit) ? $exit : EXIT_SUCCESS;
    }
}
