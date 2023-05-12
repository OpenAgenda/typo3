<?php

namespace Openagenda\Openagenda\Utility;

/**
 * Interface for OpenagendaEventProcessorUtility.
 */
interface OpenagendaEventProcessorUtilityInterface
{
    /**
     * Build an event's render array.
     *
     * @param array $event
     *   The event to render.
     * @param $agenda
     *   The agenda the event relates to.
     *
     * @return array
     *   An agenda's render array or a simple markup to report
     *   that no agenda was found.
     */
    public function buildRenderArray(array $event, $agenda): array;

    /**
     * Process an event's timetable.
     *
     * @param array $event
     *   Event to process.
     *
     * @return array
     *   An array of months and weeks with days and time range values.
     */
    public function processEventTimetable(array $event): array;

    /**
     * Process metadata for an event.
     *
     * @param array $event
     *   The event.
     *
     * @return array
     *   Metadata array attachable through html_head in the render array.
     */
    public function processEventMetadata(array $event): array;

}