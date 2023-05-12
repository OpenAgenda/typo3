<?php

namespace Openagenda\Openagenda\Utility;

/**
 * Interface for OpenagendaAgendaProcessorUtility.
 */
interface OpenagendaAgendaProcessorUtilityInterface
{

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
    public function buildRenderArray($calendarUid, $event, ?bool $ajax = FALSE, ?int $page = NULL): array;

}