/*global $, clearTimeout, setTimeout, document, window*/
// Config variable for live search (ls)
var ls = {
    url: "ajax/process_livesearch.php",
    form_id: "#ls_form",
    form_anti_bot_id: "#ls_anti_bot",
    // This should be the same as the same parameter's value in config file
    form_anti_bot: "Ehsan's guard",
    query_id: "#ls_query",
    result_id: "#ls_result_div",
    footer_id: "#ls_result_footer",
    current_page_hidden_id: "#ls_current_page",
    current_page_lbl_id: "#ls_current_page_lbl",
    last_page_lbl_id: "#ls_last_page_lbl",
    page_range_id: "#ls_items_per_page",
    navigation_class: ".navigation",
    arrow_class: ".arrow",
    next_page_id: "ls_next_page",
    previous_page_id: "ls_previous_page",
    slide_speed: "fast",
    type_delay: 350,
    select_column_index: 1
};

var result = $(ls.result_id);
var query = $(ls.query_id);
var footer = $(ls.footer_id);
var current_page = $(ls.current_page_hidden_id);
var current_page_lbl = $(ls.current_page_lbl_id);
var total_page_lbl = $(ls.last_page_lbl_id);
var page_range = $(ls.page_range_id);
var select_result;

function show_result() {
    'use strict';
    result.slideDown(ls.slide_speed);
}

function hide_result() {
    'use strict';
    result.slideUp(ls.slide_speed);
}

/*
 get the number of results and based on that optimized the select
 number of rows / items dropdown
 */
function remove_select_options(number_of_results) {
    'use strict';
    var all_options, selected_option_removed;

    // Store selected option
    all_options = page_range.data('selected_option', page_range.val()).find("option");

    // Store default options if it is not set
    if (page_range.data('all_options') === undefined) {
        page_range.data('all_options', all_options);
    } else {
        // reset the select
        page_range.empty();
        page_range.append(page_range.data('all_options'));
    }

    selected_option_removed = false;
    $(page_range.data('all_options')).each(function () {
        // remove unnecessary options
        if (this.value >= number_of_results) {
            if (this.value === page_range.data('selected_option')) {
                // previously selected option is about to remove - In this case select the default one later
                selected_option_removed = true;
            }

            $(this).remove();
        }
    });

    // update selected option based on previously selected option
    if (selected_option_removed) {
        page_range.val("0");
    } else {
        page_range.val(page_range.data('selected_option'));
    }

    // hide footer if the select has only one option (all)
    if (page_range.find("option").length <= 1) {
        footer.hide();
        // add border radius to the last row of the result
        result.find("table").addClass("border_radius");
    } else {
        // add border radius to the last row of the result
        result.find("table").removeClass("border_radius");
        footer.show();
    }

}

/*
 in case there is an error or there is no result,
 remove footer, add border radius to bottom right and left,
 and unbind click event and handler
 */
function remove_footer() {
    'use strict';
    result.off("click", "tr", select_result);
    footer.hide();
    // add border radius to the last row of the result
    result.find("table").addClass("border_radius");
}

/*
 get the search input object (not just its value)
 */
function search_query(search_object, bypass_check_last_value, reset_current_page) {
    'use strict';
    if ($.trim(search_object.value).length) {

        // If the previous value is different from the new one perform the search
        // Otherwise ignore that. This is useful when user change cursor position on the search field
        if (bypass_check_last_value || search_object.latest_value !== search_object.value) {

            if (reset_current_page) {
                // Reset the current page (label and hidden input)
                current_page.val("1");
                current_page_lbl.html("1");
            }

            // Reset selected row if there is any
            search_object.selected_row = undefined;

            /*
             If a search is in the queue to be executed while another one is coming,
             prevent the last one
             */
            if (search_object.to_be_executed) {
                clearTimeout(search_object.to_be_executed);
            }

            // Start search after the type delay
            search_object.to_be_executed = setTimeout(function () {

                // Sometimes requests with no search value get through, double check the length to avoid it
                if ($.trim(query.val()).length) {
                    // Display loading icon
                    query.addClass('ajax_loader');

                    // Send the request
                    $.ajax({
                        type: "post",
                        url: ls.url,
                        data: $(ls.form_id).serialize(),
                        dataType: "json",
                        success: function (response) {
                            if (response.status === 'success') {

                                var resultObj = $.parseJSON(response.result);

                                // set html result and total pages
                                result.find('table tbody').html(resultObj.html);

                                /*
                                 If the number of results is zero, hide the footer (pagination)
                                 also unbind click and select_result handler
                                 */
                                if (resultObj.number_of_results === 0) {
                                    remove_footer();
                                } else {
                                    /*
                                     If total number of pages is 1 there is no point to have navigation / paging
                                     */
                                    if (resultObj.total_pages > 1) {
                                        $(ls.navigation_class).show();
                                        total_page_lbl.html(resultObj.total_pages);
                                    } else {
                                        // Hide paging
                                        $(ls.navigation_class).hide();
                                    }

                                    /*
                                     Display select options based on the total number of results
                                     There is no point to have a option with the value of 10 when there is
                                     only 5 results
                                     */
                                    remove_select_options(resultObj.number_of_results);

                                    result.on("click", "tr", select_result);
                                    //footer.show();
                                }

                            } else {
                                // There is an error
                                result.find('table tbody').html(response.message);

                                remove_footer();
                            }

                        },
                        error: function () {
                            result.find('table tbody').html('Something went wront. Please refresh the page.');

                            remove_footer();
                        },
                        complete: function () {
                            /*
                             Because this is a asynchronous request
                             it may add result even after there is no query in the search field
                             */
                            if ($.trim(search_object.value).length && result.is(":hidden")) {
                                show_result();
                            }

                            query.removeClass('ajax_loader');

                        }
                    });
                    // End of request
                }

            }, ls.type_delay);

        }

    } else {
        // If search field is empty, hide the result
        // If $(ls.result_id + ":animated") is removed, it may check visibility of the result div
        // while it's animating and may not hide the div
        if (result.is(":visible") || result.is(":animated")) {
            hide_result();
        }
    }

    search_object.latest_value = search_object.value;

}

/*
 select row / item function when users click or press enter key
 */
select_result = function () {
    'use strict';
    query.val($(query.selected_row).find('td').eq(ls.select_column_index).html());
    hide_result();
};

/*
 result width and position is changed based on search input
 */
function adjust_result_position() {
    'use strict';
    // considering result div border size, place the div in the center, underneath of search input
    // outerwidth - border size of the result div
    // adjust result position
    $(result).css({left: query.position().left + 1, width: query.outerWidth() - 2});
}

$(document).ready(function () {
    'use strict';
    // Adjust result position based on search input position.
    adjust_result_position();

    // re-Adjust result position when screen resizes
    $(window).resize(function () {
        adjust_result_position();
    });

    // Set anti bot value for those that do not have JavaScript enabled
    $(ls.form_anti_bot_id).val(ls.form_anti_bot);

    // Trigger search when typing is started
    $(query).on('keyup', function (event) {

        // If enter key is pressed check if the user want to selected hovered row
        var keycode = event.keyCode || event.which;
        if ($.trim(query.val()).length && keycode === 13) {
            if ((result.is(":visible") || result.is(":animated")) && result.find("tr").length !== 0) {
                // find hovered row
                if (query.selected_row !== undefined) {
                    /*
                     Do whatever you want with the selected row
                     Instead of calling directly select function, it should be through click event
                     then easily can bind or unbind to page_range result handler
                     */
                    $(result).find("tr").trigger("click");
                } // If there is any results and hidden and the search input is in focus, show result by press enter
            } else {
                show_result();
            }
        } else {
            // If something other than enter is pressed start search immediately
            search_query(this, false, true);
        }

    });

    // While search input is in focus
    // Move among the rows, by pressing or keep pressing arrow up and down
    $(query).on('keydown', function (event) {

        var keycode = event.keyCode || event.which;
        if (keycode === 40 || keycode === 38) {
            if ($.trim(query.val()).length && result.find("tr").length !== 0) {

                if ((result.is(":visible") || result.is(":animated"))) {
                    result.find('tr').removeClass('hover');

                    if (query.selected_row === undefined) {
                        // Moving just started
                        query.selected_row = result.find("tr").eq(0);
                        $(query.selected_row).addClass("hover");
                    } else {

                        $(query.selected_row).removeClass("hover");

                        if (keycode === 40) {
                            // next
                            if ($(query.selected_row).next().length === 0) {
                                // here is the end of the table
                                query.selected_row = result.find("tr").eq(0);
                                $(query.selected_row).addClass("hover");
                            } else {
                                $(query.selected_row).next().addClass("hover");
                                query.selected_row = $(query.selected_row).next();
                            }

                        } else {
                            // previous
                            if ($(query.selected_row).prev().length === 0) {
                                // here is the end of the table
                                query.selected_row = result.find("tr").last();
                                query.selected_row.addClass("hover");
                            } else {
                                $(query.selected_row).prev().addClass("hover");
                                query.selected_row = $(query.selected_row).prev();
                            }
                        }

                    }
                } else {
                    // If there is any results and hidden and the search input is in focus, show result by press down
                    if (keycode === 40) {
                        show_result();
                    }
                }
            }
        }

    });

    // Show result when is focused
    $(query).on('focus', function () {
        // check if the result is not empty show it
        if ($.trim(query.val()).length && (result.is(":hidden") || result.is(":animated")) && result.find("tr").length !== 0) {
            search_query(this, false, true);
            show_result();
        }
    });

    // In the beginning, there is no result / tr, so we bind the event to the future tr
    $(result).on('mouseover', 'tr', function () {
        // remove all the hover classes, otherwise there are more than one hovered rows
        result.find('tr').removeClass('hover');

        // set the current selected row
        query.selected_row = this;

        $(this).addClass('hover');
    });

    // In the beginning, there is no result / tr, so we bind the event to the future tr
    $(result).on('mouseleave', 'tr', function () {
        // remove all the hover classes, otherwise there are more than one hovered rows
        result.find('tr').removeClass('hover');

        // Reset selected row
        query.selected_row = undefined;
    });

    $(result).on('click', 'tr', select_result);

    // Click doesn't work on iOS - This is to fix that
    // According to: http://stackoverflow.com/a/9380061/2045041
    var touchStartPos;
    $(document)
        // log the position of the touchstart interaction
        .bind('touchstart', function () {
            touchStartPos = $(window).scrollTop();
        })
        // log the position of the touchend interaction
        .bind('touchend', function (event) {
            // calculate how far the page has moved between
            // touchstart and end.
            var distance, clickableItem;

            distance = touchStartPos - $(window).scrollTop();

            clickableItem = $(document);

            // adding this class for devices that
            // will trigger a click event after
            // the touchend event finishes. This
            // tells the click event that we've
            // already done things so don't repeat

            clickableItem.addClass("touched");

            if (distance < 10 && distance > -10) {
                // the distance was less than 20px
                // so we're assuming it's tap and not swipe
                if (!$(event.target).closest(result).length && !$(event.target).is(query) && $(result).is(":visible")) {
                    hide_result();
                }
            }
        });


    $(document).on('click', function (event) {
        // for any non-touch device, we need
        // to still apply a click event
        // but we'll first check to see
        // if there was a previous touch
        // event by checking for the class
        // that was left by the touch event.
        if ($(this).hasClass("touched")) {
            // this item's event was already triggered via touch
            // so we won't call the function and reset this for
            // the next touch by removing the class
            $(this).removeClass("touched");
        } else {
            // there wasn't a touch event. We're
            // instead using a mouse or keyboard
            // Hide the result if outside of the result is clicked
            if (!$(event.target).closest(result).length && !$(event.target).is(query) && $(result).is(":visible")) {
                hide_result();
            }
        }
    });

    // disable the form submit on pressing enter
    $(ls.form_id).submit(function () {
        return false;
    });

    // arrow button - next
    $(ls.arrow_class).on('click', function () {

        var new_current_page;
        if (this.id === ls.next_page_id) {
            // go next if it will be lower or equal to the total
            if (parseInt(current_page.val(), 10) + 1 <= parseInt(total_page_lbl.html(), 10)) {
                new_current_page = parseInt(current_page.val(), 10) + 1;
            } else {
                return;
            }
        } else {
            // previous button
            if (parseInt(current_page.val(), 10) - 1 >= 1) {
                new_current_page = parseInt(current_page.val(), 10) - 1;
            } else {
                return;
            }
        }

        current_page.val(new_current_page);
        current_page_lbl.html(new_current_page);

        // search again
        search_query(query[0], true, false);
    });

    // Search again when the items per page dropdown is changed
    $(page_range).on('change', function () {
        // we need to pass a DOM Element: query[0]
        // In this case last value should not check against the current one
        search_query(query[0], true, true);
    });

});