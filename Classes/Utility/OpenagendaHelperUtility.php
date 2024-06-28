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
use JetBrains\PhpStorm\ArrayShape;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * OpenagendaHelperUtility class
 */
class OpenagendaHelperUtility implements OpenagendaHelperUtilityInterface
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
     * Logger
     *
     * @var array
     */
    private array $languagePriorityList;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->uriBuilder->setRequest($this->getExtbaseRequest());
    }

    /**
     * Encode the context request parameter.
     *
     * @param int $index
     *   Position of event in current search.
     * @param int $total
     *   Total number of events returned by current search.
     * @param array $filters
     *   Array of search parameters.
     * @param int $calendarUid
     *   calendar uid from pi settings.
     *
     * @return string
     *   Encoded context.
     */
    public function encodeContext(int $index, int $total, array $filters, int $calendarUid): string
    {
        $context = [
            'index' => $index,
            'total' => $total,
            'calendarUid' => $calendarUid
        ];

        if (!empty($filters)) {
            $context['filters'] = $filters;
        }

        return base64_encode(json_encode($context));
    }

    /**
     * Decode the context request parameter.
     *
     * @param string $serialized_context
     *   The context parameter to decode.
     *
     * @return array
     *   Decoded context.
     */
    public function decodeContext(string $serialized_context): array
    {
        return json_decode(base64_decode($serialized_context), true);
    }

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
    public function createEventUrl(int $event_uid, string $event_slug, string $oac, int $page = null, string $language = null, bool $preview = false): string
    {
        $parameters = [
            'uid' => $event_uid,
            'event' => $event_slug,
            'oac' => $oac,
        ];

        if($preview) {
            $parameters['preview'] = 1;
        }

        $this->uriBuilder
            ->reset()
            ->uriFor(
                'event',
                $parameters,
                'Openagenda',
                'openagenda',
                'Agenda'
            );

        if($language != null) {
            $this->uriBuilder->setLanguage($language);
        }

        if($page != null) {
            $this->uriBuilder->setTargetPageUid($page);
        }

        return $this->uriBuilder->buildFrontendUri();
    }

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
     * @param string $content_language
     *   The content language.
     *
     * @return string
     *   The value best matching the language.
     * @throws AspectNotFoundException
     */
    public function getLocalizedValue(array $data, string $key, string $content_language = 'default'): string
    {
        $value = '';
        $language_priority_list = array_keys($this->getLanguagePriorityList($content_language));

        // Check the property exists in our array.
        if (!empty($data[$key])) {
            // Pick first non-empty value in our language priorities order.
            foreach ($language_priority_list as $language) {
                if (!empty($data[$key][$language])) {
                    $value = $data[$key][$language];
                    break;
                }
            }
        }

        if(is_array($value)) {
            $value = $value[0];
        }

        return $value;
    }

    /**
     * Localize event fields.
     *
     * @param array $event
     *   Event to localize.
     * @param string $content_language
     *   The content language.
     * @throws AspectNotFoundException
     */
    public function localizeEvent(array &$event, string $content_language = 'default') {
        $localized_properties = [
            'title',
            'description',
            'country',
            'dateRange',
            'longDescription',
            'keywords',
            'conditions',
        ];

        foreach ($localized_properties as $localized_property) {
            $event[$localized_property] = $this->getLocalizedValue($event, $localized_property, $content_language);
        }
    }

    /**
     * Get language priority list.
     *
     * @param string $content_language
     *   The content language.
     *
     * @return array
     *   The ordered language priority list.
     * @throws AspectNotFoundException
     */
    protected function getLanguagePriorityList(string $content_language = 'default'): array
    {
        if (empty($this->languagePriorityList)) {
            $this->setLanguagePriorityList($content_language);
        }

        return $this->languagePriorityList;
    }

    /**
     * Get the preferred language.
     *
     * @param string $content_language
     *   The content language.
     *
     * @return string
     *   The preferred language code.
     * @throws AspectNotFoundException
     */
    public function getPreferredLanguage(string $content_language = 'default'): string
    {
        $langcode_priority_list = array_keys($this->getLanguagePriorityList($content_language));
        return reset($langcode_priority_list);
    }

    /**
     * Set language priority list.
     *
     * Language priority order:
     *   user > [OA settings (node) > OA settings (main)] > site > fr.
     *
     *   'fr' is already taken account for through the way we build
     *   the available language list.
     *
     * @param string $content_language
     *   The content language.
     *
     * @return $this
     * @throws AspectNotFoundException
     */
    protected function setLanguagePriorityList(string $content_language = 'default'): static
    {
        $language_list = $this->getAvailableLanguages();
        $ordered_langcodes = [];

        // Content Language.
        if ($content_language != 'default') {
            $ordered_langcodes[] = $content_language;
        }

        // Site language.
        $context = GeneralUtility::makeInstance(Context::class);
        $site = $GLOBALS['TYPO3_REQUEST']->getAttribute('site');
        $langId = $context->getPropertyFromAspect('language', 'id');
        $language = $site->getLanguageById($langId);
        $langCode = $language->getTwoLetterIsoCode();
        $ordered_langcodes[] = $langCode;

        foreach (array_reverse($ordered_langcodes) as $langcode) {
            $language = $language_list[$langcode];
            unset($language_list[$langcode]);
            $language_list = [$langcode => $language] + $language_list;
        }

        $this->languagePriorityList = $language_list;

        return $this;
    }

    /**
     * Get a list of available languages.
     *
     * @return array
     *   The available languages keyed by language code.
     */
    #[ArrayShape(['fr' => "string", 'en' => "string", 'de' => "string", 'es' => "string", 'it' => "string"])]
    public function getAvailableLanguages(): array
    {
        return [
            'fr' => 'Français',
            'en' => 'English',
            'de' => 'Deutsch',
            'es' => 'Español',
            'it' => 'Italiano',
        ];
    }

    /**
     * @param $node
     * @return array
     *  Pre-filters array.
     */
    public function getPreFilters($filter): array
    {
        $preFilters = [];
        if (!empty($filter) && $parsedUrl = parse_url($filter)) {
            parse_str($filter, $preFilters);
        }

        return $preFilters;
    }

    /**
     * Process relative timing to event.
     *
     * @param array $event
     *   The event to parse.
     * @param string $lang
     *   Language code for date format.
     *
     * @return string|null
     *   relative timing to event.
     */
    public function processRelativeTimingToEvent(array $event, string $lang = 'default'): ?string
    {
        $relative_timing = NULL;

        if (!empty($event) && !empty($event['timings'])) {
            $nowDt = new DateTime();

            // Find next timing for the event.
            foreach ($event['timings'] as $timing) {
                $begin = DateTime::createFromFormat('Y-m-d\TH:i:sP', $timing['begin']);

                if ($begin > $nowDt) {
                    $next_begin = $begin;
                    break;
                }
            }

            // Modify string to reflect that we are working with next or last timing.
            if (!empty($next_begin)) {
                $formatted_time_diff = $this->TimeToJourJ($next_begin->getTimestamp(), $lang);

                $relative_timing = LocalizationUtility::translate('relativeTimingIn', 'openagenda', $formatted_time_diff, $lang);
            } else {
                $last_end = DateTime::createFromFormat('Y-m-d\TH:i:sP', array_pop($event['timings'])['end']);

                $formatted_time_diff = $this->TimeToJourJ($last_end->getTimestamp(), $lang);

                $relative_timing = LocalizationUtility::translate('relativeTimingSince', 'openagenda', $formatted_time_diff, $lang);
            }
        }

        return $relative_timing;
    }

    /**
     * Get date in array.
     *
     * @param string $date
     *   The date
     *
     * @return array
     *   Date in array.
     */
    protected function DateConvert(string $date): array
    {
        $day = (int)substr($date, 8, 2);
        $month = (int)substr($date, 5, 2);
        $year = (int)substr($date, 0, 4);
        $hour = (int)substr($date, 11, 2);
        $minute = (int)substr($date, 14, 2);
        $second = (int)substr($date, 17, 2);

        $key = array('year', 'month', 'day', 'hour', 'minute', 'second');
        $value = array($year, $month, $day, $hour, $minute, $second);

        return array_combine($key, $value);
    }

    /**
     * Process relative timing to event.
     *
     * @param int $date
     *   The date
     * @param $lang
     * @return array
     *   Number and type of date.
     */
    protected function TimeToJourJ(int $date, $lang): array
    {
        $tabDate = $this->DateConvert(date('Y-m-d H:i:s', $date));
        $mktJourj = mktime($tabDate['hour'],
            $tabDate['minute'],
            $tabDate['second'],
            $tabDate['month'],
            $tabDate['day'],
            $tabDate['year']);

        $mktNow = time();

        $diff = $mktJourj - $mktNow;

        $day = 3600 * 24;
        $week = 3600 * 24 * 7;

        if($diff>=$week) {
            // In weeks
            $calcul = $diff / $week;
            return array(ceil($calcul), LocalizationUtility::translate('week'.$this->plural($calcul), 'openagenda', array(), $lang));
        } elseif($diff>=$day) {
            // In days
            $calcul = $diff / $day;
            return array(ceil($calcul), LocalizationUtility::translate('day'.$this->plural($calcul), 'openagenda', array(), $lang));
        } elseif($diff<$day && $diff>=0 && $diff>=3600) {
            // In hours
            $calcul = $diff / 3600;
            return array(ceil($calcul), LocalizationUtility::translate('hour'.$this->plural($calcul), 'openagenda', array(), $lang));
        } elseif($diff<$day && $diff>=0 && $diff<3600) {
            // In minutes
            $calcul = $diff / 60;
            return array(ceil($calcul), LocalizationUtility::translate('minute'.$this->plural($calcul), 'openagenda', array(), $lang));
        } elseif($diff<0 && abs($diff)<3600) {
            // Since Minutes
            $calcul = abs($diff) / 60;
            return array(ceil($calcul), LocalizationUtility::translate('minute'.$this->plural($calcul), 'openagenda', array(), $lang));
        } elseif($diff<0 && abs($diff)<=$day) {
            // Since hours
            $calcul = abs($diff) / 3600;
            return array(ceil($calcul), LocalizationUtility::translate('hour'.$this->plural($calcul), 'openagenda', array(), $lang));
        } elseif($diff<0 && abs($diff)>$day) {
            // Since days
            $calcul = abs($diff) / $day;
            return array(ceil($calcul), LocalizationUtility::translate('day'.$this->plural($calcul), 'openagenda', array(), $lang));
        } else {
            // Since minutes
            $calcul = abs($diff) / 60;
            return array(ceil($calcul), LocalizationUtility::translate('minute'.$this->plural($calcul), 'openagenda', array(), $lang));
        }
    }

    /**
     * @return string
     * @throws AspectNotFoundException
     */
    public function getLanguage($language): string
    {
        // Get language site
        $context = GeneralUtility::makeInstance(Context::class);
        $site = $GLOBALS['TYPO3_REQUEST']->getAttribute('site');
        $langId = $context->getPropertyFromAspect('language', 'id');
        $language = $site->getLanguageById($langId);
        $langCode = $language->getTwoLetterIsoCode();

        return $langCode ?: $language;
    }

    /**
     * @return string
     * @throws AspectNotFoundException
     */
    public function getLanguageId(): string
    {
        // Get language ID
        $context = GeneralUtility::makeInstance(Context::class);
        return $context->getPropertyFromAspect('language', 'id');
    }

    /**
     * @param $chiffre
     * @return string|null
     */
    protected function plural($chiffre): ?string
    {
        if($chiffre > 1) {
            return 's';
        }

        return null;
    }

    /**
     * @return RequestInterface
     */
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
