<?php

namespace Openagenda\Openagenda\Service;

use Openagenda\Openagenda\Utility\OpenagendaHelperUtility;
use Openagenda\Openagenda\Utility\OpenagendaPaginationUtility;
use OpenAgendaSdk\OpenAgendaSdk;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class OpenagendaService
{
	/**
	 * OpenAgenda SDK.
	 *
	 * @var OpenAgendaSdk
	 */
	protected OpenAgendaSdk $sdk;

	/**
	 * OpenagendaHelperUtility.
	 *
	 * @var OpenagendaHelperUtility
	 */
	protected OpenagendaHelperUtility $openagendaHelper;

	/**
	 * UriBuilder.
	 *
	 * @var UriBuilder
	 */
	protected UriBuilder $uriBuilder;

	/**
	 * @param LoggerInterface $logger
	 * @param string $publicKey
	 */
	public function __construct(LoggerInterface $logger, string $publicKey)
	{
		$this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
		$this->sdk = new OpenAgendaSdk($publicKey);
		$this->openagendaHelper = new OpenagendaHelperUtility($logger);
	}

	/**
	 * @return array
	 */
	public function getQueryInfo(): array
	{
		$request = $GLOBALS['TYPO3_REQUEST'];
		$normalizedParams = $request->getAttribute('normalizedParams');
		parse_str($normalizedParams->getQueryString(), $queryInfo);

		return $queryInfo;
	}

	/**
	 * @param string|null $preFilter
	 * @param string $current
	 * @param bool $reset
	 * @return array|int[]
	 */
	public function getFilters(string|null $preFilter, string $current, bool $reset = false): array
	{
		$queryInfo = $this->getQueryInfo();
		$filters = $queryInfo;
		$filters += ['detailed' => 1];

		// Remove pager params.
		if($reset === true) {
			if(isset($filters['reset']) && $filters['reset'] == 1) {
				unset($filters['tx_openagenda_agenda']);
			}
			unset($filters['reset']);
		}
		unset($filters['type']);

		// Get pre-filters and add them to filters if defined.
		$preFilters = !is_null($preFilter) ? $this->openagendaHelper->getPreFilters($preFilter) : [];

		// Current & upcoming events only.
		$currentValue = $current;
		if (!empty($currentValue)) {
			$preFilters['relative'] = [
				'current',
				'upcoming',
			];
		}
		$filters += $preFilters;

		return $filters;
	}

	/**
	 * @param string|null $language
	 * @return string
	 */
	public function getAgendaURLBase(string|null $language = null): string
	{
		$this->uriBuilder->reset();
		if(!is_null($language)) {
			$this->uriBuilder->setLanguage($language);
		}

		return $this->uriBuilder->buildFrontendUri();
	}

	/**
	 * @param string $url
	 * @param array $filters
	 * @return string
	 */
	public function getAgendaURLWithFilters(string $url, array $filters): string
	{
		return $url . '?' . http_build_query($filters);
	}

	/**
	 * @param string|null $suivi
	 * @return string
	 */
	public function getParamsTracking(string|null $suivi): string {
		$paramsTracking = '';
		if(isset($suivi)) {
			$paramsTracking = '&cms=typo3&host=' . $_SERVER['SERVER_NAME'];
		}
		return $paramsTracking;
	}

	/**
	 * @param int|null $total
	 * @param int $eventsPerPage
	 * @param int|null $currentPage
	 * @return array
	 */
	public function getPagination(int|null $total, int $eventsPerPage, int $currentPage = 1): array
	{
		$pagination = [
			'paginator' => null,
			'pagination' => null,
			'currentPage' => 0
		];
		if (!empty($total)) {
			$itemsPerPage = $eventsPerPage;
			$numberOfEvents = range(0, $total);
			$maximumLinks = 6;
			$pagination['currentPage'] = isset($currentPage) ? (int)$currentPage : 1;
			$pagination['paginator'] = new ArrayPaginator($numberOfEvents, $pagination['currentPage'], $itemsPerPage);
			$pagination['pagination'] = new OpenagendaPaginationUtility($pagination['paginator'], $maximumLinks);
		}

		return $pagination;
	}

	/**
	 * @param array $fields
	 * @param string $additionnalFieldFilter
	 * @return array
	 */
	public function getAdditionalFields(array $fields, string $additionnalFieldFilter): array
	{
		$additionalFields = [];
		foreach ($fields as $agendaCustomField) {
			if (in_array($agendaCustomField->field, explode(';', $additionnalFieldFilter))) {
				$additionalFields[] = $agendaCustomField->field;
			}
		}

		return $additionalFields;
	}

	/**
	 * @param array $events
	 * @param int $total
	 * @param int $from
	 * @param int $calendarUid
	 * @param string $language
	 * @param string|null $languageUid
	 * @param array $filters
	 * @param int|null $page
	 * @param bool $preview
	 * @return array
	 * @throws AspectNotFoundException
	 */
	public function processEvents(
		array $events,
		int $total,
		int $from,
		int $calendarUid,
		string $language,
		string|null $languageUid,
		array $filters,
		int|null $page = null,
		bool $preview = false,
		bool $test = true
	): array
	{
		foreach ($events as $key => &$event) {
			// We use the event's key in the array as index.
			$serialized_context = $this->openagendaHelper->encodeContext((int)$key + $from, $total, $filters, $calendarUid);

			// Localize event according to the language set in the node.
			if($test === true) {
				$this->openagendaHelper->localizeEvent($event, $language);
			}

			// Set Relative timing
			$event['relative_timing'] = $this->openagendaHelper->processRelativeTimingToEvent($event, $language);

			// Set event local link.
			if(!is_null($languageUid)) {
				$event['local_url'] = $this->openagendaHelper->createEventUrl($event['uid'], $event['slug'], $serialized_context, $page, $languageUid, $preview);
			} else {
				$event['local_url'] = $this->openagendaHelper->createEventUrl($event['uid'], $event['slug'], $serialized_context);
			}
		}

		return $events;
	}
}