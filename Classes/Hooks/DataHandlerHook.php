<?php

declare(strict_types = 1);

namespace B13\Menus\Hooks;

/*
 * This file is part of TYPO3 CMS-based extension "menus" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * This is a helper class and a wrapper around "cache_hash".
 *
 * The pure joy of this class is the get() method, which calculates tags and max lifetime based on the fetched
 * records. If found in cache, fetched directly.
 */
class DataHandlerHook
{
    private const LIMIT_FOR_FLUSH_PAGE_CACHE = 1000;

    /**
     * @var FrontendInterface
     */
    protected $cacheHash;

    /**
     * @var FrontendInterface
     */
    protected $cachePages;

    public function __construct(
        FrontendInterface $cacheHash = null,
        FrontendInterface $cachePages = null
    ) {
        $this->cacheHash = $cacheHash ?? GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_hash');
        $this->cachePages = $cachePages ?? GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_pages');
    }

    /**
     * @param string $status
     * @param string $table
     * @param mixed $id
     * @param array $fieldArray
     * @param DataHandler $dataHandler
     */
    public function processDatamap_afterDatabaseOperations(string $status, string $table, $id, array $fieldArray, DataHandler $dataHandler): void
    {
        if (
            $table === 'pages' &&
            MathUtility::canBeInterpretedAsInteger($id) &&
            is_array($dataHandler->datamap['pages'])
        ) {
            $pageCaches = $this->cachePages->getBackend()->findIdentifiersByTag('menuId_' . $id);
            $menuTags = ['menuId_' . $id];
            $this->cacheHash->flushByTags($menuTags);
            if (count($pageCaches) > self::LIMIT_FOR_FLUSH_PAGE_CACHE) {
                $this->cachePages->flush();
            } else {
                $this->cachePages->flushByTags($menuTags);
            }
        }
    }
}
