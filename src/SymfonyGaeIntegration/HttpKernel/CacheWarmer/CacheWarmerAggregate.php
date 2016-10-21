<?php

namespace Jhg\SymfonyGaeIntegration\HttpKernel\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate as OriginalCacheWarmerAggregate;

/**
 * Class CacheWarmerAggregate
 */
class CacheWarmerAggregate extends OriginalCacheWarmerAggregate
{
    /**
     * @var null|string
     */
    protected $compiledDir;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * CacheWarmerAggregate constructor.
     *
     * @param array       $warmers
     * @param string|null $compiledDir
     * @param bool        $debug
     */
    public function __construct(array $warmers = array(), $compiledDir = null, $debug = false)
    {
        parent::__construct($warmers);

        $this->compiledDir = $compiledDir;
        $this->debug = $debug;
    }

    /**
     * @param string $cacheDir
     */
    public function warmUp($cacheDir)
    {
        foreach ($this->warmers as $warmer) {
            if (!$this->optionalsEnabled && $warmer->isOptional()) {
                continue;
            }

            switch (get_class($warmer)) {
                case 'Symfony\Bundle\FrameworkBundle\CacheWarmer\ClassCacheCacheWarmer':
                case 'Symfony\Bundle\FrameworkBundle\CacheWarmer\TemplatePathsCacheWarmer':
                    $warmer->warmUp($this->compiledDir && !$this->debug ? $this->compiledDir : $cacheDir);
                    break;

                default:
                    $warmer->warmUp($cacheDir);
            }
        }
    }
}
