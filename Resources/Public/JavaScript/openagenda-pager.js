/**
 * @file
 * Contains the definition of the OpenAgenda pager behaviour.
 */

$(document).ready(function () {
    // Pager navigation.
    $('body').on('click', '#oa-wrapper .pager__link', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const link = $(event.target);
        let ajaxUrl = link.attr('href');

        // Hack for SVG click
        if(ajaxUrl === undefined) {
            ajaxUrl = link.parent().attr('href');
        }
        if(ajaxUrl === undefined) {
            ajaxUrl = link.parent().parent().attr('href');
        }

        // Show Loader
        $('#loading').show();

        // Ajax query execution.
        $.ajax({
            url: settingsOpenagendaAjaxUrl + '&' + ajaxUrl + '&settingsOpenagendaCalendarUid=' + settingsOpenagendaCalendarUid + '&settingsOpenagendaPage=' + settingsOpenagendaPage + '&settingsOpenagendaLanguageId=' + settingsOpenagendaLanguageId + '&settingsOpenagendaLanguage=' + settingsOpenagendaLanguage + '&settingsOpenagendaColumns=' + settingsOpenagendaColumns + '&settingsOpenagendaEventsPerPage=' + settingsOpenagendaEventsPerPage + '&settingsOpenagendaPreFilter=' + settingsOpenagendaPreFilter
        }).done(( data ) => {
            $('#oa-wrapper').html( data.content );
            // Hide Loader
            $('#loading').hide();
            document.getElementById('oa-wrapper').scrollIntoView();
        });
    });
});
