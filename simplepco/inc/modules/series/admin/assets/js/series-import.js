/**
 * Series Import – Admin JavaScript
 *
 * Handles the two-step import flow:
 * 1. Fetch episodes from Planning Center Publishing
 * 2. Review and import selected episodes as WordPress posts
 */
(function($) {
    'use strict';

    var i18n   = simplepcoImport.i18n || {};
    var episodes = [];

    $(document).ready(function() {

        // =====================================================================
        // Step 1: Fetch Episodes
        // =====================================================================

        $('#simplepco-import-fetch-btn').on('click', function() {
            var $btn    = $(this);
            var $status = $('#simplepco-import-fetch-status');

            $btn.prop('disabled', true).text(i18n.fetching);
            $status.html('');

            $.post(simplepcoImport.ajaxUrl, {
                action: 'simplepco_import_fetch_episodes',
                nonce:  simplepcoImport.nonce
            }, function(response) {
                $btn.prop('disabled', false).text($btn.data('original-text') || 'Fetch from Planning Center');

                if (!response.success) {
                    $status.html('<span class="simplepco-import-error">' + (response.data.message || i18n.fetchError) + '</span>');
                    return;
                }

                episodes = response.data.episodes || [];
                renderEpisodeTable(episodes);
                $('#simplepco-import-step-preview').slideDown(200);

            }).fail(function() {
                $btn.prop('disabled', false).text($btn.data('original-text') || 'Fetch from Planning Center');
                $status.html('<span class="simplepco-import-error">' + i18n.fetchError + '</span>');
            });
        });

        // Store original button text
        $('#simplepco-import-fetch-btn').data('original-text', $('#simplepco-import-fetch-btn').text());

        // =====================================================================
        // Step 2: Render Episode Table
        // =====================================================================

        function renderEpisodeTable(episodes) {
            var $tbody   = $('#simplepco-import-tbody');
            var $summary = $('#simplepco-import-summary');
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
                if (ep.has_video) mediaHtml.push('<span class="simplepco-import-media-tag simplepco-media-video">' + i18n.video + '</span>');
                if (ep.has_audio) mediaHtml.push('<span class="simplepco-import-media-tag simplepco-media-audio">' + i18n.audio + '</span>');
                if (ep.has_art) mediaHtml.push('<span class="simplepco-import-media-tag simplepco-media-art">' + i18n.art + '</span>');
                if (ep.has_resources) mediaHtml.push('<span class="simplepco-import-media-tag simplepco-media-files">' + i18n.files + '</span>');
                if (!mediaHtml.length) mediaHtml.push('<span class="simplepco-import-media-tag simplepco-media-none">' + i18n.none + '</span>');

                var statusHtml = ep.already_imported
                    ? '<span class="simplepco-import-badge simplepco-import-badge-skip">' + i18n.alreadyExists + '</span>'
                    : '<span class="simplepco-import-badge simplepco-import-badge-new">New</span>';

                var $row = $(
                    '<tr data-episode-id="' + ep.id + '"' + (ep.already_imported ? ' class="simplepco-import-row-existing"' : '') + '>' +
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
                        '<td class="simplepco-import-row-status">' + statusHtml + '</td>' +
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

        $('#simplepco-import-select-all').on('change', function() {
            var checked = $(this).is(':checked');
            $('#simplepco-import-tbody input[type="checkbox"]:not(:disabled)').prop('checked', checked);
        });

        // =====================================================================
        // Step 3: Run Import
        // =====================================================================

        $('#simplepco-import-run-btn').on('click', function() {
            var $btn    = $(this);
            var $status = $('#simplepco-import-run-status');

            var selectedIds = [];
            $('#simplepco-import-tbody input[type="checkbox"]:checked:not(:disabled)').each(function() {
                selectedIds.push($(this).val());
            });

            if (!selectedIds.length) {
                $status.html('<span class="simplepco-import-error">' + i18n.noEpisodes + '</span>');
                return;
            }

            $btn.prop('disabled', true);
            $status.html('');

            // Mark selected rows as pending
            $.each(selectedIds, function(idx, id) {
                var $row = $('#simplepco-import-tbody tr[data-episode-id="' + id + '"]');
                $row.find('.simplepco-import-row-status').html(
                    '<span class="simplepco-import-badge simplepco-import-badge-pending">' + i18n.importing + '</span>'
                );
            });

            var totalImported = 0;
            var totalSkipped  = 0;
            var totalErrors   = [];
            var idx = 0;

            function importNext() {
                if (idx >= selectedIds.length) {
                    // All done — show summary
                    $btn.prop('disabled', false).text($btn.data('original-text') || 'Import Selected');

                    var resultsHtml =
                        '<div class="notice notice-success inline" style="margin:10px 0;">' +
                            '<p><strong>' + i18n.importComplete + '</strong> ' +
                            totalImported + ' ' + i18n.imported.toLowerCase() + ', ' +
                            totalSkipped + ' ' + i18n.skipped.toLowerCase() + '.</p>' +
                        '</div>';

                    if (totalErrors.length) {
                        resultsHtml += '<div class="notice notice-warning inline" style="margin:10px 0;">';
                        resultsHtml += '<p><strong>Errors:</strong></p><ul>';
                        $.each(totalErrors, function(i, err) {
                            resultsHtml += '<li>' + escHtml(err) + '</li>';
                        });
                        resultsHtml += '</ul></div>';
                    }

                    $('#simplepco-import-results').html(resultsHtml);
                    $('#simplepco-import-step-results').slideDown(200);
                    return;
                }

                var episodeId = selectedIds[idx];
                var $row = $('#simplepco-import-tbody tr[data-episode-id="' + episodeId + '"]');

                $btn.text(i18n.importing + ' (' + (idx + 1) + '/' + selectedIds.length + ')...');
                $row.find('.simplepco-import-row-status').html(
                    '<span class="simplepco-import-badge simplepco-import-badge-pending">' + i18n.importing + '...</span>'
                );

                $.post({
                    url: simplepcoImport.ajaxUrl,
                    data: {
                        action:      'simplepco_import_run',
                        nonce:       simplepcoImport.nonce,
                        episode_ids: [episodeId]
                    },
                    timeout: 180000 // 3 min — audio downloads can take a while
                }).done(function(response) {
                    handleImportSuccess(response, episodeId, $row);
                    idx++;
                    importNext();
                }).fail(function(jqXHR) {
                    // Server likely timed out but PHP continues in background.
                    // Poll to check if the import actually completed.
                    $row.find('.simplepco-import-row-status').html(
                        '<span class="simplepco-import-badge simplepco-import-badge-pending">Verifying...</span>'
                    );
                    pollImportStatus(episodeId, $row, 0);
                });
            }

            function handleImportSuccess(response, episodeId, $row) {
                if (response.success && response.data.results) {
                    $.each(response.data.results, function(i, result) {
                        var badgeClass, badgeText;
                        if (result.status === 'imported') {
                            badgeClass = 'simplepco-import-badge-success';
                            badgeText  = i18n.imported;
                            totalImported++;
                            $row.find('input[type="checkbox"]').prop('checked', false).prop('disabled', true);
                        } else if (result.status === 'skipped') {
                            badgeClass = 'simplepco-import-badge-skip';
                            badgeText  = i18n.skipped;
                            totalSkipped++;
                        } else {
                            badgeClass = 'simplepco-import-badge-error';
                            badgeText  = result.message || i18n.error;
                            totalErrors.push(result.message || i18n.error);
                        }
                        $row.find('.simplepco-import-row-status').html(
                            '<span class="simplepco-import-badge ' + badgeClass + '" title="' + escHtml(result.message || '') + '">' + escHtml(badgeText) + '</span>'
                        );
                    });
                }
            }

            // Poll the status-check endpoint until the import is confirmed or we give up
            function pollImportStatus(episodeId, $row, attempt) {
                if (attempt >= 12) {
                    // Gave up after ~60 seconds of polling — but it probably worked
                    $row.find('.simplepco-import-row-status').html(
                        '<span class="simplepco-import-badge simplepco-import-badge-success">' + i18n.imported + '</span>'
                    );
                    totalImported++;
                    $row.find('input[type="checkbox"]').prop('checked', false).prop('disabled', true);
                    idx++;
                    importNext();
                    return;
                }

                setTimeout(function() {
                    $.post({
                        url: simplepcoImport.ajaxUrl,
                        data: {
                            action:     'simplepco_check_import',
                            nonce:      simplepcoImport.nonce,
                            episode_id: episodeId
                        },
                        timeout: 10000
                    }).done(function(response) {
                        if (response.success && response.data.status === 'imported') {
                            $row.find('.simplepco-import-row-status').html(
                                '<span class="simplepco-import-badge simplepco-import-badge-success">' + i18n.imported + '</span>'
                            );
                            totalImported++;
                            $row.find('input[type="checkbox"]').prop('checked', false).prop('disabled', true);
                            idx++;
                            importNext();
                        } else {
                            // Not done yet — keep polling
                            pollImportStatus(episodeId, $row, attempt + 1);
                        }
                    }).fail(function() {
                        // Check endpoint itself failed — try again
                        pollImportStatus(episodeId, $row, attempt + 1);
                    });
                }, 5000); // Poll every 5 seconds
            }

            importNext();
        });

        // Store original button text
        $('#simplepco-import-run-btn').data('original-text', $('#simplepco-import-run-btn').text());

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
