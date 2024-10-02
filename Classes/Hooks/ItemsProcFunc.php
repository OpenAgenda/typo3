<?php
namespace Openagenda\Openagenda\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

class ItemsProcFunc
{
    /**
     * Itemsproc function to extend the selection of templateLayouts in the plugin
     *
     * @param array &$config configuration array
     */
    public function user_templateLayout(array &$config)
    {
        
        $pageId = 0;

        $currentColPos = $config['flexParentDatabaseRow']['colPos'] ?? null;
        if ($currentColPos === null) {
            return;
        }
        $pageId = $this->getPageId($config['flexParentDatabaseRow']['pid']);

        $templateLayouts = [];
        $pagesTsConfig = BackendUtility::getPagesTSconfig($pageId);
        if (isset($pagesTsConfig['tx_openagenda.']['templateLayouts.']) && is_array($pagesTsConfig['tx_news.']['templateLayouts.'])) {
            $templateLayouts = $pagesTsConfig['tx_openagenda.']['templateLayouts.'];
        }
        
        if (!empty($templateLayouts)) {
            foreach ($templateLayouts as $key => $label) {
                $additionalLayout = [$label, $key];
                array_push($config['items'], $additionalLayout);
            }
        }
    }

        /**
     * Get page id, if negative, then it is a "after record"
     *
     * @param int $pid
     * @return int
     */
    protected function getPageId($pid): int
    {
        $pid = (int)$pid;

        if ($pid > 0) {
            return $pid;
        }

        $row = BackendUtility::getRecord('tt_content', abs($pid), 'uid,pid');
        return $row['pid'];
    }
}