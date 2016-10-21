# Symfony Google App Engine Integration

This library helps to deploy Symfony applications on Google App Engine.

## Configure Kernel

To integrate Google App Engine with your application, you must extend GaeKernel instead Symfony one.

    use Jhg\SymfonyGaeIntegration\HttpKernel\GaeKernel;
    
    /**
     * Class AppKernel
     */
    class AppKernel extends GaeKernel
    {
        /**
         * @return array
         */
        public function registerBundles()
        {
            ...
        }
    }

Main GaeKernel methods are getCacheDir() and getLogDir(). You should not override those methods to get
 provided feature. In other case, your Kernel determines how to use cache.
 
## GAE Functions

**gae_get_version_suffix()**

**gae_get_bucket_name()**

**gae_on_app_engine()**

**gae_on_dev_app_server()**


## Gcloud admin utils

You can import utils sets in your yaml files, to provide some useful tools in your
 Google App Engine deploy.

    # app.yaml
    includes:
      - vendor/javihgil/symfony-gae-integration/utils/utils-routes.yaml

Of course, those utils are available only for administrators, 
 so that it's restricted with "login: admin" GAE configuration option.

**phpinfo**

You can enter /_sf/phpinfo to view environment information. 
 
**symfony config.php**

You can enter /_sf/config to view *web/config.php*. 
 
**cache clear**
 
You can enter /_sf/cache-clear to execute *console cache:clear --no-warmup --env=prod*. 
 
**cache warmup**
 
You can enter /_sf/cache-warmup to execute *console cache:warm --env=prod*. 


