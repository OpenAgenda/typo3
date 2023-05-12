<?php

namespace Openagenda\Openagenda\Utility;

/**
 * Interface for OpenagendaConnectorUtility.
 */
interface OpenagendaConnectorUtilityInterface
{
    const DEFAULT_EVENTS_SIZE = 20;

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
     *   Event sort parameter. One of the following values.
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
                                    ?string $sort = 'timings.asc',
                                    ?bool $include_embedded = TRUE): mixed;

    /**
     * Get agenda settings.
     *
     * @param string $agenda_uid
     *   The agenda UID.
     *
     * @return array
     *   Data from the Openagenda server representing this agenda's settings.
     */
    public function getAgendaSettings(string $agenda_uid): array;

    /**
     * Get an event from its slug.
     *
     * @param string $agenda_uid
     *   The agenda UID.
     * @param string $event_slug
     *   Slug of the event to get.
     * @param bool|null $include_embedded
     *   Wether include embedded code in event html or not.
     *
     * @return array
     *   An array representing an event in this agenda.
     */
    public function getEventBySlug(string $agenda_uid, string $event_slug, ?bool $include_embedded = TRUE): array;

    /**
     * Get an event from its slug.
     *
     * @param string $agenda_uid
     *   The agenda UID.
     * @param array $filters
     *   An array of filter parameters.
     * @param bool|null $include_embedded
     *   Wether include embedded code in event html or not.
     *
     * @return array
     *   An array representing an event in this agenda.
     */
    public function getEventSlugByOffset(string $agenda_uid, array $filters, int $from, ?bool $include_embedded = TRUE): array;

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
    public function getNextEventSlug(string $agenda_uid, array $filters, int $from): string;

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
     * @return string
     *   The slug corresponding to the previous event in this agenda.
     */
    public function getPreviousEventSlug(string $agenda_uid, array $filters, int $from): string;

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
    public function getEventTriplet(string $agenda_uid, array $filters, int $from, ?bool $include_embedded = TRUE): array;

}
