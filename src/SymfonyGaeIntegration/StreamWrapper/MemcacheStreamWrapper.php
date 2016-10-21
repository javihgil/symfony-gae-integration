<?php

namespace Jhg\SymfonyGaeIntegration\StreamWrapper;

use \MemcacheStreamWrapper as BaseMemcacheStreamWrapper;

/**
 * Class MemcacheStreamWrapper
 */
class MemcacheStreamWrapper extends BaseMemcacheStreamWrapper implements StreamWrapperInterface
{
//    /**
//     * MemcacheStreamWrapper constructor.
//     */
//    public function __construct()
//    {
//        $memcache = new \Memcache();
//        parent::__construct($memcache);
//    }


//    /**
//     * FIX fail on memcached rename($from, $to);
//     *
//     * @param string $from
//     * @param string $to
//     *
//     * @return bool
//     */
//    public function rename($from, $to)
//    {
//        file_put_contents($to, file_get_contents($from));
//        unlink($from);
//
//        return true;
//    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function unlink($path)
    {
        $key = $this->createKeyFromPath($path);
        $this->removeStatCache($key);
        $this->memcache->delete($key);

        return true;
    }

    public function stream_cast($cast_as)
    {
        // TODO: Implement stream_cast() method.
    }

    public function stream_close()
    {
        // TODO: Implement stream_close() method.
    }

    public function stream_flush()
    {
        // TODO: Implement stream_flush() method.
    }

    public function stream_set_option($option, $arg1, $arg2)
    {
        // TODO: Implement stream_set_option() method.
    }

    public function stream_truncate($new_size)
    {
        // TODO: Implement stream_truncate() method.
    }

    public function stream_seek($offset, $whence = SEEK_SET)
    {
        return parent::stream_seek($offset, $whence);
    }


}