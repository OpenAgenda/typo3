<?php

namespace Openagenda\Openagenda\Utility;

/**
 * This file is part of the "openagenda" Extension for TYPO3 CMS.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * OpenagendaAgendaProcessorUtility class
 */
class OpenagendaAgendaProcessorUtility implements OpenagendaAgendaProcessorUtilityInterface
{
    /**
     * UriBuilder
     *
     * @var UriBuilder
     */
    protected UriBuilder $uriBuilder;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Config
     *
     * @var array
     */
    private array $config;

    /**
     * OpenagendaHelperUtility
     *
     * @var OpenagendaHelperUtility
     */
    private OpenagendaHelperUtility $openagendaHelper;

    /**
     * OpenagendaConnectorUtility
     *
     * @var OpenagendaConnectorUtility
     */
    private OpenagendaConnectorUtility $openagendaConnector;

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $backendConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->get('openagenda');
        $this->config['publicKey'] = $backendConfiguration['public_key'];
        $this->config['includeEmbedded'] = $backendConfiguration['include_embedded'];
        $this->config['current'] = $backendConfiguration['current'];
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->uriBuilder->setRequest($this->getExtbaseRequest());
        $this->openagendaHelper = new OpenagendaHelperUtility($this->logger);
        $this->openagendaConnector = new OpenagendaConnectorUtility($this->logger);
    }

    /**
     * Build an agenda's render array.
     *
     * @param $calendarUid
     *   Calendar Uid.
     *
     * @param $event
     *   An entity with a field_openagenda attached to it.
     *
     * @param bool|null $ajax
     *   Wether it is an ajax or not.
     *
     * @param int|null $page
     *   Wether it is an ajax or not.
     *
     * @return array
     *   The render array.
     */
    public function buildRenderArray($calendarUid, $event, ?bool $ajax = FALSE, ?int $page = NULL, $language = 'default', $eventsPerPage = 20, $columns = 2, $preFilter = null): array
    {
        // Get request parameters : page.
        $size = (int) $eventsPerPage;
        $from = $page ? ($page - 1) * $size : 0;
        $preFilters = null;

        // Get request filters.
        $request = $GLOBALS['TYPO3_REQUEST'];
        $normalizedParams = $request->getAttribute('normalizedParams');
        parse_str($normalizedParams->getQueryString(), $queryInfo);

        $filters = $queryInfo;
        $filters += ['detailed' => 1];

        // Remove pager params.
        unset($filters['page']);
        unset($filters['type']);

        // Get pre-filters and add them to filters if defined.
        $preFilters = $this->openagendaHelper->getPreFilters($preFilter);

        // Current & upcoming events only.
        $currentValue = $this->config['current'];
        if (!empty($currentValue)) {
            $preFilters['relative'] = [
                'current',
                'upcoming',
            ];
        }
        $filters += $preFilters;

        // Get events.
        $data = $this->openagendaConnector->getAgendaEvents($calendarUid, $filters, $from, $size, NULL, (bool) $this->config['includeEmbedded']);

        // Security if page is higher than page count.
        $total = !empty($data['total']) ? $data['total'] : 0;
        if ($from > $total) {
            $page = floor(($total - 1) / $size) + 1;

            return $this->buildRenderArray($event, FALSE, $page);
        }

        // Localize events.
        $events = $data['events'] ?? [];
        $lang = $language;
        foreach ($events as &$event) {
            $this->openagendaHelper->localizeEvent($event, $lang);
        }

        return [
            'theme' => 'openagenda_agenda',
            'event' => $event,
            'events' => !empty($events) ? $events : [],
            'total' => $total,
            'from' => $from,
            'lang' => $language,
            'columns' => $columns,
            'filters' => $filters,
            'ajax' => $ajax,
        ];
    }

    private function getExtbaseRequest(): RequestInterface
    {
        /** @var ServerRequestInterface $request */
        $request = $GLOBALS['TYPO3_REQUEST'];

        // We have to provide an Extbase request object
        return new Request(
            $request->withAttribute('extbase', new ExtbaseRequestParameters())
        );
    }
}
