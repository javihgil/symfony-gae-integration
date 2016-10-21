<?php

namespace Jhg\SymfonyGaeIntegration\HttpKernel;

use Jhg\SymfonyGaeIntegration\DependencyInjection\CompilerPass\OverrideCacheWarmerCompilerPass;
use Symfony\Component\ClassLoader\ClassCollectionLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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

            case 'file':
                return $this->getFileCachePath();

            default:
                throw new \Exception(sprintf('"%s" is not a valid GAE cache type. Valid options: gcs, file', $cacheType));
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
     * @return string
     */
    public function getCompiledDir()
    {
        return $this->getRootDir().'/../var/compiled';
    }

    /**
     * @param LoaderInterface $loader
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $this->checkConstructed();

        if (gae_on_app_engine()) {
            $loader->load($this->getRootDir().'/../app/config/config_'.$this->getEnvironment().'_gae.yml');
        } elseif ($this->console) {
            $loader->load($this->getRootDir().'/../app/config/config_'.$this->getEnvironment().'_console.yml');
        } else {
            $loader->load($this->getRootDir().'/../app/config/config_'.$this->getEnvironment().'.yml');
        }
    }

    /**
     * Used internally
     *
     * @param array $classes
     */
    public function setClassCache(array $classes)
    {
        if ($this->isDebug()) {
            parent::setClassCache($classes);

            return;
        }

        file_put_contents($this->getCompiledDir().'/classes.map', sprintf('<?php return %s;', var_export($classes, true)));
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
     * @param string $name
     * @param string $extension
     */
    protected function doLoadClassCache($name, $extension)
    {
        if ($this->isDebug()) {
            parent::doLoadClassCache($name, $extension);

            return;
        }

        if (!$this->booted && is_file($this->getCompiledDir().'/classes.map')) {
            ClassCollectionLoader::load(include($this->getCompiledDir().'/classes.map'), $this->getCompiledDir(), $name, $this->debug, false, $extension);
        }
    }

    /**
     * Returns the kernel parameters.
     *
     * @return array An array of kernel parameters
     */
    protected function getKernelParameters()
    {
        $kernelParameters = parent::getKernelParameters();
        $kernelParameters['kernel.compiled_dir'] = realpath($this->getCompiledDir()) ?: $this->getCompiledDir();

        return $kernelParameters;
    }

    /**
     * @return ContainerBuilder
     */
    protected function buildContainer()
    {
        $container = parent::buildContainer();

        $container->addCompilerPass(new OverrideCacheWarmerCompilerPass($this));

        return $container;
    }

    /**
     * Check if constructor was called
     */
    private function checkConstructed()
    {
        if (!$this->constructed) {
            throw new \Exception('Your AppKernel extends from GaeKernel, so you must call parent::__construct()');
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
}
