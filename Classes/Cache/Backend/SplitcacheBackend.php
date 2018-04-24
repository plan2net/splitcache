<?php
/*
 * (c) 2018 Oliver Gassner <og@plan2.net>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace Plan2net\Splitcache\Cache\Backend;

use TYPO3\CMS\Core\Cache\Backend\AbstractBackend;
use TYPO3\CMS\Core\Cache\Backend\BackendInterface;
use TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface;

class SplitcacheBackend extends AbstractBackend implements TaggableBackendInterface
{

    /**
     * @var bool Indicates whether data is compressed or not (requires php zlib)
     */
    protected $compression = false;

    /**
     * @var int -1 to 9, indicates zlib compression level: -1 = default level 6, 0 = no compression, 9 maximum compression
     */
    protected $compressionLevel = -1;

    /**
     * @var int[]
     */
    protected $levels = [];

    /**
     * @var int
     */
    protected $maxLevel = 0;

    /**
     * @var int
     */
    protected $maxLifetime = -1;

    /**
     * @var BackendInterface[]
     */
    protected $backends = [];

    /**
     * @var int[]
     */
    protected $maxLifetimes = [];

    /**
     * @var int
     */
    protected $tempCacheContentWorkaround = 0;

    /**
     * Enable data compression
     *
     * @param bool $compression TRUE to enable compression
     */
    public function setCompression($compression)
    {
        $this->compression = $compression;
    }

    /**
     * Set data compression level.
     * If compression is enabled and this is not set,
     * gzcompress default level will be used
     *
     * @param int -1 to 9: Compression level
     */
    public function setCompressionLevel($compressionLevel)
    {
        if ($compressionLevel >= -1 && $compressionLevel <= 9) {
            $this->compressionLevel = $compressionLevel;
        }
    }

    /**
     * Saves data in the cache.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data            The data to be stored
     * @param array  $tags            Tags to associate with this cache entry. If the backend does not support tags, this option can be ignored.
     * @param int    $lifetime        Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @throws \TYPO3\CMS\Core\Cache\Exception if no cache frontend has been set.
     * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException if the data is not a string
     * @api
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        if (!$lifetime) {
            $lifetime = $this->defaultLifetime;
        }
        $backendKey = $this->getBackendKeyForLifetime($lifetime);
        if ($this->tempCacheContentWorkaround && ($backendKey > 0)) {
            $this->removeFromFasterCaches($entryIdentifier, $backendKey);
        }
        $this->backends[$backendKey]->set($entryIdentifier, $data, $tags, $lifetime);
    }

    /**
     * @param int $lifetime
     * @return BackendInterface
     */
    protected function getBackendKeyForLifetime($lifetime)
    {
        foreach ($this->maxLifetimes as $key => $maxLifetime) {
            if (($maxLifetime >= $lifetime) || ($key == $this->maxLevel)) {
                return $key;
            }
        }
    }

    /**
     * Loads data from the cache.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
     * @api
     */
    public function get($entryIdentifier)
    {
        foreach ($this->backends as $key => $backend) {
            if ($entry = $backend->get($entryIdentifier)) {
                //echo "<pre>$entryIdentifier found in [$key]".get_class($backend)."</pre>";
                return $entry;
            }
        }

        return false;
    }

    public function setCache(\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache)
    {
        parent::setCache($cache);
        foreach ($this->backends as $key => $backend) {
            $this->backends[$key]->setCache($this->cache);
        }
    }

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return bool TRUE if such an entry exists, FALSE if not
     * @api
     */
    public function has($entryIdentifier)
    {
        foreach ($this->backends as $key => $backend) {
            if ($backend->get($entryIdentifier)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry but if - for what reason ever -
     * old entries for the identifier still exist, they are removed as well.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return bool TRUE if (at least) an entry could be removed or FALSE if no entry was found
     * @api
     */
    public function remove($entryIdentifier)
    {
        foreach ($this->backends as $key => $backend) {
            if ($backend->get($entryIdentifier)) {
                $backend->remove($entryIdentifier);

                return true;
            }
        }

        return false;
    }

    /**
     * @param string $entryIdentifier
     * @param int    $targetLevel
     */
    protected function removeFromFasterCaches($entryIdentifier, $targetLevel)
    {
        for ($i = 0; $i < $targetLevel; $i++) {
            $this->backends[$i]->remove($entryIdentifier);
        }
    }

    /**
     * Removes all cache entries of this cache.
     *
     * @api
     */
    public function flush()
    {
        foreach ($this->backends as $key => $backend) {
            $backend->flush();
        }
    }

    /**
     * Does garbage collection
     *
     * @api
     */
    public function collectGarbage()
    {
        // TODO: Implement collectGarbage() method.
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     * @api
     */
    public function flushByTag($tag)
    {
        // TODO: Implement flushByTag() method.
    }

    /**
     * Finds and returns all cache entry identifiers which are tagged by the
     * specified tag
     *
     * @param string $tag The tag to search for
     * @return array An array with identifiers of all matching entries. An empty array if no entries matched
     * @api
     */
    public function findIdentifiersByTag($tag)
    {
        // TODO: Implement findIdentifiersByTag() method.
    }

    /**
     * @return array
     */
    public function getLevels()
    {
        return $this->levels;
    }

    /**
     * @param array $levels
     */
    public function setLevels($levels)
    {
        $this->levels = $levels;
    }

    public function __construct($context, array $options = [])
    {
        parent::__construct($context, $options);
        if (is_array($this->levels) || $this->levels instanceof \ArrayAccess) {
            foreach ($this->levels as $key => $level) {
                $levelLivetime = \TYPO3\CMS\Core\Cache\Backend\AbstractBackend::UNLIMITED_LIFETIME;
                if (isset($level['options']['maxLifetime'])) {
                    $levelLivetime = intval($level['options']['maxLifetime']);
                    unset($level['options']['maxLifetime']);
                }
                if (($levelLivetime != \TYPO3\CMS\Core\Cache\Backend\AbstractBackend::UNLIMITED_LIFETIME) && ($levelLivetime <= $this->maxLifetime)) {
                    $levelLivetime = $this->maxLifetime + 1;
                }
                $this->maxLifetimes[$key] = $levelLivetime;
                $this->maxLifetime = $levelLivetime;

                //set compression & compressionlevel to child-caches if only set in parent
                foreach (['compression', 'compressionLevel'] as $optionName) {
                    if (isset($options[$optionName]) && !isset($level['options'][$optionName])) {
                        if ($level['backend'] != 'TYPO3\\CMS\\Core\\Cache\\Backend\\FileBackend') {//no compression in FileBackend
                            $level['options'][$optionName] = $options[$optionName];
                        }
                    }
                }

                $backendOptions = $level['options'];
                $backend = '\\'.ltrim($level['backend'], '\\');

                /** @var BackendInterface $backendInstance */
                $backendInstance = new $backend('production', $backendOptions);
                if (!$backendInstance instanceof BackendInterface) {
                    throw new InvalidBackendException('"'.$backend.'" is not a valid cache backend object.', 1464550977);
                }
                if (is_callable([$backendInstance, 'initializeObject'])) {
                    $backendInstance->initializeObject();
                }

                $this->backends[$key] = $backendInstance;
            }
            $this->maxLevel = max(array_keys($this->levels));
        }
    }

    /**
     * @return int
     */
    public function getTempCacheContentWorkaround(): int
    {
        return $this->tempCacheContentWorkaround;
    }

    /**
     * @param int $tempCacheContentWorkaround
     */
    public function setTempCacheContentWorkaround(int $tempCacheContentWorkaround)
    {
        $this->tempCacheContentWorkaround = $tempCacheContentWorkaround;
    }
}
