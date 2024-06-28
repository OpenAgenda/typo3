<?php

namespace Openagenda\Openagenda\Utility;

/**
 * Interface for OpenagendaHelperUtility.
 */
interface OpenagendaHelperUtilityInterface
{
    /**
     * Encode the context request parameter.
     *
     * @param int $index
     *   Position of event in current search.
     * @param int $total
     *   Total number of events returned by current search.
     * @param array $search
     *   Array of search parameters.
	 * @param int $calendarUid
	 *   calendar uid from pi settings.
     *
     * @return string
     *   Encoded context.
     */
    public function encodeContext(int $index, int $total, array $search, int $calendarUid): string;

    /**
     * Decode the context request parameter.
     *
     * @param string $serialized_context
     *   The context parameter to decode.
     *
     * @return array
     *   Decoded context.
     */
    public function decodeContext(string $serialized_context): array;

    /**
     * Create an event url from a slug.
     *
     * @param int $event_uid
     *   The event's uid.
     * @param string $event_slug
     *   The event's slug.
     * @param string $oac
     *   The oac parameter (serialized context)
     *
     * @return string
     *   The event's url.
     */
    public function createEventUrl(int $event_uid, string $event_slug, string $oac): string;

    /**
     * Get the value of an agenda/event property best matching the language.
     *
     * Language priority order:
     *   user > OA settings (node) > OA settings (main) > site > agenda > fr.
     *
     * @param array $data
     *   An agenda or an event.
     * @param string $key
     *   The property to get the value from.
     *
     * @return string
     *   The value best matching the language.
     */
    public function getLocalizedValue(array $data, string $key): string;

    /**
     * Localize event fields.
     *
     * @param array $event
     *   Event to localize.
     */
    public function localizeEvent(array &$event);

    /**
     * Get the preferred language.
     *
     * @return string
     *   The preferred language code.
     */
    public function getPreferredLanguage(): string;

    /**
     * Get a list of available languages.
     *
     * @return array
     *   The available languages keyed by language code.
     */
    public function getAvailableLanguages(): array;

}