<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Cache;

use Behat\Gherkin\Node\FeatureNode;

/**
 * Memory cache.
 * Caches feature into a memory.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class MemoryCache implements CacheInterface
{
    /**
     * @var array<string, FeatureNode>
     */
    private array $features = [];
    /**
     * @var array<string, positive-int>
     */
    private array $timestamps = [];

    public function isFresh($path, $timestamp)
    {
        if (!isset($this->features[$path])) {
            return false;
        }

        return $this->timestamps[$path] > $timestamp;
    }

    public function read($path)
    {
        return $this->features[$path];
    }

    public function write($path, FeatureNode $feature)
    {
        $this->features[$path] = $feature;
        $this->timestamps[$path] = time();
    }
}
