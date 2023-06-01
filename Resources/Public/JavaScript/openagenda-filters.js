/**
 * @file
 * Contains the definition of the OpenAgenda filters behaviour.
 */

// window.oa.
if (typeof window.oa === 'undefined') {
    window.oa = {
        query: window.location.search,
        locale: settingsOpenagendaLanguage,
        locales: {
            en: {
                eventsTotal: '{total, plural, =0 {No events match this search} one {{total} event} other {{total} events}}'
            },
            fr: {
                eventsTotal: '{total, plural, =0 {Aucun événement ne correspond à cette recherche} one {{total} événement} other {{total} événements}}'
            }
        },
        res: settingsOpenagendaFiltersUrl,
        ajaxUrl: settingsOpenagendaAjaxUrl,
        filtersRef: null,
        values: null,
        queryParams: null,
        onLoad: async (values, aggregations, filtersRef, _form) => {
            oa.filtersRef = filtersRef;
            oa.values = values;
            oa.queryParams = {...values, aggregations};

            // Update filters.
            await  oa.updateFiltersAndWidgets();
        },
        onFilterChange: async (values, aggregations, filtersRef, _form) => {
            oa.filtersRef = filtersRef;
            oa.values = values;
            oa.queryParams = {...values, aggregations, settingsOpenagendaPage, settingsOpenagendaLanguageId, settingsOpenagendaLanguage, settingsOpenagendaColumns, settingsOpenagendaEventsPerPage, settingsOpenagendaPreFilter};

            // Show Loader
            $('#loading').show();

            // Load events.
            $.ajax({
                url: oa.ajaxUrl + '&reset=1&' + $.param(oa.queryParams)
            }).done(function( data ) {
                $('#oa-wrapper').html( data.content );
            });

            // Update filters and location.
            oa.updateFiltersAndWidgets();
            oa.filtersRef.updateLocation(oa.values);
        },
        updateFiltersAndWidgets: async () => {
            $.ajax({
                url: oa.res + '&' + $.param(oa.queryParams),
                type: 'GET',
                dataType: 'json',
                async: true,
                complete: (data) => {
                    oa.filtersRef.updateFiltersAndWidgets(oa.values, data.responseJSON);

                    // Hide Loader
                    $('#loading').hide();
                }
            });
        },
    };
}
