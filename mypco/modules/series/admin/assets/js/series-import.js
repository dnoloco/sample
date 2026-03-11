/**
 * Series Import – Admin JavaScript
 *
 * Handles the two-step import flow:
 * 1. Fetch episodes from Planning Center Publishing
 * 2. Review and import selected episodes as WordPress posts
 */
(function($) {
    'use strict';

    var i18n   = mypcoImport.i18n || {};
    var episodes = [];

    $(document).ready(function() {

        // =====================================================================
        // Step 1: Fetch Episodes
        // =====================================================================

        $('#mypco-import-fetch-btn').on('click', function() {
            var $btn    = $(this);
            var $status = $('#mypco-import-fetch-status');

            $btn.prop('disabled', true).text(i18n.fetching);
            $status.html('');

            $.post(mypcoImport.ajaxUrl, {
                action: 'mypco_import_fetch_episodes',
                nonce:  mypcoImport.nonce
            }, function(response) {
                $btn.prop('disabled', false).text($btn.data('original-text') || 'Fetch from Planning Center');

                if (!response.success) {
                    $status.html('<span class="mypco-import-error">' + (response.data.message || i18n.fetchError) + '</span>');
                    return;
                }

                episodes = response.data.episodes || [];
                renderEpisodeTable(episodes);
                $('#mypco-import-step-preview').slideDown(200);

            }).fail(function() {
                $btn.prop('disabled', false).text($btn.data('original-text') || 'Fetch from Planning Center');
                $status.html('<span class="mypco-import-error">' + i18n.fetchError + '</span>');
            });
        });

        // Store original button text
        $('#mypco-import-fetch-btn').data('original-text', $('#mypco-import-fetch-btn').text());

        // =====================================================================
        // Step 2: Render Episode Table
        // =====================================================================

        function renderEpisodeTable(episodes) {
            var $tbody   = $('#mypco-import-tbody');
            var $summary = $('#mypco-import-summary');
            $tbody.empty();

            var newCount = 0;
            var existingCount = 0;

            $.each(episodes, function(idx, ep) {
                if (ep.already_imported) {
                    existingCount++;
                } else {
                    newCount++;
                }

                var mediaHtml = [];
                if (ep.has_video) mediaHtml.push('<span class="mypco-import-media-tag mypco-media-video">' + i18n.video + '</span>');
                if (ep.has_audio) mediaHtml.push('<span class="mypco-import-media-tag mypco-media-audio">' + i18n.audio + '</span>');
                if (ep.has_sermon_audio) mediaHtml.push('<span class="mypco-import-media-tag mypco-media-sermon-audio">' + i18n.sermonAudio + '</span>');
                if (ep.has_art) mediaHtml.push('<span class="mypco-import-media-tag mypco-media-art">' + i18n.art + '</span>');
                if (ep.has_resources) mediaHtml.push('<span class="mypco-import-media-tag mypco-media-files">' + i18n.files + '</span>');
                if (!mediaHtml.length) mediaHtml.push('<span class="mypco-import-media-tag mypco-media-none">' + i18n.none + '</span>');

                var statusHtml = ep.already_imported
                    ? '<span class="mypco-import-badge mypco-import-badge-skip">' + i18n.alreadyExists + '</span>'
                    : '<span class="mypco-import-badge mypco-import-badge-new">New</span>';

                var $row = $(
                    '<tr data-episode-id="' + ep.id + '"' + (ep.already_imported ? ' class="mypco-import-row-existing"' : '') + '>' +
                        '<th class="check-column">' +
                            '<input type="checkbox" name="import_episodes[]" value="' + ep.id + '"' +
                            (ep.already_imported ? '' : ' checked') +
                            (ep.already_imported ? ' disabled' : '') + ' />' +
                        '</th>' +
                        '<td><strong>' + escHtml(ep.title) + '</strong></td>' +
                        '<td>' + escHtml(ep.speaker_name || '—') + '</td>' +
                        '<td>' + escHtml(ep.series_name || '—') + '</td>' +
                        '<td>' + escHtml(ep.published_date || '—') + '</td>' +
                        '<td>' + mediaHtml.join(' ') + '</td>' +
                        '<td class="mypco-import-row-status">' + statusHtml + '</td>' +
                    '</tr>'
                );

                $tbody.append($row);
            });

            $summary.html(
                '<strong>' + episodes.length + '</strong> ' + i18n.episodesFound +
                ' <strong>' + newCount + '</strong> ' + i18n.newEpisodes +
                ' <strong>' + existingCount + '</strong> ' + i18n.alreadyImported
            );
        }

        // =====================================================================
        // Select All / Deselect All
        // =====================================================================

        $('#mypco-import-select-all').on('change', function() {
            var checked = $(this).is(':checked');
            $('#mypco-import-tbody input[type="checkbox"]:not(:disabled)').prop('checked', checked);
        });

        // =====================================================================
        // Step 3: Run Import
        // =====================================================================

        $('#mypco-import-run-btn').on('click', function() {
            var $btn    = $(this);
            var $status = $('#mypco-import-run-status');

            var selectedIds = [];
            $('#mypco-import-tbody input[type="checkbox"]:checked:not(:disabled)').each(function() {
                selectedIds.push($(this).val());
            });

            if (!selectedIds.length) {
                $status.html('<span class="mypco-import-error">' + i18n.noEpisodes + '</span>');
                return;
            }

            $btn.prop('disabled', true).text(i18n.importing);
            $status.html('');

            // Mark selected rows as importing
            $.each(selectedIds, function(idx, id) {
                var $row = $('#mypco-import-tbody tr[data-episode-id="' + id + '"]');
                $row.find('.mypco-import-row-status').html(
                    '<span class="mypco-import-badge mypco-import-badge-pending">' + i18n.importing + '</span>'
                );
            });

            $.post(mypcoImport.ajaxUrl, {
                action:      'mypco_import_run',
                nonce:       mypcoImport.nonce,
                episode_ids: selectedIds
            }, function(response) {
                $btn.prop('disabled', false).text($btn.data('original-text') || 'Import Selected');

                if (!response.success) {
                    $status.html('<span class="mypco-import-error">' + (response.data.message || i18n.error) + '</span>');
                    return;
                }

                var data = response.data;

                // Update individual row statuses
                if (data.results) {
                    $.each(data.results, function(idx, result) {
                        var $row = $('#mypco-import-tbody tr[data-episode-id="' + result.id + '"]');
                        var badgeClass, badgeText;

                        if (result.status === 'imported') {
                            badgeClass = 'mypco-import-badge-success';
                            badgeText  = i18n.imported;
                            $row.find('input[type="checkbox"]').prop('checked', false).prop('disabled', true);
                        } else if (result.status === 'skipped') {
                            badgeClass = 'mypco-import-badge-skip';
                            badgeText  = i18n.skipped;
                        } else {
                            badgeClass = 'mypco-import-badge-error';
                            badgeText  = result.message || i18n.error;
                        }

                        $row.find('.mypco-import-row-status').html(
                            '<span class="mypco-import-badge ' + badgeClass + '" title="' + escHtml(result.message || '') + '">' + escHtml(badgeText) + '</span>'
                        );
                    });
                }

                // Show results summary
                var resultsHtml =
                    '<div class="notice notice-success inline" style="margin:10px 0;">' +
                        '<p><strong>' + i18n.importComplete + '</strong> ' +
                        data.imported + ' ' + i18n.imported.toLowerCase() + ', ' +
                        data.skipped + ' ' + i18n.skipped.toLowerCase() + '.</p>' +
                    '</div>';

                if (data.errors && data.errors.length) {
                    resultsHtml += '<div class="notice notice-warning inline" style="margin:10px 0;">';
                    resultsHtml += '<p><strong>Errors:</strong></p><ul>';
                    $.each(data.errors, function(idx, err) {
                        resultsHtml += '<li>' + escHtml(err) + '</li>';
                    });
                    resultsHtml += '</ul></div>';
                }

                $('#mypco-import-results').html(resultsHtml);
                $('#mypco-import-step-results').slideDown(200);

            }).fail(function(jqXHR) {
                $btn.prop('disabled', false).text($btn.data('original-text') || 'Import Selected');
                var msg = i18n.error;
                if (jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                    msg = jqXHR.responseJSON.data.message;
                } else if (jqXHR.status === 0) {
                    msg = i18n.requestTimeout;
                } else if (jqXHR.statusText) {
                    msg = i18n.error + ': ' + jqXHR.statusText;
                }
                $status.html('<span class="mypco-import-error">' + escHtml(msg) + '</span>');

                // Reset row badges back from "Importing..." to "New"
                $.each(selectedIds, function(idx, id) {
                    var $row = $('#mypco-import-tbody tr[data-episode-id="' + id + '"]');
                    $row.find('.mypco-import-row-status').html(
                        '<span class="mypco-import-badge mypco-import-badge-new">New</span>'
                    );
                });
            });
        });

        // Store original button text
        $('#mypco-import-run-btn').data('original-text', $('#mypco-import-run-btn').text());

        // =====================================================================
        // Helpers
        // =====================================================================

        function escHtml(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }
    });
})(jQuery);
