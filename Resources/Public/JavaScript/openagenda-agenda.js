/**
 * @file
 * Contains the definition of the OpenAgenda default behaviour.
 */

$(document).ready(function () {
    //console.log(style);
    // Equalize.
    window.doEqualizeAgenda = () => {
        $('.oa-list').each(function() {
            var children = $(this).find('.oa-list__item');

            if (children.length == 0) {
                return false;
            }
            var heights = [];
            var rowElementCount = Math.floor($(this).width() / children.first().width());
            children.each(function(i, e) {
                $(e).height('auto');
            });
            children.each(function(i, e) {
                if (heights[Math.floor(i/rowElementCount)] == undefined) {
                    heights[Math.floor(i/rowElementCount)] = 0;
                }
                if ($(e).height() > heights[Math.floor(i/rowElementCount)]) {
                    heights[Math.floor(i/rowElementCount)] = $(e).height();
                }
            });

            children.each(function(i, e) {
                $(e).height('auto');
                $(e).height(heights[Math.floor(i/rowElementCount)]);
            });
        });
    }

    let oaContent = document.getElementsByClassName('field--type-openagenda') ?? document.getElementsByClassName('oa-agenda oa-agenda--preview');
    if (oaContent.length) {
        let observer = new MutationObserver((mutations) => {
            window.doEqualizeAgenda();
        });
        observer.observe(oaContent[0], {
            childList: true,
            subtree: true,
        });
    }

    setTimeout(window.doEqualizeAgenda, 250);
});