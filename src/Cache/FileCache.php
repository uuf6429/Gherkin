<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Cache;

use Behat\Gherkin\Exception\CacheException;
use Behat\Gherkin\Node\FeatureNode;
use Composer\InstalledVersions;

/**
 * File cache.
 * Caches feature into a file.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class FileCache implements CacheInterface
{
    private string $path;

    /**
     * Used as part of the cache directory path to invalidate cache if the installed package version changes.
     */
    private static function getGherkinVersionHash(): string
    {
        $version = InstalledVersions::getVersion('behat/gherkin')
            ?? throw new \RuntimeException('Cannot detect behat/gherkin package version');

        // Composer version strings can contain arbitrary content so hash for filesystem safety
        return md5($version);
    }

    /**
     * Initializes file cache.
     *
     * @param string $path path to the folder where to store caches
     *
     * @throws CacheException
     */
    public function __construct($path)
    {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::getGherkinVersionHash();

        if (!is_dir($this->path)) {
            @mkdir($this->path, 0777, true);
        }

        if (!is_writable($this->path)) {
            throw new CacheException(sprintf('Cache path "%s" is not writeable. Check your filesystem permissions or disable Gherkin file cache.', $this->path));
        }
    }

    public function isFresh($path, $timestamp)
    {
        $cachePath = $this->getCachePathFor($path);

        if (!file_exists($cachePath)) {
            return false;
        }

        return filemtime($cachePath) > $timestamp;
    }

    public function read($path)
    {
        $cachePath = $this->getCachePathFor($path);
        $fileData = file_get_contents($cachePath);
        if ($fileData === false) {
            throw new CacheException(sprintf('Can not read cache for a feature "%s" from "%s".', $path, $cachePath));
        }

        $feature = unserialize($fileData);
        if (!$feature instanceof FeatureNode) {
            throw new CacheException(sprintf('Can not load cache for a feature "%s" from "%s".', $path, $cachePath));
        }

        return $feature;
    }

    public function write($path, FeatureNode $feature)
    {
        file_put_contents($this->getCachePathFor($path), serialize($feature));
    }

    /**
     * Returns feature cache file path from features path.
     *
     * @param string $path Feature path
     *
     * @return string
     */
    protected function getCachePathFor($path)
    {
        return $this->path . '/' . md5($path) . '.feature.cache';
    }
}
