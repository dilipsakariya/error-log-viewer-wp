var debounce = function(func, wait, immediate) {
    var timeout;
    wait = wait || 250;
    return function() {
        var context = this,
            args = arguments;
        var later = function() {
            timeout = null;
            if (!immediate) {
                func.apply(context, args);
            }
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) {
            func.apply(context, args);
        }
    };
};

function parseQueryString(qs) {
    var query = (qs || '?').substr(1),
        map = {};
    query.replace(/([^&=]+)=?([^&]*)(?:&+|$)/g, function(match, key, value) {
        (map[key] = map[key] || value);
    });
    return map;
}

function stripe() {
    var errors = jQuery('#wp_elv_error_list').find('article');
    errors.removeClass('alternate');
    errors.filter(':not(.hide):odd').addClass('alternate');
}

function filterSet() {
    var typeCount = {};
    var checked = jQuery('#wp_elv_type_filter').find('input:checkbox:checked').map(function() {
        return jQuery(this).val();
    }).get();
    var input = jQuery('#wp_elv_path_filter').find('input').val();
    jQuery('#wp_elv_error_list article').each(function() {
        var a = jQuery(this);
        var found = a.data('path').toLowerCase().indexOf(input.toLowerCase());
        if ((input.length && found == -1) || (jQuery.inArray(a.data('type'), checked) == -1)) {
            a.addClass('hide');
        } else {
            a.removeClass('hide');
        }
        if (found != -1) {
            if (typeCount.hasOwnProperty(a.data('type'))) {
                ++typeCount[a.data('type')];
            } else {
                typeCount[a.data('type')] = 1;
            }
        }
    });
    jQuery('#wp_elv_type_filter').find('label').each(function() {
        var type = jQuery(this).attr('class');
        if (typeCount.hasOwnProperty(type)) {
            jQuery('span', jQuery(this)).data('current', typeCount[type]);
        } else {
            jQuery('span', jQuery(this)).data('current', 0);
        }
    });
}

function sortEntries(type, order) {
    var aList = jQuery('#wp_elv_error_list').find('article');
    aList.sort(function(a, b) {
        if (!isNaN(jQuery(a).data(type))) {
            var entryA = parseInt(jQuery(a).data(type));
            var entryB = parseInt(jQuery(b).data(type));
        } else {
            var entryA = jQuery(a).data(type);
            var entryB = jQuery(b).data(type);
        }
        if (order == 'asc') {
            return (entryA < entryB) ? -1 : (entryA > entryB) ? 1 : 0;
        }
        return (entryB < entryA) ? -1 : (entryB > entryA) ? 1 : 0;
    });
    jQuery('section').html(aList);
}
jQuery(document).ready(function($) {
    // $('#wp_elv_error_log_refresh').on('click', function (e) {
    //        e.preventDefault();
    //        var date = $('#date').val();
    // });
    $( "#wp_elv_datepicker,#wp_elv_select_date" ).datepicker({
        format: ajax_script_object.date_format
    });
    $(document).on('change', '#wp_elv_datepicker,#wp_elv_select_date', function(){
        var date_format_php = ajax_script_object.date_format_php;
        var date_val = $(this).val();
        if (date_format_php == 'F j, Y') {
            var date_val_arr = date_val.split(' ');
            date_val_arr[0]  = ajax_script_object.months[date_val_arr[0]]
            date_val = date_val_arr.join(' ');
            $(this).val(date_val);
        }
    });
    $('#wp_elv_skip_to_bottom').on('click', function() {
        $(document).scrollTop($(document).height());
    });
    $('#wp_elv_skip_to_top').on('click', function() {
        document.body.scrollTop = 0;
        document.documentElement.scrollTop = 0;
    });
    $('#wp_elv_error_log_purge').on('click', function() {
        var r = confirm("Are you sure want to delete this log?");
        if (r == true) {
            var wp_elv_error_log = $('#wp_elv_error_log').val();
            jQuery.ajax({
                type: 'POST',
                url: ajax_script_object.ajax_url,
                dataType: "json",
                data: {
                    'action': 'wp_elv_purge_log',
                    'wp_elv_nonce': ajax_script_object.purge_log_nonce,
                    'wp_elv_error_log': wp_elv_error_log
                },
                success: function(data) {
                    // alert(data);
                    if (data.success == 1) {
                        window.location.reload();
                    } else {
                        alert(data.msg);
                    }
                }
            });
        }
    });
    $('#wp_elv_type_filter').find('input:checkbox').on('change', function() {
        filterSet();
        visible();
    });
    $('#wp_elv_path_filter').find('input').on('keyup', debounce(function() {
        filterSet();
        visible();
    }));
    $('#wp_elv_sort_options').find('a').on('click', function() {
        var qs = parseQueryString($(this).attr('href'));
        sortEntries(qs.type, qs.order);
        $(this).attr('href', '?type=' + qs.type + '&order=' + (qs.order == 'asc' ? 'desc' : 'asc'));
        if (qs.type == 'type') {
            $('span', $(this)).text((qs.order == 'asc' ? 'z-a' : 'a-z'));
        } else {
            $('span', $(this)).text((qs.order == 'asc' ? 'desc' : 'asc'));
        }
        return false;
    });
    $(document).on('click', 'a.codeblock, a.traceblock', function(e) {
        $('#' + $(this).data('for')).toggle();
        return false;
    });
    stripe();
});
$ = jQuery;
$(document).ready(function() {
    var wp_elv_log_list_table = $('#wp_elv_log_list_table').dataTable({
        "processing": true,
        "serverSide": true,
        'serverMethod': 'post',
        "searching": false,
        "dataType": "json",
        "dom": 'Bfrtip',
        "paging": true,
        "visible": false,
        "lengthChange": true,
        "pageLength": 10,
        "order": [
            [0, "desc"]
        ],
        "bSort": true,
        "fnDrawCallback": function(oSettings) {
            if ($('#wp_elv_log_list_table tr').length > 5) {
                $('.dataTables_paginate').show();
            }
        },
        "ajax": datatable.datatable_ajax_url,
        columns: [{
            data: 'created_at'
        }, {
            data: 'plugin'
        }, {
            data: 'theme'
        }, {
            data: 'others'
        }, {
            data: 'wp_elv_log_path'
        }, {
            data: 'action'
        }],
        // Needs button container
    });
    $('body').on('click', '.wp_elv_datatable_delete', function() {
        var r = confirm("Are you sure want to delete this log?");
        if (r == true) {
            var wp_elv_datatable_deleteid = $(this)[0].id;
            jQuery.ajax({
                type: 'POST',
                url: ajax_script_object.ajax_url,
                dataType: "json",
                data: {
                    'action': 'wp_elv_datatable_delete_data',
                    '_wpnonce': ajax_script_object.delete_data_nonce,
                    'wp_elv_datatable_deleteid': wp_elv_datatable_deleteid
                },
                success: function(data) {
                    if (data.success == 1) {
                        window.location.reload();
                    } else {
                        alert(data.msg);
                    }
                }
            });
        }
    });
});
/*deactivation*/
(function($) {
    // alert('dd');
    $(function() {
        var pluginSlug = 'wp-error-log-viewer';
        // Code to fire when the DOM is ready.
        $(document).on('click', 'tr[data-slug="' + pluginSlug + '"] .deactivate', function(e) {
            e.preventDefault();
            $('.wp_elv-popup-overlay').addClass('wp_elv-active');
            $('body').addClass('wp_elv-hidden');
        });
        $(document).on('click', '.wp_elv-popup-button-close', function() {
            close_popup();
        });
        $(document).on('click', ".wp_elv-serveypanel,tr[data-slug='" + pluginSlug + "'] .deactivate", function(e) {
            e.stopPropagation();
        });
        $(document).click(function() {
            close_popup();
        });
        $('.wp_elv-reason label').on('click', function() {
            if ($(this).find('input[type="radio"]').is(':checked')) {
                //$('.wp_elv-anonymous').show();
                $(this).next().next('.wp_elv-reason-input').show().end().end().parent().siblings().find('.wp_elv-reason-input').hide();
            }
        });
        $('input[type="radio"][name="wp_elv-selected-reason"]').on('click', function(event) {
            $(".wp_elv-popup-allow-deactivate").removeAttr('disabled');
            $(".wp_elv-popup-skip-feedback").removeAttr('disabled');
            $('.message.error-message').hide();
            $('.wp_elv-pro-message').hide();
        });
        $('.wp_elv-reason-pro label').on('click', function() {
            if ($(this).find('input[type="radio"]').is(':checked')) {
                $(this).next('.wp_elv-pro-message').show().end().end().parent().siblings().find('.wp_elv-reason-input').hide();
                $(this).next('.wp_elv-pro-message').show()
                $('.wp_elv-popup-allow-deactivate').attr('disabled', 'disabled');
                $('.wp_elv-popup-skip-feedback').attr('disabled', 'disabled');
            }
        });
        $(document).on('submit', '#wp_elv-deactivate-form', function(event) {
            event.preventDefault();
            var _reason = $('input[type="radio"][name="wp_elv-selected-reason"]:checked').val();
            var _reason_details = '';
            var deactivate_nonce = $('.wp_elv_error_log_deactivation_nonce').val();
            if (_reason == 2) {
                _reason_details = $(this).find("input[type='text'][name='better_plugin']").val();
            } else if (_reason == 7) {
                _reason_details = $(this).find("input[type='text'][name='other_reason']").val();
            }
            if ((_reason == 7 || _reason == 2) && _reason_details == '') {
                $('.message.error-message').show();
                return;
            }
            $.ajax({
                url: ajax_script_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'wp_elv_error_log_deactivation',
                    reason: _reason,
                    reason_detail: _reason_details,
                    wp_elv_error_log_deactivation_nonce: deactivate_nonce
                },
                beforeSend: function() {
                    $(".wp_elv-spinner").show();
                    $(".wp_elv-popup-allow-deactivate").attr("disabled", "disabled");
                }
            }).done(function() {
                $(".wp_elv-spinner").hide();
                $(".wp_elv-popup-allow-deactivate").removeAttr("disabled");
                window.location.href = $("tr[data-slug='" + pluginSlug + "'] .deactivate a").attr('href');
            });
        });
        $('.wp_elv-popup-skip-feedback').on('click', function(e) {
            // e.preventDefault();
            window.location.href = $("tr[data-slug='" + pluginSlug + "'] .deactivate a").attr('href');
        })

        function close_popup() {
            $('.wp_elv-popup-overlay').removeClass('wp_elv-active');
            $('#wp_elv-deactivate-form').trigger("reset");
            $(".wp_elv-popup-allow-deactivate").attr('disabled', 'disabled');
            $(".wp_elv-reason-input").hide();
            $('body').removeClass('wp_elv-hidden');
            $('.message.error-message').hide();
            $('.wp_elv-pro-message').hide();
        }
    });
})(jQuery);

function visible() {
    var vis = jQuery('#wp_elv_error_list').find('article').filter(':not(.hide)');
    var len = vis.length;
    if (len == 0) {
        jQuery('#nothingToShow').removeClass('hide');
        jQuery('.log_entries').text('0 entries showing (' + script_object.total + ' filtered out)');
    } else {
        jQuery('#nothingToShow').addClass('hide');
        if (len == script_object.total) {
            jQuery('.log_entries').text(script_object.total + ' distinct entr' + ((script_object.total) == 1 ? 'y' : 'ies'));
        } else {
            jQuery('.log_entries').text(len + ' distinct entr' + (len == 1 ? 'y' : 'ies') + ' showing (' + (script_object.total - len) + ' filtered out)');
        }
    }
    jQuery('#wp_elv_type_filter').find('label span').each(function() {
        var count = (jQuery('#wp_elv_path_filter').find('input').val() == '' ? jQuery(this).data('total') : jQuery(this).data('current') + '/' + jQuery(this).data('total'));
        jQuery(this).text(count);
    });
    stripe();
}
if (script_object.error_type) {
    jQuery('input:checkbox').removeAttr('checked');
    jQuery('input[type=checkbox]').each(function() {
        var a = jQuery(this);
        var checkedvalue = jQuery(this).val();
        a.addClass(checkedvalue);
        jQuery('.'+script_object.error_type).prop("checked", true);
        var typeCount = {};
        var checked = jQuery('#wp_elv_type_filter').find('input:checkbox:checked').map(function() {
            return jQuery(this).val();
        }).get();
        var input = jQuery('#wp_elv_path_filter').find('input').val();
        jQuery('#wp_elv_error_list article').each(function() {
            var a = jQuery(this);
            var found = a.data('path').toLowerCase().indexOf(input.toLowerCase());
            if ((input.length && found == -1) || (jQuery.inArray(a.data('type'), checked) == -1)) {
                a.addClass('hide');
            } else {
                a.removeClass('hide');
            }
            if (found != -1) {
                if (typeCount.hasOwnProperty(a.data('type'))) {
                    ++typeCount[a.data('type')];
                } else {
                    typeCount[a.data('type')] = 1;
                }
            }
            jQuery("input[type=checkbox]").change(function() {
                if (jQuery(this).is(":checked")) {
                    jQuery('#wp_elv_skip_to_top').show();
                } else if (jQuery(this).is(":not(:checked)")) {
                    jQuery('#wp_elv_skip_to_top').hide();
                }
            });
        });
        jQuery('#wp_elv_type_filter').find('label').each(function() {
            var type = jQuery(this).attr('class');
            if (typeCount.hasOwnProperty(type)) {
                jQuery('span', jQuery(this)).data('current', typeCount[type]);
            } else {
                jQuery('span', jQuery(this)).data('current', 0);
            }
        });
    });
}