<?php

namespace Jhg\SymfonyGaeIntegration\HttpKernel;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class GaeKernel
 */
abstract class GaeKernel extends Kernel
{
    /**
     * This server bag is used to obtain some environment variables, using specified default values
     *
     * @var ServerBag
     */
    protected $server;

    /**
     * @var boolean
     */
    private $constructed = false;

    /**
     * @var boolean
     */
    protected $console;

    /**
     * {@inheritdoc}
     */
    public function __construct($environment, $debug, $console = false)
    {
        $this->constructed = true;
        $this->server = new ServerBag($_SERVER);
        $this->console = $console;

        parent::__construct($environment, $debug);

        // Symfony console requires timezone to be set manually.
        if (gae_on_app_engine() && !ini_get('date.timezone')) {
            date_default_timezone_set($this->getEnv('DEFAULT_TIMEZONE', 'UTC'));
        }

        // configure gs stream on app engine environment
        if ((gae_on_app_engine() || gae_on_dev_app_server()) && $bucketName = gae_get_bucket_name()) {
            // Enable optimistic caching for GCS.
            $options = [
                'gs' => [
                    'enable_cache'              => $this->getEnv('GCS_STREAM_ENABLE_CACHE', true),
                    'enable_optimistic_cache'   => $this->getEnv('GCS_STREAM_ENABLE_OPTIMISTIC_CACHE', true),
                    'read_cache_expiry_seconds' => $this->getEnv('GCS_STREAM_READ_CACHE_EXPIRY_SECONDS', 300),
                ],
            ];
            stream_context_set_default($options);
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getCacheDir()
    {
        $this->checkConstructed();

        $cacheType = $this->getEnv('SF_CACHE_TYPE', 'file');

        switch ($cacheType) {
            case 'gcs':
                return $this->getGcsCachePath();

            case 'memcached':
                return $this->getMemcachedCachePath();

            case 'file':
                return $this->getFileCachePath();

            default:
                throw new \Exception(sprintf('"%s" is not a valid GAE cache type. Valid options: gcs, memcached, file', $cacheType));
        }
    }

    /**
     * @return string
     */
    public function getLogDir()
    {
        $this->checkConstructed();

        if (gae_on_app_engine() && $bucketName = gae_get_bucket_name()) {
            return sprintf('gs://%s/symfony/%s/log', $bucketName, gae_get_version_suffix());
        }

        return $this->getRootDir().'/../var/logs';
    }

    /**
     * @param LoaderInterface $loader
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        if (gae_on_app_engine()) {
            $loader->load($this->getRootDir().'/../app/config/config_'.$this->getEnvironment().'_gae.yml');
        } elseif ($this->console) {
            $loader->load($this->getRootDir().'/../app/config/config_'.$this->getEnvironment().'_console.yml');
        } else {
            $loader->load($this->getRootDir().'/../app/config/config_'.$this->getEnvironment().'.yml');
        }
    }

    /**
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    protected function getEnv($key, $default = null)
    {
        return $this->server->get($key, $default);
    }

    /**
     * Check if constructor was called
     */
    private function checkConstructed()
    {
        if (!$this->constructed) {
            throw new \Exception('Your AppKernel extends from GaeKernel, so must call parent::__construct()');
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getGcsCachePath()
    {
        if (! $bucketName = gae_get_bucket_name()) {
            throw new \Exception('No bucket name is provided for cache');
        }

        $bucketsNameFromIni = ini_get('google_app_engine.allow_include_gs_buckets');

        if (in_array($bucketName, explode(',', $bucketsNameFromIni))) {
            throw new \Exception('Google App Engine PHP runtime requires that bucket name is included in php.ini in "google_app_engine.allow_include_gs_buckets" directive.');
        }

        if (gae_on_app_engine()) {
            return sprintf('gs://%s/symfony/%s/cache', $bucketName, gae_get_version_suffix());
        } elseif (gae_on_dev_app_server()) {
            return sprintf('gs://%s/symfony/%s/cache', $bucketName, gae_get_version_suffix());
        }

        throw new \Exception('No development or AppEngine environment detected');
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getFileCachePath()
    {
        if (gae_on_app_engine()) {
            throw new \Exception('AppEngine has read only filesystem, can not use cache on files');
        }

        return $this->getRootDir().'/../var/cache/'.$this->getEnvironment();
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getMemcachedCachePath()
    {
        $this->registerMemcachedStreamWrapper();

        $versionSuffix = gae_get_version_suffix();

        return sprintf('memcached://symfony/%s/cache', $versionSuffix ? $versionSuffix : 'dev');
    }

    /**
     * Loads memcached stream wrapper
     *
     * @throws \Exception
     */
    private function registerMemcachedStreamWrapper()
    {
        if (!function_exists('getmyuid')) {
            throw new \Exception('You must activate getmyuid to allow using MemcacheStreamWrapper. You can do this including function name in google_app_engine.enable_functions directive.');
        }

        if (!class_exists('Memcached')) {
            throw new \Exception('Memcached extension is not enabled.');
        }

        if (!in_array('memcached', stream_get_wrappers())) {
            stream_wrapper_register('memcached', 'Jhg\SymfonyGaeIntegration\StreamWrapper\MemcacheStreamWrapper');
        }
    }
}
