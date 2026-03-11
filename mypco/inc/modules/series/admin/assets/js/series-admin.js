/**
 * Series Admin JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Confirm delete actions
        $('.submitdelete').on('click', function(e) {
            if (!confirm($(this).data('confirm') || 'Are you sure?')) {
                e.preventDefault();
            }
        });

        // Lock our meta boxes in place on the Message editor –
        // disable sortable so they cannot be dragged between areas.
        // Wrapped in try-catch because sortable may not be initialised
        // yet when Gutenberg renders meta boxes asynchronously.
        if ($('body').hasClass('post-type-mypco_message')) {
            try {
                $('.meta-box-sortables').sortable('disable');
            } catch (e) { /* sortable not ready yet */ }
        }

        // =====================================================================
        // Inline "Add New Speaker" from the Message editor meta box
        // Uses event delegation so it works with Gutenberg's async rendering.
        // =====================================================================

        // Toggle the add-new form
        $(document).on('click', '#mypco_toggle_add_speaker', function(e) {
            e.preventDefault();
            $('#mypco_add_speaker_form').slideToggle(150);
        });

        // Create the speaker via AJAX
        $(document).on('click', '#mypco_add_speaker_btn', function() {
            var $btn    = $(this);
            var $input  = $('#mypco_new_speaker_name');
            var $select = $('#mypco_speaker_id');
            var $status = $('#mypco_add_speaker_status');
            var name    = $.trim($input.val());

            if (!name) {
                $input.focus();
                return;
            }

            $btn.prop('disabled', true);
            $status.text('Adding…').show();

            $.post(mypcoSeriesAdmin.ajaxUrl, {
                action:       'mypco_add_speaker',
                nonce:        mypcoSeriesAdmin.addSpeakerNonce,
                speaker_name: name
            }, function(response) {
                $btn.prop('disabled', false);

                if (response.success) {
                    $select.append(
                        $('<option>', { value: response.data.id, text: response.data.name })
                    );
                    $select.val(response.data.id);
                    $input.val('');
                    $status.text('Added!').delay(2000).fadeOut();
                    $('#mypco_add_speaker_form').slideUp(150);
                } else {
                    $status.text(response.data.message || 'Error').show();
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                $status.text('Request failed.').show();
            });
        });

        // Allow pressing Enter in the speaker name field to trigger the add
        $(document).on('keydown', '#mypco_new_speaker_name', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#mypco_add_speaker_btn').trigger('click');
            }
        });

        // =====================================================================
        // Media Upload (Audio / Video) via WordPress Media Library
        // =====================================================================

        $(document).on('click', '.mypco-upload-media-btn', function(e) {
            e.preventDefault();

            var $btn       = $(this);
            var $target    = $($btn.data('target'));
            var mediaType  = $btn.data('media-type') || '';

            var frameOpts = {
                title: $btn.text(),
                button: { text: 'Use this file' },
                multiple: false
            };

            if (mediaType) {
                frameOpts.library = { type: mediaType };
            }

            var frame = wp.media(frameOpts);

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $target.val(attachment.url);
                $btn.siblings('.mypco-remove-media-btn').show();
            });

            frame.open();
        });

        $(document).on('click', '.mypco-remove-media-btn', function(e) {
            e.preventDefault();
            var $target = $($(this).data('target'));
            $target.val('');
            $(this).hide();
        });

        // Show/hide remove button when the URL input changes
        $(document).on('input', '#mypco_message_audio, #mypco_message_video', function() {
            var $remove = $(this).closest('td').find('.mypco-remove-media-btn');
            if ($(this).val()) {
                $remove.show();
            } else {
                $remove.hide();
            }
        });

        // =====================================================================
        // Scripture Passages – Cascading Book → Chapter → Verse
        // =====================================================================

        var bibleData = (typeof mypcoSeriesAdmin !== 'undefined' && mypcoSeriesAdmin.bibleData)
            ? mypcoSeriesAdmin.bibleData
            : [];

        var i18n = (typeof mypcoSeriesAdmin !== 'undefined' && mypcoSeriesAdmin.i18n)
            ? mypcoSeriesAdmin.i18n
            : { selectBook: 'Select Book', chapter: 'Chapter', verseStart: 'Start Verse', verseEnd: 'End Verse' };

        function getBookData(bookName) {
            for (var i = 0; i < bibleData.length; i++) {
                if (bibleData[i].name === bookName) {
                    return bibleData[i];
                }
            }
            return null;
        }

        function populateBookDropdown($select) {
            var saved = $select.data('value') || '';
            $select.empty().append('<option value="">' + i18n.selectBook + '</option>');
            $.each(bibleData, function(idx, book) {
                $select.append('<option value="' + book.name + '">' + book.name + '</option>');
            });
            if (saved) {
                $select.val(saved);
            }
        }

        function populateChapterDropdown($select, book) {
            var saved = $select.data('value') || '';
            $select.empty().append('<option value="">' + i18n.chapter + '</option>');
            if (book) {
                for (var c = 1; c <= book.chapters.length; c++) {
                    $select.append('<option value="' + c + '">' + c + '</option>');
                }
                $select.prop('disabled', false);
            } else {
                $select.prop('disabled', true);
            }
            if (saved) {
                $select.val(saved);
            }
        }

        function populateVerseDropdown($select, book, chapter, label) {
            var saved = $select.data('value') || '';
            $select.empty().append('<option value="">' + label + '</option>');
            if (book && chapter > 0 && chapter <= book.chapters.length) {
                var count = book.chapters[chapter - 1];
                for (var v = 1; v <= count; v++) {
                    $select.append('<option value="' + v + '">' + v + '</option>');
                }
                $select.prop('disabled', false);
            } else {
                $select.prop('disabled', true);
            }
            if (saved) {
                $select.val(saved);
            }
        }

        function populateVerses($row, book, chapter) {
            var $start = $row.find('.mypco-scripture-verse-start');
            var $end   = $row.find('.mypco-scripture-verse-end');
            populateVerseDropdown($start, book, chapter, i18n.verseStart);
            populateVerseDropdown($end, book, chapter, i18n.verseEnd);
        }

        // Initialise existing scripture rows (populate selects + restore saved values)
        $('#mypco-scripture-passages .mypco-scripture-row').each(function() {
            var $row     = $(this);
            var $book    = $row.find('.mypco-scripture-book');
            var $chapter = $row.find('.mypco-scripture-chapter');

            populateBookDropdown($book);

            var bookData = getBookData($book.val());
            populateChapterDropdown($chapter, bookData);

            if (bookData && $chapter.val()) {
                populateVerses($row, bookData, parseInt($chapter.val(), 10));
            }
        });

        // Book changed → populate chapters, reset verses
        $(document).on('change', '.mypco-scripture-book', function() {
            var $row     = $(this).closest('.mypco-scripture-row');
            var $chapter = $row.find('.mypco-scripture-chapter');
            var bookData = getBookData($(this).val());

            $chapter.removeData('value');
            $row.find('.mypco-scripture-verse-start').removeData('value');
            $row.find('.mypco-scripture-verse-end').removeData('value');
            populateChapterDropdown($chapter, bookData);
            populateVerses($row, null, 0);
        });

        // Chapter changed → populate verses
        $(document).on('change', '.mypco-scripture-chapter', function() {
            var $row     = $(this).closest('.mypco-scripture-row');
            var bookData = getBookData($row.find('.mypco-scripture-book').val());

            $row.find('.mypco-scripture-verse-start').removeData('value');
            $row.find('.mypco-scripture-verse-end').removeData('value');
            populateVerses($row, bookData, parseInt($(this).val(), 10) || 0);
        });

        // Add a new passage row
        $(document).on('click', '#mypco-add-scripture', function() {
            var $container = $('#mypco-scripture-passages');
            var index      = $container.find('.mypco-scripture-row').length;

            var $row = $(
                '<div class="mypco-scripture-row" data-index="' + index + '">' +
                    '<select name="mypco_scriptures[' + index + '][book]" class="mypco-scripture-book">' +
                        '<option value="">' + i18n.selectBook + '</option>' +
                    '</select>' +
                    '<select name="mypco_scriptures[' + index + '][chapter]" class="mypco-scripture-chapter" disabled>' +
                        '<option value="">' + i18n.chapter + '</option>' +
                    '</select>' +
                    '<select name="mypco_scriptures[' + index + '][verse_start]" class="mypco-scripture-verse-start" disabled>' +
                        '<option value="">' + i18n.verseStart + '</option>' +
                    '</select>' +
                    '<span class="mypco-scripture-dash">&ndash;</span>' +
                    '<select name="mypco_scriptures[' + index + '][verse_end]" class="mypco-scripture-verse-end" disabled>' +
                        '<option value="">' + i18n.verseEnd + '</option>' +
                    '</select>' +
                    '<button type="button" class="button mypco-remove-scripture" title="Remove">&times;</button>' +
                '</div>'
            );

            $container.append($row);
            populateBookDropdown($row.find('.mypco-scripture-book'));
        });

        // Remove a passage row (clear the last row instead of removing it)
        $(document).on('click', '.mypco-remove-scripture', function() {
            var $container = $('#mypco-scripture-passages');
            var $row       = $(this).closest('.mypco-scripture-row');

            if ($container.find('.mypco-scripture-row').length <= 1) {
                $row.find('.mypco-scripture-book').val('').trigger('change');
                return;
            }

            $row.remove();
        });

        // =====================================================================
        // Speaker Links – Repeatable rows
        // =====================================================================

        $(document).on('click', '#mypco-add-speaker-link', function() {
            var $container = $('#mypco-speaker-links');
            var index = $container.find('.mypco-speaker-link-row').length;

            // Detect field name prefix from existing rows (supports both
            // the custom admin page and the WP post editor meta box).
            var namePrefix = 'speaker_links';
            var $existing = $container.find('.mypco-link-label').first();
            if ($existing.length) {
                var match = $existing.attr('name').match(/^(.+?)\[/);
                if (match) {
                    namePrefix = match[1];
                }
            }

            var $row = $(
                '<div class="mypco-speaker-link-row" data-index="' + index + '">' +
                    '<input type="text" name="' + namePrefix + '[' + index + '][label]" ' +
                        'class="regular-text mypco-link-label" placeholder="Label (e.g. Facebook)" />' +
                    '<input type="url" name="' + namePrefix + '[' + index + '][url]" ' +
                        'class="regular-text mypco-link-url" placeholder="https://..." />' +
                    '<button type="button" class="button mypco-remove-speaker-link" title="Remove">&times;</button>' +
                '</div>'
            );

            $container.append($row);
        });

        $(document).on('click', '.mypco-remove-speaker-link', function() {
            var $container = $('#mypco-speaker-links');
            var $row = $(this).closest('.mypco-speaker-link-row');

            if ($container.find('.mypco-speaker-link-row').length <= 1) {
                $row.find('input').val('');
                return;
            }

            $row.remove();
        });

        // =====================================================================
        // Image Upload via WordPress Media Library
        // =====================================================================

        // Pattern 1: .mypco-image-upload wrapper (existing admin templates)
        $('.mypco-image-upload').each(function() {
            var $wrap      = $(this);
            var $input     = $wrap.find('input[type="hidden"]');
            var $preview   = $wrap.find('.mypco-image-preview');
            var $uploadBtn = $wrap.find('.mypco-upload-btn');
            var $removeBtn = $wrap.find('.mypco-remove-btn');
            var uploadType = $wrap.data('upload-type') || '';

            $uploadBtn.on('click', function(e) {
                e.preventDefault();

                // Set custom upload directory param before opening the frame
                if (uploadType && wp.Uploader) {
                    wp.Uploader.defaults.multipart_params.mypco_upload_type = uploadType;
                }

                var frame = wp.media({
                    title: $uploadBtn.text(),
                    button: { text: 'Use this image' },
                    multiple: false,
                    library: { type: 'image' }
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    var url = attachment.sizes && attachment.sizes.large
                        ? attachment.sizes.large.url
                        : attachment.url;

                    $input.val(url);
                    $preview.html('<img src="' + url + '" alt="">').addClass('has-image');
                    $removeBtn.show();
                });

                frame.on('close', function() {
                    // Clean up the custom param so normal uploads aren't affected
                    if (wp.Uploader) {
                        delete wp.Uploader.defaults.multipart_params.mypco_upload_type;
                    }
                });

                frame.open();
            });

            $removeBtn.on('click', function(e) {
                e.preventDefault();
                $input.val('');
                $preview.html('').removeClass('has-image');
                $removeBtn.hide();
            });
        });

        // Pattern 2: data-target / data-preview buttons (meta box + taxonomy forms)
        $(document).on('click', '.mypco-upload-image-btn', function(e) {
            e.preventDefault();

            var $btn     = $(this);
            var $target  = $($btn.data('target'));
            var $preview = $btn.data('preview') ? $($btn.data('preview')) : null;
            var $remove  = $btn.siblings('.mypco-remove-image-btn');

            var frame = wp.media({
                title: $btn.text(),
                button: { text: 'Use this image' },
                multiple: false,
                library: { type: 'image' }
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                var url = attachment.sizes && attachment.sizes.medium
                    ? attachment.sizes.medium.url
                    : attachment.url;

                $target.val(url);

                if ($preview && $preview.length) {
                    $preview.html('<img src="' + url + '" style="max-width:200px;height:auto;" />');
                }

                if ($remove.length) {
                    $remove.show();
                }
            });

            frame.open();
        });

        $(document).on('click', '.mypco-remove-image-btn', function(e) {
            e.preventDefault();

            var $btn     = $(this);
            var $target  = $($btn.data('target'));
            var $preview = $btn.data('preview') ? $($btn.data('preview')) : null;

            $target.val('');

            if ($preview && $preview.length) {
                $preview.html('');
            }

            $btn.hide();
        });

        // =====================================================================
        // Files Repeater – Add / Remove / Upload / Reorder
        // =====================================================================

        // Make the file list sortable (drag to reorder)
        if ($('#mypco-message-files').length && $.fn.sortable) {
            $('#mypco-message-files').sortable({
                handle: '.mypco-file-drag',
                items: '.mypco-file-row',
                axis: 'y',
                tolerance: 'pointer',
                update: function() {
                    reindexFileRows();
                }
            });
        }

        // Add a new file row
        $(document).on('click', '#mypco-add-file', function() {
            var $container = $('#mypco-message-files');
            var index = $container.find('.mypco-file-row').length;

            var $row = $(
                '<div class="mypco-file-row" data-index="' + index + '">' +
                    '<span class="mypco-file-drag dashicons dashicons-menu" title="Drag to reorder"></span>' +
                    '<input type="text" name="mypco_message_files[' + index + '][name]" ' +
                        'class="regular-text mypco-file-name" placeholder="Name" />' +
                    '<input type="url" name="mypco_message_files[' + index + '][url]" ' +
                        'class="regular-text mypco-file-url" placeholder="File URL" />' +
                    '<button type="button" class="button mypco-file-upload-btn">Upload</button>' +
                    '<button type="button" class="button mypco-file-remove-btn" title="Remove">&times;</button>' +
                '</div>'
            );

            $container.append($row);
        });

        // Remove a file row
        $(document).on('click', '.mypco-file-remove-btn', function() {
            var $container = $('#mypco-message-files');
            var $row = $(this).closest('.mypco-file-row');

            if ($container.find('.mypco-file-row').length <= 1) {
                // Clear the last row instead of removing it
                $row.find('input').val('');
                return;
            }

            $row.remove();
            reindexFileRows();
        });

        // Upload file via WP Media Library
        $(document).on('click', '.mypco-file-upload-btn', function(e) {
            e.preventDefault();

            var $btn  = $(this);
            var $row  = $btn.closest('.mypco-file-row');
            var $url  = $row.find('.mypco-file-url');
            var $name = $row.find('.mypco-file-name');

            var frame = wp.media({
                title: 'Select File',
                button: { text: 'Use this file' },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $url.val(attachment.url);
                // Auto-fill name if empty
                if (!$name.val()) {
                    $name.val(attachment.title || attachment.filename || '');
                }
            });

            frame.open();
        });

        function reindexFileRows() {
            $('#mypco-message-files .mypco-file-row').each(function(idx) {
                $(this).attr('data-index', idx);
                $(this).find('.mypco-file-name').attr('name', 'mypco_message_files[' + idx + '][name]');
                $(this).find('.mypco-file-url').attr('name', 'mypco_message_files[' + idx + '][url]');
            });
        }
    });
})(jQuery);
