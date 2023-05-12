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

use JetBrains\PhpStorm\ArrayShape;
use OpenAgendaSdk\OpenAgendaSdk;
use Psr\Log\LoggerInterface;
use Throwable;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * OpenagendaConnectorUtility class
 */
class OpenagendaConnectorUtility implements OpenagendaConnectorUtilityInterface
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
     * SDK
     *
     * @var OpenAgendaSdk
     */
    private OpenAgendaSdk $sdk;

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    public function __construct(LoggerInterface $logger)
    {
        $backendConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->get('openagenda');
        $this->config['publicKey'] = $backendConfiguration['public_key'];
        $this->sdk = new OpenAgendaSdk($this->config['publicKey']);
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->logger = $logger;
    }

    /**
     * Get events from the OpenAgenda server.
     *
     * @param string $agenda_uid
     *   The agenda UID.
     * @param array $params
     *   An array of params for OpenAgenda SDK.
     *
     * @return array|mixed
     *   Data from the OpenAgenda server, including an event array.
     */
    protected function getData(string $agenda_uid, array $params = []): mixed
    {
        // Make request.
        try {
            $data = json_decode($this->sdk->getEvents($agenda_uid, $params), true);
            if (isset($data->error)) {
                $this->logger->error($data->error);

                return [];
            }
        }
        catch (Throwable) {
            $this->logger->error($data->error);

            return [];
        }

        return $data;
    }

    /**
     * Get agenda settings.
     *
     * @param string $agenda_uid
     *   The agenda UID.
     *
     * @return array
     *   Data from the Openagenda server representing this agenda's settings.
     */
    public function getAgendaSettings(string $agenda_uid): array
    {
        try {
            $data = json_decode($this->sdk->getAgenda($agenda_uid), true);
            if ($data->error) {
                $this->logger->error($data->error);

                return [];
            }
        }
        catch (Throwable) {
            $this->logger->error($data->error);

            return [];
        }

        return $data;
    }

    /**
     * Get agenda events.
     *
     * @param string $agenda_uid
     *   The agenda UID.
     * @param array $filters
     *   An array of filter parameters.
     * @param int $from
     *   Get events starting from.
     * @param int $size
     *   Number of events to get.
     * @param string|null $sort
     *   Event sort parameter. One of the following values:
     *      - timingsWithFeatured.asc
     *      - timings.asc
     *      - updatedAt.desc
     *      - updatedAt.asc
     * @param bool|null $include_embedded
     *   Wether include embedded code in event html or not.
     *
     * @return array|mixed
     *   Data from the OpenAgenda server, including an event array.
     */
    public function getAgendaEvents(string $agenda_uid,
                                    array $filters = [],
                                    int $from = 0,
                                    int $size = self::DEFAULT_EVENTS_SIZE,
                                    ?string $sort = 'timingsWithFeatured.asc',
                                    ?bool $include_embedded = TRUE): mixed
    {
        // Build param array.
        $params = $filters;
        $params += ['from' => $from];
        if (!isset($filters['size'])) {
            $params += ['size' => $size];
        }
        $params += ['sort' => $sort];
        $params += ['longDescriptionFormat' => $include_embedded ? 'HTMLWithEmbeds' : 'HTML'];

        return $this->getData($agenda_uid, $params);
    }

    /**
     * Get an event from its slug.
     *
     * @param string $agenda_uid
     *   The agenda UID.
     * @param string $event_slug
     *   Slug of the event to get.
     * @param bool|null $include_embedded
     *
     * @return array
     *   An array representing an event in this agenda.
     */
    public function getEventBySlug(string $agenda_uid, string $event_slug, ?bool $include_embedded = TRUE): array
    {
        $data = $this->getData($agenda_uid, ['slug' => $event_slug, 'detailed' => 1, 'longDescriptionFormat' => $include_embedded ? 'HTMLWithEmbeds' : 'HTML']);
        return !empty($data['events']) ? array_pop($data['events']) : array();
    }

    /**
     * Get an event slug by offset.
     *
     * @param string $agenda_uid
     *   The agenda UID.
     * @param array $filters
     *   An array of filter parameters.
     * @param int $from
     * @param bool|null $include_embedded
     *   Wether include embedded code in event html or not.
     *
     * @return array
     *   An array with three events (previous, current, next).
     */
    public function getEventSlugByOffset(string $agenda_uid, array $filters, int $from, ?bool $include_embedded = TRUE): array
    {
        $data = $this->getData($agenda_uid, $filters + ['detailed' => 1, 'longDescriptionFormat' =>  $include_embedded ? 'HTMLWithEmbeds' : 'HTML']);

        $slug = '';
        if (!empty($data['events'])) {
            $event = array_pop($data['events']);
            $slug = !empty($event['slug']) ? $event['slug'] : '';
        }

        return $slug;
    }

    /**
     * Get next event slug in a filter configuration.
     *
     * @param string $agenda_uid
     *   The agenda UID.
     * @param array $filters
     *   An array of filter parameters.
     * @param int $from
     *   Current offset.
     *
     * @return string
     *   The slug corresponding to the next event in this agenda.
     */
    public function getNextEventSlug(string $agenda_uid, array $filters, int $from): string
    {
        return $this->getEventSlugByOffset($agenda_uid, $filters, $from + 1);
    }

    /**
     * Get previous event slug in a filter configuration.
     *
     * @param string $agenda_uid
     *   The agenda UID.
     * @param array $filters
     *   An array of filter parameters.
     * @param int $from
     *   Current offset.
     *
     * @return string The slug corresponding to the previous event in this agenda.
     *   The slug corresponding to the previous event in this agenda.
     */
    public function getPreviousEventSlug(string $agenda_uid, array $filters, int $from): string
    {
        return $from > 0 ? $this->getEventSlugByOffset($agenda_uid, $filters, $from - 1) : '';
    }

    /**
     * Get event triplet (previous, current, next).
     *
     * @param string $agenda_uid
     *   The agenda UID.
     * @param array $filters
     *   An array of filter parameters.
     * @param int $from
     *   Get events starting from that offset.
     * @param bool|null $include_embedded
     *   Wether include embedded code in event html or not.
     *
     * @return array
     *   An array with three events (previous, current, next).
     */
    #[ArrayShape(['previous' => "mixed|null", 'current' => "mixed|null", 'next' => "mixed|null"])]
    public function getEventTriplet(string $agenda_uid, array $filters, int $from, ?bool $include_embedded = TRUE): array
    {
        if ($from == 0) {
            $data = $this->getAgendaEvents($agenda_uid, $filters + ['detailed' => 1], $from, 2, NULL, $include_embedded);
        }
        else {
            $data = $this->getAgendaEvents($agenda_uid, $filters + ['detailed' => 1], $from - 1, 3, NULL, $include_embedded);
        }

        $events = $data['events'] ?? [];
        return [
            'previous' => $from > 0 ? array_shift($events) : NULL,
            'current' => array_shift($events),
            'next' => array_shift($events),
        ];
    }
}
