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

use DateTime;
use DateTimeZone;
use Exception;
use IntlDateFormatter;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * OpenAgendaEventProcessorUtility class
 */
class OpenagendaEventProcessorUtility implements OpenagendaEventProcessorUtilityInterface
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
        $this->config['style'] = $backendConfiguration['default_style'];
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->openagendaHelper = new OpenagendaHelperUtility($this->logger);
        $this->openagendaConnector = new OpenagendaConnectorUtility($this->logger);
    }

    /**
     * Build an event's render array.
     *
     * @param array $event
     *   The event to render.
     * @param $agenda
     *   The agenda the event relates to.
     * @param array $context
     *   Context for event navigation.
     *
     * @return array
     *   An agenda's render array or a simple markup to report
     *   that no agenda was found.
     */
    public function buildRenderArray(array $event, $agenda, array $context = [], $language = 'default'): array
    {
        $build = [];

        if (is_object($agenda)) {
            // Localize the event.
            $this->openagendaHelper->localizeEvent($event, $language);

            $build = [
                'agenda' => $agenda,
                'event' => $event,
                'context' => $context,
                'lang' => $this->openagendaHelper->getPreferredLanguage($language),
            ];
        }

        return $build;
    }

    /**
     * Process an event's timetable.
     *
     * @param array $event
     *   Event to process.
     * @param string $lang
     *   Language code for date format.
     *
     * @return array
     *   An array of months and weeks with days and time range values.
     * @throws Exception
     */
    public function processEventTimetable(array $event, string $lang = 'default'): array
    {
        $timetable = [];
        $current_month = '';
        $current_month_timings = [];
        $current_week = '';
        $current_week_timings = [];
        $current_day = '';
        $current_day_timings = [];
        $formatter = new IntlDateFormatter($lang . '_' . strtoupper($lang), IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);

        // First check event has all the necessary values.
        if (!empty($event) && !empty($event['timings'])) {
            // Set the timezone to the location's timezone.
            if (!empty($event['location']) && !empty($event['location']['timezone'])) {
                $formatter->setTimezone(new DateTimeZone($event['location']['timezone']));
            } else {
                $formatter->setTimezone(new DateTimeZone('Europe/Paris'));
            }

            // Parse timings.
            foreach ($event['timings'] as $timing) {
                // Check our timing is valid (has a start and an end).
                if (!empty($timing['begin']) && !empty($timing['end'])) {
                    // Format of day (ex: Thursday 25).
                    $timing_day = new DateTime($timing['begin']);
                    $formatter->setPattern('cccc d');
                    $timing_day = $formatter->format($timing_day);

                    // If this is a new day...
                    if ($timing_day != $current_day) {
                        // ... and our current day has timings...
                        if (!empty($current_day_timings)) {
                            // ...push our current day timings in our current week timings...
                            $current_week_timings[] = $current_day_timings;
                        }

                        $current_day_timings = [
                            'label' => $timing_day,
                            'timings' => [],
                        ];
                        $current_day = $timing_day;

                        // Format of month (ex: March 2021).
                        $timing_month = new DateTime($timing['begin']);
                        $formatter->setPattern('LLLL yyyy');
                        $timing_month = $formatter->format($timing_month);

                        // Week number is only used to check for week change.
                        $timing_week = new DateTime($timing['begin']);
                        $formatter->setPattern('ww');
                        $timing_week = $formatter->format($timing_week);

                        // If week or month has changed...
                        if ($timing_week != $current_week || $timing_month != $current_month) {
                            // ... and the week we were working on has timings...
                            if (!empty($current_week_timings)) {
                                // ... push it in our current month's array.
                                $current_month_timings['weeks'][] = $current_week_timings;
                            }

                            $current_week_timings = [];
                            $current_week = $timing_week;
                        }

                        // If month has changed do the whole thing again.
                        if ($timing_month != $current_month) {
                            if (!empty($current_month_timings)) {
                                $timetable[] = $current_month_timings;
                            }

                            $current_month_timings = [
                                'label' => $timing_month,
                                'weeks' => [],
                            ];
                            $current_month = $timing_month;
                        }
                    }

                    $formatter->setPattern('HH:mm');

                    $begin = new DateTime($timing['begin']);
                    $begin = $formatter->format($begin);

                    $end = new DateTime($timing['end']);
                    $end = $formatter->format($end);

                    $current_day_timings['timings'][] = [
                        'begin' => $begin,
                        'end' => $end,
                    ];
                }
            }

            // Push the last day/week/month's timings.
            if (!empty($current_day_timings)) {
                $current_week_timings[] = $current_day_timings;
                $current_month_timings['weeks'][] = $current_week_timings;
                $timetable[] = $current_month_timings;
            }
        }

        return $timetable;
    }

    /**
     * Process metadata for an event.
     *
     * @param array $event
     *   The event.
     *
     * @return array
     *   Metadata array attachable through html_head in the render array.
     */
    public function processEventMetadata(array $event): array
    {
        $metadata = [];

        // Attaching og:type and og:url.
        $metadata[] = [
            [
                '#tag' => 'meta',
                '#attributes' => [
                    'property' => 'og:type',
                    'content' => 'article',
                ],
            ],
            'oa_event_og_type',
        ];

        if (!empty($event['baseUrl'])) {
            $metadata[] = [
                [
                    '#tag' => 'meta',
                    '#attributes' => [
                        'property' => 'og:url',
                        'content' => $event['baseUrl'],
                    ],
                ],
                'oa_event_og_url',
            ];
        }

        // Attaching title and og:title.
        if (!empty($event['title'])) {
            $metadata[] = [
                [
                    '#tag' => 'meta',
                    '#attributes' => [
                        'name' => 'title',
                        'content' => $event['title'],
                    ],
                ],
                'oa_event_title',
            ];

            $metadata[] = [
                [
                    '#tag' => 'meta',
                    '#attributes' => [
                        'property' => 'og:title',
                        'content' => $event['title'],
                    ],
                ],
                'oa_event_og_title',
            ];
        }

        // Attaching description and og:description.
        if (!empty($event['description'])) {
            $metadata[] = [
                [
                    '#tag' => 'meta',
                    '#attributes' => [
                        'name' => 'description',
                        'content' => $event['description'],
                    ],
                ],
                'oa_event_description',
            ];

            $metadata[] = [
                [
                    '#tag' => 'meta',
                    '#attributes' => [
                        'property' => 'og:description',
                        'content' => $event['description'],
                    ],
                ],
                'oa_event_og_description',
            ];
        }

        // Attaching og:image.
        if (!empty($event['image'])) {
            $metadata[] = [
                [
                    '#tag' => 'meta',
                    '#attributes' => [
                        'property' => 'og:image',
                        'content' => $event['image']['base'] . $event['image']['filename'],
                    ],
                ],
                'oa_event_og_image',
            ];
        }

        return $metadata;
    }

    /**
     * @throws Exception
     */
    public function convert(int $calendarUid, string $slug, string $oac, string $lang) {
        // If an oac parameter is provided, we first try to get an event triplet
        // to get previous, current & next event in one request.
        if (!empty($oac)) {
            $context = $this->openagendaHelper->decodeContext($oac);

            if (isset($context['index'])) {
                $filters = !empty($context['filters']) ? $context['filters'] : [];
                $event_triplet = $this->openagendaConnector->getEventTriplet($calendarUid, $filters, $context['index'], (bool) $this->config['includeEmbedded']);

                // We check at least a current event was found and also that its slug
                // matches with the url in case a wrong oac was given.
                if (isset($event_triplet['current']['slug'])
                    && $event_triplet['current']['slug'] == $slug) {
                    $event = $event_triplet['current'];

                    if (!empty($event_triplet['previous']) && !empty($event_triplet['previous']['slug'])) {
                        $event['previousEventSlug'] = $event_triplet['previous']['slug'];
                    }

                    if (!empty($event_triplet['next']) && !empty($event_triplet['next']['slug'])) {
                        $event['nextEventSlug'] = $event_triplet['next']['slug'];
                    }
                }
            }
        }

        // Failing that, we try to get the event from its slug.
        if (empty($event)) {
            $event = $this->openagendaConnector->getEventBySlug($calendarUid, $slug, (bool) $this->config['includeEmbedded']);
        }

        $event['timetable'] = $this->processEventTimetable($event, $lang);

        return $event;
    }
}
