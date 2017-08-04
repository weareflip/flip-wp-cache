<?php
/*
Plugin Name: Flip Cache
Plugin URI: https://www.weareflip.com/
Description: Customisable Segment Caching Solution
Version: 1.0.0
Author: Andris Trieb
Author URI: https://www.weareflip.com/
Copyright: Flip
Text Domain: flip-cache
*/

class FlipCache
{

    protected $defaultRefresh = 3600;
    protected $indexPath, $cacheIndex;


    public function __construct(){ }

    public function initialise() {

        $defaultCacheDirectory = getenv('FLIPCACHE_DIRECTORY') ? : WP_CONTENT_DIR . '/cache/';
        $this->define('FLIPCACHE_DIRECTORY', $defaultCacheDirectory);
        $this->indexPath = FLIPCACHE_DIRECTORY . 'index.json';

        $this->getIndex();
    }

    private function getIndex()
    {
        $index = [];

        if (file_exists($this->indexPath)) {
            $json = file_get_contents($this->indexPath);
            $index = json_decode($json, TRUE);
        }

        $this->cacheIndex = $index;
    }

    private function updateIndex()
    {
        file_put_contents($this->indexPath, json_encode($this->cacheIndex));
    }


    /**
     * @param string $name
     * @param null $cachedFunction
     * @param int | boot $expire
     * @return string
     */

    public function segment($name = '', $cachedFunction = null, $expire = null)
    {
        if(empty($name) || empty($cachedFunction)) {
            return '';
        }

        if($expire !== false && !is_int($expire)) {
            $expire = $this->defaultRefresh;
        }

        $md5Name = md5($name);
        $cacheTime = 0;
        $location = FLIPCACHE_DIRECTORY . $md5Name . '.html';

        if (isset($this->cacheIndex[$md5Name]) && ( $expire === false || $this->cacheIndex[$md5Name] > time())) {

            if(file_exists($location)) {
                return file_get_contents($location);
            }
        }


        if(file_exists($location)) {
            unlink($location);
        }
        ob_start();

        $cachedFunction();

        $contents = ob_get_clean();

        file_put_contents($location, $contents);

        $this->cacheIndex[$md5Name] = $expire ? time() + $expire : 0;
        $this->updateIndex();

        return $contents;

    }

    public function clear(){
        foreach ($this->cacheIndex as $fileName => $time) {
            $location = FLIPCACHE_DIRECTORY . $fileName . '.html';

            if(file_exists($location)) {
                unlink($location);
            }
        }

        $this->cacheIndex = [];
        $this->updateIndex();
    }

    function define( $name, $value = true ) {

        if( !defined($name) ) define( $name, $value );

    }

}

function flipCache()
{
    global $flipCache;

    if(!isset($flipCache)) {
        $flipCache = new FlipCache();
        $flipCache->initialise();
    }

}

function cacheSegment($name = '', $function = null, $expire = null)
{
    global $flipCache;
    return $flipCache->segment($name, $function, $expire);
}

function theCacheSegment($name = '', $function = null, $expire = null)
{
    global $flipCache;
    echo $flipCache->segment($name, $function, $expire);
}

function clearFlipCache() {
    global $flipCache;
    $flipCache->clear();
}

flipCache();