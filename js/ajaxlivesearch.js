/*global $, clearTimeout, setTimeout, document, window, jQuery*/
(function ($) {
    "use strict";
    $.fn.ajaxlivesearch = function (options) {
        /**
         * Start validation
         */
        if (options.loaded_at === undefined) {
            throw 'loaded_at must be defined';
        }

        if (options.token === undefined) {
            throw 'token must be defined';
        }
        /**
         * Finish validation
         */

        var ls = {
            url: "core/AjaxProcessor.php",
            // This should be the same as the same parameter's value in config file
            form_anti_bot: "ajaxlivesearch_guard",
            cache: false,
            /**
             * Beginning of classes
             */
            form_anti_bot_class: "ls_anti_bot",
            footer_class: "ls_result_footer",
            next_page_class: "ls_next_page",
            previous_page_class: "ls_previous_page",
            page_limit: "page_limit",
            result_wrapper_class: "ls_result_div",
            result_class: "ls_result_main",
            container_class: "ls_container",
            pagination_class: "pagination",
            form_class: "search",
            loaded_at_class: "ls_page_loaded_at",
            token_class: "ls_token",
            current_page_hidden_class: "ls_current_page",
            current_page_lbl_class: "ls_current_page_lbl",
            last_page_lbl_class: "ls_last_page_lbl",
            total_page_lbl_class: "ls_last_page_lbl",
            page_range_class: "ls_items_per_page",
            page_ranges: [0, 5, 10],
            page_range_default: 5,
            navigation_class: "navigation",
            arrow_class: "arrow",
            /**
             * End of classes
             */
            slide_speed: "fast",
            type_delay: 350,
            max_input: 20,
            min_chars_to_search: 0
        };

        ls = $.extend(ls, options);

        /**
         * Remove footer, add border radius to bottom right and left
         *
         * @param footer
         * @param result
         */
        function remove_footer(footer, result) {
            footer.hide();
            // add border radius to the last row of the result
            result.find("table").addClass("border_radius");
        }

        /**
         * Add footer, and remove border radius from bottom right and left
         *
         * @param footer
         * @param result
         */
        function show_footer(footer, result) {
            // add border radius to the last row of the result
            result.find("table").removeClass("border_radius");
            footer.show();
        }

        /**
         * Return minimum value of
         *
         * @param page_range
         */
        function get_minimum_option_value(page_range) {
            var minimumOptionValue;
            var i;
            var all_options = page_range.find('option');
            for (i = 0; i < all_options.length; i += 1) {
                if (minimumOptionValue === undefined && parseInt(all_options[i].value) !== 0) {
                    minimumOptionValue = all_options[i].value;
                } else {
                    if (parseInt(all_options[i].value) < parseInt(minimumOptionValue) && parseInt(all_options[i].value) !== 0) {
                        minimumOptionValue = all_options[i].value;
                    }
                }
            }

            return minimumOptionValue;
        }

        /**
         * Return the relevant element of the form
         *
         * @param form
         * @param key
         * @param options
         * @returns {*}
         */
        function getFormInfo(form, key, options) {
            if (form === undefined || options === undefined) {
                throw 'Form or Options is missing';
            }

            var find = '.' + options.current_page_hidden_class;

            switch (key) {
                case 'result':
                    return form.find('.' + options.result_wrapper_class);
                case 'footer':
                    return form.find('.' + options.footer_class);
                case 'arrow':
                    return form.find('.' + options.arrow_class);
                case 'navigation':
                    return form.find('.' + options.navigation_class);
                case 'current_page':
                    return form.find(find);
                case 'current_page_lbl':
                    return form.find('.' + options.current_page_lbl_class);
                case 'total_page_lbl':
                    return form.find('.' + options.total_page_lbl_class);
                case 'page_range':
                    return form.find('.' + options.page_range_class);
                default:
                    throw 'Key: ' + key + ' is not found';
            }
        }

        /**
         * Slide up the result
         *
         * @param result
         * @param options
         */
        function hide_result(result, options) {
            result.slideUp(options.slide_speed);
        }

        /**
         * Slide down the result
         *
         * @param result
         * @param options
         */
        function show_result(result, options) {
            result.slideDown(options.slide_speed);
        }

        /**
         * Get the search input object (not just its value)
         *
         * @param search_object
         * @param form
         * @param options
         * @param bypass_check_last_value
         * @param reset_current_page
         */
        function search(search_object, form, options, bypass_check_last_value, reset_current_page) {
            var result = getFormInfo(form, 'result', options);

            if ($.trim(search_object.value).length && $.trim(search_object.value).length >= options.min_chars_to_search) {
                /**
                 * If the previous value is different from the new one perform the search
                 * Otherwise ignore that. This is useful when user change cursor position on the search field
                 */
                if (bypass_check_last_value || search_object.latest_value !== search_object.value) {
                    if (reset_current_page) {
                        var current_page = getFormInfo(form, 'current_page', options);
                        var current_page_lbl = getFormInfo(form, 'current_page_lbl', options);

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
                        if ($.trim(search_object.value).length) {
                            // Display loading icon
                            $(search_object).addClass('ajax_loader');

                            var navigation = getFormInfo(form, 'navigation', options);
                            var total_page_lbl = getFormInfo(form, 'total_page_lbl', options);
                            var page_range = getFormInfo(form, 'page_range', options);
                            var footer = getFormInfo(form, 'footer', options);

                            var toPostData = $(form).serializeArray();
                            var customData = $(search_object).data();

                            $.each(customData, function(k, v){
                                var dataObj = {};
                                dataObj['name'] = k;
                                dataObj['value'] = v;

                                toPostData.push(dataObj);
                            });

                            // Send the request
                            $.ajax({
                                type: "post",
                                url: ls.url,
                                data: toPostData,
                                dataType: "json",
                                cache: ls.cache,
                                success: function (response) {
                                    if (response.status === 'success') {
                                        var responseResultObj = $.parseJSON(response.result);

                                        // set html result and total pages
                                        result.find('table tbody').html(responseResultObj.html);

                                        /*
                                         If the number of results is zero, hide the footer (pagination)
                                         also unbind click and select_result handler
                                         */
                                        if (responseResultObj.number_of_results === 0) {
                                            remove_footer(footer, result);
                                        } else {
                                            /*
                                             If total number of pages is 1 there is no point to have navigation / paging
                                             */
                                            if (responseResultObj.total_pages > 1) {
                                                navigation.show();
                                                total_page_lbl.html(responseResultObj.total_pages);
                                            } else {
                                                // Hide paging
                                                navigation.hide();
                                            }

                                            /**
                                             * Display select options based on the total number of results
                                             * There is no point to have a option with the value of 10 when there is
                                             * only 5 results
                                             */
                                            //remove_select_options(responseResultObj.number_of_results, page_range, result, footer);

                                            var minimumOptionValue = get_minimum_option_value(page_range);

                                            // if is visible
                                            if (footer.is(":visible")) {
                                                // if number of results is less than minimum range except 0: Hide
                                                if (parseInt(responseResultObj.number_of_results) <= parseInt(minimumOptionValue)) {
                                                    remove_footer(footer, result);
                                                } else {
                                                    show_footer(footer, result);
                                                }
                                            } else {
                                                // if number of results is NOT less than minimum range except 0: show
                                                if (parseInt(responseResultObj.number_of_results) > parseInt(minimumOptionValue)) {
                                                    show_footer(footer, result);
                                                } else {
                                                    remove_footer(footer, result);
                                                }
                                            }
                                        }

                                    } else {
                                        // There is an error
                                        result.find('table tbody').html(response.message);

                                        remove_footer(footer, result);
                                    }

                                },
                                error: function () {
                                    result.find('table tbody').html('Something went wrong. Please refresh the page.');

                                    remove_footer(footer, result);
                                },
                                complete: function (e) {
                                    /*
                                     Because this is a asynchronous request
                                     it may add result even after there is no query in the search field
                                     */
                                    if ($.trim(search_object.value).length && result.is(":hidden")) {
                                        show_result(result, options);
                                    }

                                    $(search_object).removeClass('ajax_loader');

                                    if (options.onAjaxComplete !== undefined) {
                                        var data = {this: this};
                                        options.onAjaxComplete(e, data);
                                    }
                                }
                            });
                            // End of request
                        }

                    }, ls.type_delay);

                }
            } else {
                // If search field is empty, hide the result
                if (result.is(":visible") || result.is(":animated")) {
                    hide_result(result, options);
                }
            }

            search_object.latest_value = search_object.value;
        }

        /**
         * Generate Form html for the text input
         *
         * @param elem
         * @param ls
         * @returns {string}
         */
        function generateFormHtml(elem, ls) {
            var elem_id = elem.attr('id');
            elem.attr('autocomplete', 'off');
            elem.attr('name', 'ls_query');
            elem.addClass('ls_query');
            elem.attr('maxlength', ls.max_input);

            var optionsHtml = '', i, selected, option_value;
            var option_name = '';
            for (i = 0; i < ls.page_ranges.length; i += 1) {
                option_value = ls.page_ranges[i];
                if (option_value === 0) {
                    option_name = 'All';
                } else {
                    option_name = option_value;
                }

                if (ls.page_range_default === option_value) {
                    selected = 'selected';
                } else {
                    selected = '';
                }

                optionsHtml += '<option value="' + option_value + '" ' + selected + '>' + option_name + '</option>';
            }

            var paginationHtml = '<div class="' + ls.footer_class + '">' +
                    '<div class="col ' + ls.page_limit + '">' +
                    '<select name="ls_items_per_page" class="' + ls.page_range_class + '">' +
                    optionsHtml +
                    '</select>' +
                    '</div>' +
                    '<div class="col ' + ls.navigation_class + '">' +
                    '<i class="icon-left-circle ' + ls.arrow_class + ' ' + ls.previous_page_class + '">' +
                    '</i>' +
                    '</div>' +
                    '<div class="col ' + ls.navigation_class + ' ' + ls.pagination_class + '">' +
                    '<label class="' + ls.current_page_lbl_class + '">1</label> / ' +
                    '<label class="' + ls.last_page_lbl_class + '"></label>' +
                    '</div>' +
                    '<div class="col ' + ls.navigation_class + '">' +
                    '<i class="icon-right-circle ' + ls.arrow_class + ' ' + ls.next_page_class + '">' +
                    '</i>' +
                    '</div>' +
                    '</div>';

            var wrapper = '<div class="' + ls.container_class + '">' +
                    '<form accept-charset="UTF-8" class="' + ls.form_class + '" id="' + ls.form_class + '_' + elem_id + '" name="ls_form">' +
                    '</form>' +
                    '</div>';

            var hidden_inputs = '<input type="hidden" name="ls_anti_bot" class="' + ls.form_anti_bot_class + '" value="">' +
                    '<input type="hidden" name="ls_token" class="' + ls.token_class + '" value="' + ls.token + '">' +
                    '<input type="hidden" name="ls_page_loaded_at" class="' + ls.loaded_at_class + '" value="' + ls.loaded_at + '">' +
                    '<input type="hidden" name="ls_current_page" class="' + ls.current_page_hidden_class + '" value="1">' +
                    '<input type="hidden" name="ls_query_id" value="' + elem_id + '">';

            var result = '<div class="' + ls.result_wrapper_class + '">' +
                    '<div class="' + ls.result_class + '">' +
                    '<table><tbody></tbody></table>' +
                    '</div>' + paginationHtml + '</div>';

            elem.wrap(wrapper);
            elem.before(hidden_inputs);
            elem.after(result);
        }

        this.each(function () {
            var query = $(this);
            var query_id = query.attr('id');

            generateFormHtml(query, ls);

            var form = $('#' + ls.form_class + '_' + query_id);
            var result = getFormInfo(form, 'result', ls);
            var arrow = getFormInfo(form, 'arrow', ls);
            var current_page = getFormInfo(form, 'current_page', ls);
            var current_page_lbl = getFormInfo(form, 'current_page_lbl', ls);
            var total_page_lbl = getFormInfo(form, 'total_page_lbl', ls);
            var page_range = getFormInfo(form, 'page_range', ls);

            /**
             * Start binding
             */
            // Trigger search when typing is started
            query.on('keyup', function (event) {
                // If enter key is pressed check if the user wants to select hovered row
                var keycode = event.keyCode || event.which;
                if ($.trim(query.val()).length && keycode === 13) {
                    if (!(result.is(":visible") || result.is(":animated")) || parseInt(result.find("tr").length) === 0) {
                        show_result(result, ls);
                    } else {
                        if (query.selected_row !== undefined) {
                            var data = {selected: $(query.selected_row), this: this, searchField: query};

                            if (options.onResultEnter !== undefined) {
                                options.onResultEnter(event, data);
                            }
                        }
                    }
                } else {
                    // If something other than enter is pressed start search immediately
                    search(this, form, ls, false, true);
                }
            });

            /**
             * While search input is in focus
             * Move among the rows, by pressing or keep pressing arrow up and down
             */
            query.on('keydown', function (event) {
                var keycode = event.keyCode || event.which;
                if (keycode === 40 || keycode === 38) {
                    if ($.trim(query.val()).length && result.find("tr").length !== 0) {
                        if (result.is(":visible") || result.is(":animated")) {
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
                                show_result(result, ls);
                            }
                        }
                    }

                    // prevent cursor from jumping to beginning or end of input
                    return false;
                }
            });

            // Show result when is focused
            query.on('focus', function () {
                // check if the result is not empty show it
                if ($.trim(query.val()).length && (result.is(":hidden") || result.is(":animated")) && result.find("tr").length !== 0) {
                    search(this, form, ls, false, true);
                    show_result(result, ls);
                }
            });

            // In the beginning, there is no result / tr, so we bind the event to the future tr
            result.on('mouseover', 'tr', function () {
                // remove all the hover classes, otherwise there are more than one hovered rows
                result.find('tr').removeClass('hover');

                // set the current selected row
                query.selected_row = this;

                $(this).addClass('hover');
            });

            // In the beginning, there is no result / tr, so we bind the event to the future tr
            result.on('mouseleave', 'tr', function () {
                // remove all the hover classes, otherwise there are more than one hovered rows
                result.find('tr').removeClass('hover');

                // Reset selected row
                query.selected_row = undefined;
            });

            // disable the form submit on pressing enter
            form.submit(function () {
                return false;
            });

            // arrow button - next
            arrow.on('click', function () {
                var new_current_page;

                if ($(this).hasClass(ls.next_page_class)) {
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
                search(query[0], form, ls, true, false);
            });

            // Search again when the items per page dropdown is changed
            page_range.on('change', function () {
                /**
                 * we need to pass a DOM Element: query[0]
                 * In this case last value should not check against the current one
                 */
                search(query[0], form, ls, true, true);
            });

            result.css({left: query.position().left + 1, width: query.outerWidth() - 2});

            // re-Adjust result position when screen resizes
            $(window).resize(function () {
                //adjust_result_position();
                result.css({left: query.position().left + 1, width: query.outerWidth() - 2});
            });

            /**
             * Click doesn't work on iOS - This is to fix that
             * According to: http://stackoverflow.com/a/9380061/2045041
             */
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

                    /**
                     * adding this class for devices that
                     * will trigger a click event after
                     * the touchend event finishes. This
                     * tells the click event that we've
                     * already done things so don't repeat
                     */
                    clickableItem.addClass("touched");

                    if (distance < 10 && distance > -10) {
                        /**
                         * Distance was less than 20px
                         * so we're assuming it's tap and not swipe
                         */
                        if (!$(event.target).closest(result).length && !$(event.target).is(query) && $(result).is(":visible")) {
                            hide_result(result, ls);
                        }
                    }
                });

            $(document).on('click', function (event) {
                /**
                 * For any non-touch device, we need to still apply a click event but we'll first check to see if there
                 * was a previous touch event by checking for the class that was left by the touch event.
                 */
                if ($(this).hasClass("touched")) {
                    /**
                     * This item's event was already triggered via touch so we won't call the function and reset this
                     * for the next touch by removing the class
                     */
                    $(this).removeClass("touched");
                } else {
                    /**
                     * There wasn't a touch event. We're instead using a mouse or keyboard hide the result if outside
                     * of the result is clicked
                     */
                    if (!$(event.target).closest(result).length && !$(event.target).is(query) && $(result).is(":visible")) {
                        hide_result(result, ls);
                    }
                }
            });
            /**
             * Finish binding
             */

            /**
             * Custom Events
             */
            $(result).on('click', 'tr', function (e) {
                var data = {selected: $(query.selected_row), this: this, searchField: query};

                if (options.onResultClick !== undefined) {
                    options.onResultClick(e, data);
                }
            });

            /**
             * Custom Triggers
             */
            $(this).on('ajaxlivesearch:hide_result', function () {
                hide_result(result, ls);
            });

            $(this).on('ajaxlivesearch:search', function (e, params) {
                $(this).val(params.query);
                search(this, form, ls, true, true);
            });
        });

        // Set anti bot value for those that do not have JavaScript enabled
        $('.' + ls.form_anti_bot_class).val(ls.form_anti_bot);

        // keep chaining
        return this;
    };
})(jQuery);