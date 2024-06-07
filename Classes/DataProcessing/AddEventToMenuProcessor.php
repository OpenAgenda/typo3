<?php
declare(strict_types=1);

namespace Openagenda\Openagenda\DataProcessing;

use OpenAgendaSdk\OpenAgendaSdk;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Add the current event record to any menu, e.g. breadcrumb
 * Thanks to GeorgRinger\News\DataProcessing\AddNewsToMenuProcessor
 *
 * 20 = Openagenda\Openagenda\DataProcessing\AddEventToMenuProcessor
 * 20.menus = breadcrumbMenu,specialMenu
 */
class AddEventToMenuProcessor implements DataProcessorInterface
{
    /**
     * The content object renderer
     */
    protected ?ContentObjectRenderer $cObj = null;

    /**
     * @param ContentObjectRenderer $cObj
     * @param array $contentObjectConfiguration
     * @param array $processorConfiguration
     * @param array $processedData
     * @return array
     */
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData): array
    {
        $this->cObj = $cObj;

        if (isset($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.'])) {
            return $processedData;
        }
        
        if (!$processorConfiguration['menus']) {
            return $processedData;
        }
        $event = $this->getEvent();
        if ($event) {
            $menus = GeneralUtility::trimExplode(',', $processorConfiguration['menus'], true);
            foreach ($menus as $menu) {
                if (isset($processedData[$menu])) {
                    $this->addEventRecordToMenu($event, $processedData[$menu]);
                }
            }
        }
        return $processedData;
    }

    /**
     * Add the event record to the menu items
     *
     * @param array $event
     * @param array $menu
     */
    protected function addEventRecordToMenu(array $event, array &$menu): void
    {
        foreach ($menu as &$menuItem) {
            $menuItem['current'] = 0;
        }

        // Language ISO
        $language = $this->cObj->getRequest()->getAttribute('language')->getTwoLetterIsoCode();
        $menu[] = [
            'data' => $event,
            'title' => $event['title'][$language],
            'active' => 1,
            'current' => 1,
            'link' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
            'isOpenAgendaEvent' => true,
        ];
    }

    /**
     * Get the event record including possible translations
     * #TODO : manage language ?
     * @return array
     */
    protected function getEvent(): array
    {
        $eventUid = 0;
        $vars = $this->cObj->getRequest()->getQueryParams();
        if (isset($vars['tx_openagenda_agenda']['uid'])) {
            $eventUid = (int)$vars['tx_openagenda_agenda']['uid'];
        }

        if ($eventUid) {
            $backendConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)
                ->get('openagenda');
            $sdk = new OpenAgendaSdk($backendConfiguration['public_key']);
            $context = json_decode(base64_decode($vars['tx_openagenda_agenda']['oac']), true);
            $event = json_decode($sdk->getEvent($context['calendarUid'], $eventUid), true);

            if ($event['success']) {
                return $event['event'];
            }
        }
        return [];
    }
}
