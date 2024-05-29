<?php

namespace Openagenda\Openagenda\Controller;

use Exception;
use Openagenda\Openagenda\Utility\OpenagendaAgendaProcessorUtility;
use Openagenda\Openagenda\Utility\OpenagendaConnectorUtilityInterface;
use Openagenda\Openagenda\Utility\OpenagendaEventProcessorUtility;
use Openagenda\Openagenda\Utility\OpenagendaHelperUtility;
use Openagenda\Openagenda\Utility\OpenagendaConnectorUtility;
use Openagenda\Openagenda\Utility\OpenagendaPaginationUtility;
use OpenAgendaSdk\OpenAgendaSdk;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class OpenagendaController extends ActionController
{
    /**
     * OpenAgenda SDK.
     *
     * @var OpenAgendaSdk
     */
    protected OpenAgendaSdk $sdk;

    /**
     * ResponseFactoryInterface
     *
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * OpenagendaHelperUtility.
     *
     * @var OpenagendaHelperUtility
     */
    protected OpenagendaHelperUtility $openagendaHelper;

    /**
     * OpenagendaConnectorUtility.
     *
     * @var OpenagendaConnectorUtility
     */
    protected OpenagendaConnectorUtility $openagendaConnector;

    /**
     * OpenagendaAgendaProcessorUtility.
     *
     * @var OpenagendaAgendaProcessorUtility
     */
    protected OpenagendaAgendaProcessorUtility $openagendaAgendaProcessor;

    /**
     * OpenagendaEventProcessorUtility.
     *
     * @var OpenagendaEventProcessorUtility
     */
    protected OpenagendaEventProcessorUtility $openagendaEventProcessor;

    /**
     * OpenAgenda Config Array.
     *
     * @var array
     */
    protected mixed $config;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    public function __construct(LoggerInterface $logger, ResponseFactoryInterface $responseFactory)
    {
        $this->logger = $logger;
        $backendConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->get('openagenda');
        $this->config['publicKey'] = $backendConfiguration['public_key'];
        $this->config['includeEmbedded'] = $backendConfiguration['include_embedded'];
        $this->config['current'] = $backendConfiguration['current'];
        $this->config['default_style'] = $backendConfiguration['default_style'];
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->sdk = new OpenAgendaSdk($this->config['publicKey']);
        $this->openagendaHelper = new OpenagendaHelperUtility($this->logger);
        $this->openagendaConnector = new OpenagendaConnectorUtility($this->logger);
        $this->openagendaEventProcessor = new OpenagendaEventProcessorUtility($this->logger);
        $this->openagendaAgendaProcessor = new OpenagendaAgendaProcessorUtility($this->logger);
        $this->responseFactory = $responseFactory;
    }

    /**
     * @throws AspectNotFoundException
     */
    public function agendaAction(): ResponseInterface
    {
        $arguments = $this->request->getArguments();
        $this->settings['language'] = $this->openagendaHelper->getLanguage($this->settings['language']);

        // Get request filters.
        $request = $GLOBALS['TYPO3_REQUEST'];
        $normalizedParams = $request->getAttribute('normalizedParams');
        parse_str($normalizedParams->getQueryString(), $queryInfo);

        $filters = $queryInfo;
        $filters += ['detailed' => 1];

        // Remove pager params.
        unset($filters['type']);

        // Get pre-filters and add them to filters if defined.
        $preFilters = $this->openagendaHelper->getPreFilters($this->settings['preFilter']);

        // Current & upcoming events only.
        $currentValue = $this->config['current'];
        if (!empty($currentValue)) {
            $preFilters['relative'] = [
                'current',
                'upcoming',
            ];
        }
        $filters += $preFilters;

        $from = (isset($arguments['page']) && $arguments['page'] > 0) ? ($arguments['page'] - 1) * (int) $this->settings['eventsPerPage'] : 0;
        $variables['entity'] = json_decode($this->sdk->getAgenda($this->settings['calendarUid']));
        $variables['events'] = $this->openagendaConnector->getAgendaEvents($this->settings['calendarUid'], $filters, $from, OpenagendaConnectorUtilityInterface::DEFAULT_EVENTS_SIZE, NULL, $this->config['includeEmbedded']);
        $erreur = false;
        $events = array();

        // This gets forwarded to the exports' template.
        $variables['search_string'] = !empty($filters) ? http_build_query($filters) : '';
        $filtersPagination = $filters;
        unset($filtersPagination['tx_openagenda_agenda']['page']);
        $filtersUrlPagination = !empty($filtersPagination) ? http_build_query($filtersPagination) : '';

        if(!empty($variables['events']['events'])) {
            $events = $variables['events']['events'];
            foreach ($events as $key => &$event) {
                // We use the event's key in the array as index.
                $serialized_context = $this->openagendaHelper->encodeContext((int)$key + $from, $variables['events']['total'], $filters);

                // Localize event according to the language set in the node.
                $this->openagendaHelper->localizeEvent($event, $this->settings['language']);

                // Set Relative timing
                $event['relative_timing'] = $this->openagendaHelper->processRelativeTimingToEvent($event, $this->settings['language']);

                // Set event local link.
                $event['local_url'] = $this->openagendaHelper->createEventUrl($event['uid'], $event['slug'], $serialized_context);
            }
        } else {
            $erreur = true;
        }

        $agendaUrlBase = $this->uriBuilder
            ->reset()
            ->setLanguage($this->settings['language'])
            ->buildFrontendUri();
        $agendaUrl = $agendaUrlBase . '?' . http_build_query($filters);

        // Add pager if needed.
        $paginator = null;
        $pagination = null;
        $currentPage = 0;
        if (!empty($variables['events']['total'])) {
            $itemsPerPage = $this->settings['eventsPerPage'];
            $numberOfEvents = range(0, $variables['events']['total']);
            $maximumLinks = 6;
            $currentPage = isset($arguments['page']) ? (int)$arguments['page'] : 1;
            $paginator = new ArrayPaginator($numberOfEvents, $currentPage, $itemsPerPage);
            $pagination = new OpenagendaPaginationUtility($paginator, $maximumLinks);
        }

        // AdditionalFields
        $additionalFields = array();
        foreach ($variables['entity']->schema->fields as $agendaCustomField) {
            if (in_array($agendaCustomField->field, explode(';', $this->settings['additionnalFieldFilter']))) {
                $additionalFields[] = $agendaCustomField->field;
            }
        }

        // Tracking
        $paramsTracking = '';
        if($this->settings['suivi']) {
            $paramsTracking = '&cms=typo3&host=' . $_SERVER['SERVER_NAME'];
        }

        $this->view->assign('agendaUrlBase', $agendaUrlBase);
        $this->view->assign('agendaUrl', $agendaUrl);
        $this->view->assign('paramsTracking', $paramsTracking);
        $this->view->assign('erreur', $erreur);
        $this->view->assign('config', $this->config);
        $this->view->assign('placeholder', LocalizationUtility::translate('placeholder', 'openagenda'));
        $this->view->assign('agenda', $variables['entity']);
        $this->view->assign('columns', $this->settings['columns']);
        $this->view->assign('search_string', $variables['search_string']);
        $this->view->assign('total', $variables['events']['total'] ?? 0);
        $this->view->assign('events', $events);
        $this->view->assign('currentPage', $GLOBALS['TSFE']->id);
        $this->view->assign('language', $this->settings['language']);
        $this->view->assign('languageId', $this->openagendaHelper->getLanguageId());
        $this->view->assign('additionalFields', $additionalFields);
        $this->view->assign('filtersUrl', $variables['search_string']);
        $this->view->assign('filtersUrlPagination', $filtersUrlPagination);
        $this->view->assign('pagination', [
            'paginator' => $paginator,
            'pagination' => $pagination,
            'page' => $currentPage,
        ]);
        return $this->htmlResponse();
    }

    /**
     * @throws AspectNotFoundException
     */
    public function previewAction(): ResponseInterface
    {
        // Get request filters.
        $this->settings['language'] = $this->openagendaHelper->getLanguage($this->settings['language']);
        $request = $GLOBALS['TYPO3_REQUEST'];
        $normalizedParams = $request->getAttribute('normalizedParams');
        parse_str($normalizedParams->getQueryString(), $queryInfo);

        $filters = $queryInfo;
        $filters += ['detailed' => 1];

        // Remove pager params.
        unset($filters['type']);

        // Get pre-filters and add them to filters if defined.
        $preFilters = $this->openagendaHelper->getPreFilters($this->settings['preFilter']);

        // Current & upcoming events only.
        $currentValue = $this->config['current'];
        if (!empty($currentValue)) {
            $preFilters['relative'] = [
                'current',
                'upcoming',
            ];
        }
        $filters += $preFilters;

        $from = 0;
        $variables['entity'] = json_decode($this->sdk->getAgenda($this->settings['calendarUid']));
        $variables['events'] = $this->openagendaConnector->getAgendaEvents($this->settings['calendarUid'], $filters, $from, $this->settings['eventsInPreview'], NULL, $this->config['includeEmbedded']);
        $erreur = false;
        $events = array();

        if(!empty($variables['events']['events'])) {
            $events = $variables['events']['events'];
            foreach ($events as $key => &$event) {
                // We use the event's key in the array as index.
                $serialized_context = $this->openagendaHelper->encodeContext((int)$key + $from, $variables['events']['total'], $filters);

                // Localize event according to the language set in the node.
                $this->openagendaHelper->localizeEvent($event, $this->settings['language']);

                // Set Relative timing
                $event['relative_timing'] = $this->openagendaHelper->processRelativeTimingToEvent($event, $this->settings['language']);

                // Set event local link.
                $event['local_url'] = $this->openagendaHelper->createEventUrl($event['uid'], $event['slug'], $serialized_context, $this->settings['agendaPage'], $this->openagendaHelper->getLanguageId(), true);
            }
        } else {
            $erreur = true;
        }

        $agendaUrlBase = $this->uriBuilder
            ->reset()
            ->buildFrontendUri();
        $agendaUrl = $agendaUrlBase . '?' . http_build_query($filters);

        // Tracking
        $paramsTracking = '';
        if($this->settings['suivi']) {
            $paramsTracking = '&cms=typo3&host=' . $_SERVER['SERVER_NAME'];
        }

        $this->view->assign('agendaUrlBase', $agendaUrlBase);
        $this->view->assign('agendaUrl', $agendaUrl);
        $this->view->assign('paramsTracking', $paramsTracking);
        $this->view->assign('erreur', $erreur);
        $this->view->assign('config', $this->config);
        $this->view->assign('agenda', $variables['entity']);
        $this->view->assign('columns', $this->settings['columns']);
        $this->view->assign('events', $events);
        $this->view->assign('currentPage', $GLOBALS['TSFE']->id);
        $this->view->assign('language', $this->settings['language']);
        $this->view->assign('languageId', $this->openagendaHelper->getLanguageId());

        return $this->htmlResponse();
    }

    /**
     * @throws Exception
     */
    public function eventAction(): ResponseInterface
    {
        $arguments = $this->request->getArguments();
        $entities = ['event' => null, 'agenda' => null];
        $erreur = null;
        $this->settings['language'] = $this->openagendaHelper->getLanguage($this->settings['language']);
        $agenda = json_decode($this->sdk->getAgenda($this->settings['calendarUid']));
        $event = json_decode($this->sdk->getEvent($this->settings['calendarUid'], $arguments['uid']), true);

        $variables = array();
        $agendaUrl = null;

        $oac = $arguments['oac'];
        $context = !empty($oac) ? $this->openagendaHelper->decodeContext($oac) : [];
        $filters = $context['filters'] ?? [];

        // Agenda link.
        $agendaUrlBase = $this->uriBuilder
            ->reset()
            ->buildFrontendUri();
        $agendaUrl .= $agendaUrlBase . '?' . http_build_query($filters);

        // Make sure our index and total values make sense.
        if (isset($context['index']) && isset($context['total'])
            && $context['total'] > 1 && $context['index'] < $context['total']) {
            // Make the index human-readable.
            $variables['index'] = $context['index'] + 1;
            $variables['total'] = $context['total'];
        }

        if(!is_null($event) && count($event) > 0) {
            $event = $event['event'];
            $event = $this->openagendaEventProcessor->convert($this->settings['calendarUid'], $event['slug'], $oac, $this->settings['language']);
            $entities = $this->openagendaEventProcessor->buildRenderArray($event, $agenda, $context, $this->settings['language']);
            $entities['event']['timetable'] = $this->openagendaEventProcessor->processEventTimetable($event, $this->settings['language']);

            // Make sure we have a parent OpenAgenda node.
            if (is_object($agenda)) {
                // Add a link if we found a previous event with those search parameters.
                if (!empty($entities['event']['previousEventSlug'])) {
                    $previous_event_context = $this->openagendaHelper->encodeContext($context['index'] - 1, $context['total'], $filters);
                    $previousEvent = $this->openagendaConnector->getEventBySlug($this->settings['calendarUid'], $entities['event']['previousEventSlug'], $this->config['includeEmbedded']);
                    $variables['previous_event_url'] = $this->openagendaHelper
                        ->createEventUrl($previousEvent['uid'], $entities['event']['previousEventSlug'], $previous_event_context);
                }

                // Add a link if we found a next event with those search parameters.
                if (!empty($entities['event']['nextEventSlug'])) {
                    $next_event_context = $this->openagendaHelper->encodeContext($context['index'] + 1, $context['total'], $filters);
                    $nextEvent = $this->openagendaConnector->getEventBySlug($this->settings['calendarUid'], $entities['event']['nextEventSlug'], $this->config['includeEmbedded']);
                    $variables['next_event_url'] = $this->openagendaHelper
                        ->createEventUrl($nextEvent['uid'], $entities['event']['nextEventSlug'], $next_event_context);
                }
            }
        } else {
            $erreur = LocalizationUtility::translate('errorEvent', 'openagenda');
        }

        // Tracking
        $paramsTracking = '';
        if($this->settings['suivi']) {
            $paramsTracking = '&cms=typo3&host=' . $_SERVER['SERVER_NAME'];
        }

        // Rich Snippet
        $richSnippet = json_encode($this->sdk->getEventRichSnippet($entities['event'], 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REDIRECT_URL'], $this->settings['language']),JSON_FORCE_OBJECT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        $this->view->assign('agendaUrlBase', $agendaUrlBase);
        $this->view->assign('agendaUrl', $agendaUrl);
        $this->view->assign('paramsTracking', $paramsTracking);
        $this->view->assign('richSnippet', $richSnippet);
        $this->view->assign('erreur', $erreur);
        $this->view->assign('config', $this->config);
        $this->view->assign('event', $entities['event']);
        $this->view->assign('agenda', $entities['agenda']);
        $this->view->assign('variables', $variables);
        $this->view->assign('currentPage', $GLOBALS['TSFE']->id);
        $this->view->assign('language', $this->settings['language']);
        $this->view->assign('languageId', $this->openagendaHelper->getLanguageId());
        $this->view->assign('preview', $arguments['preview'] ?? 0);
        return $this->htmlResponse();
    }

    public function mapFilterAction(): ResponseInterface
    {


        return $this->htmlResponse();
    }

    public function filtersCallbackAction(): ResponseInterface
    {
        // Get request filters.
        $preFilters = null;
        $request = $GLOBALS['TYPO3_REQUEST'];
        $normalizedParams = $request->getAttribute('normalizedParams');
        parse_str($normalizedParams->getQueryString(), $queryInfo);

        $filters = $queryInfo;
        $filters += ['detailed' => 1];

        // Remove type params.
        unset($filters['type']);

        // Prefilters.
        $currentValue = $this->config['current'];
        if (!empty($currentValue)) {
            $preFilters['relative'] = [
                'current',
                'upcoming',
            ];
        }
        $filters += $preFilters;

        $agenda = json_decode($this->sdk->getAgenda($queryInfo['settingsOpenagendaCalendarUid']));
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');

        if (is_object($agenda)) {
            $data = json_decode($this->sdk->getEvents($queryInfo['settingsOpenagendaCalendarUid'], $filters), TRUE);

            $response->getBody()->write(json_encode($data));

            return $response;
        }

        $response->getBody()->write(json_encode(['error' => 'This node has no opendagenda field']));

        return $response;
    }

    /**
     * Handle AJAX calls.
     *
     * @return ResponseInterface An Ajax response containing the commands to execute.
     *   An Ajax response containing the commands to execute.
     */
    public function ajaxCallbackAction(): ResponseInterface
    {
        // Get request filters.
        $preFilters = [];
        $request = $GLOBALS['TYPO3_REQUEST'];
        $normalizedParams = $request->getAttribute('normalizedParams');
        parse_str($normalizedParams->getQueryString(), $queryInfo);

        $filters = $queryInfo;
        $filters += ['detailed' => 1];

        // Remove type params.
        if(isset($filters['reset']) && $filters['reset'] == 1) {
            unset($filters['tx_openagenda_agenda']);
        }
        unset($filters['type']);
        unset($filters['reset']);

        // Current & upcoming events only.
        $currentValue = $this->config['current'];
        if (!empty($currentValue)) {
            $preFilters['relative'] = [
                'current',
                'upcoming',
            ];
        }
        $filters += $preFilters;
        $filtersUrl = !empty($filters) ? http_build_query($filters) : '';

        $filtersPagination = $filters;
        unset($filtersPagination['tx_openagenda_agenda']['page']);
        $filtersUrlPagination = !empty($filtersPagination) ? http_build_query($filtersPagination) : '';

        $agenda = json_decode($this->sdk->getAgenda($queryInfo['settingsOpenagendaCalendarUid']));

        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');

        $events = array();
        if ($agenda) {
            // Re-render the agenda with the new parameters.
            $currentPage = $filters['tx_openagenda_agenda']['page'] ?? 1;
            $from = ($currentPage - 1) * (int) $queryInfo['settingsOpenagendaEventsPerPage'];
            $entities = $this->openagendaAgendaProcessor->buildRenderArray($queryInfo['settingsOpenagendaCalendarUid'], $agenda, TRUE, $currentPage, $queryInfo['settingsOpenagendaLanguage'], $queryInfo['settingsOpenagendaEventsPerPage'], $queryInfo['settingsOpenagendaColumns'], $queryInfo['settingsOpenagendaPreFilter']);

            if(!empty($entities['events'])) {
                $events = $entities['events'];
                foreach ($events as $key => &$event) {
                    // We use the event's key in the array as index.
                    $serialized_context = $this->openagendaHelper->encodeContext((int)$key + $from, $entities['total'], $filters);

                    // Set Relative timing
                    $event['relative_timing'] = $this->openagendaHelper->processRelativeTimingToEvent($event, $queryInfo['settingsOpenagendaLanguage']);

                    // Set event local link.
                    $event['local_url'] = $this->openagendaHelper->createEventUrl($event['uid'], $event['slug'], $serialized_context, $queryInfo['settingsOpenagendaPage'], $queryInfo['settingsOpenagendaLanguageId']);
                }
            }
            // Add pager if needed.
            $paginator = null;
            $pagination = null;
            if (!empty($entities['total'])) {
                $itemsPerPage = $queryInfo['settingsOpenagendaEventsPerPage'];
                $numberOfEvents = range(0, $entities['total']);
                $maximumLinks = 6;
                $paginator = new ArrayPaginator($numberOfEvents, $currentPage, $itemsPerPage);
                $pagination = new OpenagendaPaginationUtility($paginator, $maximumLinks);
            }

            $noEventLabel = LocalizationUtility::translate('noEvent', 'openagenda', array(), $queryInfo['settingsOpenagendaLanguage']);
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setLayoutRootPaths(array('EXT:openagenda/Resources/Private/Layouts'));
            $view->setTemplateRootPaths(array('EXT:openagenda/Resources/Private/Templates'));
            $view->setPartialRootPaths(array('EXT:openagenda/Resources/Private/Partials'));

            $view->assign('noEventLabel', $noEventLabel);
            $view->assign('events', $events);
            $view->assign('total', $entities['total']);
            $view->assign('columns', $queryInfo['settingsOpenagendaColumns']);
            $view->assign('filtersUrl', $filtersUrl);
            $view->assign('filtersUrlPagination', $filtersUrlPagination);
            $view->assign('pagination', [
                'paginator' => $paginator,
                'pagination' => $pagination,
                'page' => $currentPage,
            ]);

            $content = array('content' => $view->render('AgendaAjax'));

            $response->getBody()->write(json_encode($content));
        }

        return $response;
    }
}