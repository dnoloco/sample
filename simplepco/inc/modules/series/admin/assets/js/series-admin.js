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
        if ($('body').hasClass('post-type-simplepco_message')) {
            try {
                $('.meta-box-sortables').sortable('disable');
            } catch (e) { /* sortable not ready yet */ }
        }

        // =====================================================================
        // Inline "Add New Speaker" from the Message editor meta box
        // Uses event delegation so it works with Gutenberg's async rendering.
        // =====================================================================

        // Toggle the add-new form
        $(document).on('click', '#simplepco_toggle_add_speaker', function(e) {
            e.preventDefault();
            $('#simplepco_add_speaker_form').slideToggle(150);
        });

        // Create the speaker via AJAX
        $(document).on('click', '#simplepco_add_speaker_btn', function() {
            var $btn    = $(this);
            var $input  = $('#simplepco_new_speaker_name');
            var $select = $('#simplepco_speaker_id');
            var $status = $('#simplepco_add_speaker_status');
            var name    = $.trim($input.val());

            if (!name) {
                $input.focus();
                return;
            }

            $btn.prop('disabled', true);
            $status.text('Adding…').show();

            $.post(simplepcoSeriesAdmin.ajaxUrl, {
                action:       'simplepco_add_speaker',
                nonce:        simplepcoSeriesAdmin.addSpeakerNonce,
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
                    $('#simplepco_add_speaker_form').slideUp(150);
                } else {
                    $status.text(response.data.message || 'Error').show();
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                $status.text('Request failed.').show();
            });
        });

        // Allow pressing Enter in the speaker name field to trigger the add
        $(document).on('keydown', '#simplepco_new_speaker_name', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#simplepco_add_speaker_btn').trigger('click');
            }
        });

        // =====================================================================
        // Media Upload (Audio / Video) via WordPress Media Library
        // =====================================================================

        $(document).on('click', '.simplepco-upload-media-btn', function(e) {
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
                $btn.siblings('.simplepco-remove-media-btn').show();
            });

            frame.open();
        });

        $(document).on('click', '.simplepco-remove-media-btn', function(e) {
            e.preventDefault();
            var $target = $($(this).data('target'));
            $target.val('');
            $(this).hide();
        });

        // Show/hide remove button when the URL input changes
        $(document).on('input', '#simplepco_message_audio, #simplepco_message_video', function() {
            var $remove = $(this).closest('td').find('.simplepco-remove-media-btn');
            if ($(this).val()) {
                $remove.show();
            } else {
                $remove.hide();
            }
        });

        // =====================================================================
        // Scripture Passages – Cascading Book → Chapter → Verse
        // =====================================================================

        var bibleData = (typeof simplepcoSeriesAdmin !== 'undefined' && simplepcoSeriesAdmin.bibleData)
            ? simplepcoSeriesAdmin.bibleData
            : [];

        var i18n = (typeof simplepcoSeriesAdmin !== 'undefined' && simplepcoSeriesAdmin.i18n)
            ? simplepcoSeriesAdmin.i18n
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
            var $start = $row.find('.simplepco-scripture-verse-start');
            var $end   = $row.find('.simplepco-scripture-verse-end');
            populateVerseDropdown($start, book, chapter, i18n.verseStart);
            populateVerseDropdown($end, book, chapter, i18n.verseEnd);
        }

        // Initialise existing scripture rows (populate selects + restore saved values)
        $('#simplepco-scripture-passages .simplepco-scripture-row').each(function() {
            var $row     = $(this);
            var $book    = $row.find('.simplepco-scripture-book');
            var $chapter = $row.find('.simplepco-scripture-chapter');

            populateBookDropdown($book);

            var bookData = getBookData($book.val());
            populateChapterDropdown($chapter, bookData);

            if (bookData && $chapter.val()) {
                populateVerses($row, bookData, parseInt($chapter.val(), 10));
            }
        });

        // Book changed → populate chapters, reset verses
        $(document).on('change', '.simplepco-scripture-book', function() {
            var $row     = $(this).closest('.simplepco-scripture-row');
            var $chapter = $row.find('.simplepco-scripture-chapter');
            var bookData = getBookData($(this).val());

            $chapter.removeData('value');
            $row.find('.simplepco-scripture-verse-start').removeData('value');
            $row.find('.simplepco-scripture-verse-end').removeData('value');
            populateChapterDropdown($chapter, bookData);
            populateVerses($row, null, 0);
        });

        // Chapter changed → populate verses
        $(document).on('change', '.simplepco-scripture-chapter', function() {
            var $row     = $(this).closest('.simplepco-scripture-row');
            var bookData = getBookData($row.find('.simplepco-scripture-book').val());

            $row.find('.simplepco-scripture-verse-start').removeData('value');
            $row.find('.simplepco-scripture-verse-end').removeData('value');
            populateVerses($row, bookData, parseInt($(this).val(), 10) || 0);
        });

        // Add a new passage row
        $(document).on('click', '#simplepco-add-scripture', function() {
            var $container = $('#simplepco-scripture-passages');
            var index      = $container.find('.simplepco-scripture-row').length;

            var $row = $(
                '<div class="simplepco-scripture-row" data-index="' + index + '">' +
                    '<select name="simplepco_scriptures[' + index + '][book]" class="simplepco-scripture-book">' +
                        '<option value="">' + i18n.selectBook + '</option>' +
                    '</select>' +
                    '<select name="simplepco_scriptures[' + index + '][chapter]" class="simplepco-scripture-chapter" disabled>' +
                        '<option value="">' + i18n.chapter + '</option>' +
                    '</select>' +
                    '<select name="simplepco_scriptures[' + index + '][verse_start]" class="simplepco-scripture-verse-start" disabled>' +
                        '<option value="">' + i18n.verseStart + '</option>' +
                    '</select>' +
                    '<span class="simplepco-scripture-dash">&ndash;</span>' +
                    '<select name="simplepco_scriptures[' + index + '][verse_end]" class="simplepco-scripture-verse-end" disabled>' +
                        '<option value="">' + i18n.verseEnd + '</option>' +
                    '</select>' +
                    '<button type="button" class="button simplepco-remove-scripture" title="Remove">&times;</button>' +
                '</div>'
            );

            $container.append($row);
            populateBookDropdown($row.find('.simplepco-scripture-book'));
        });

        // Remove a passage row (clear the last row instead of removing it)
        $(document).on('click', '.simplepco-remove-scripture', function() {
            var $container = $('#simplepco-scripture-passages');
            var $row       = $(this).closest('.simplepco-scripture-row');

            if ($container.find('.simplepco-scripture-row').length <= 1) {
                $row.find('.simplepco-scripture-book').val('').trigger('change');
                return;
            }

            $row.remove();
        });

        // =====================================================================
        // Speaker Links – Repeatable rows
        // =====================================================================

        $(document).on('click', '#simplepco-add-speaker-link', function() {
            var $container = $('#simplepco-speaker-links');
            var index = $container.find('.simplepco-speaker-link-row').length;

            // Detect field name prefix from existing rows (supports both
            // the custom admin page and the WP post editor meta box).
            var namePrefix = 'speaker_links';
            var $existing = $container.find('.simplepco-link-label').first();
            if ($existing.length) {
                var match = $existing.attr('name').match(/^(.+?)\[/);
                if (match) {
                    namePrefix = match[1];
                }
            }

            var $row = $(
                '<div class="simplepco-speaker-link-row" data-index="' + index + '">' +
                    '<input type="text" name="' + namePrefix + '[' + index + '][label]" ' +
                        'class="regular-text simplepco-link-label" placeholder="Label (e.g. Facebook)" />' +
                    '<input type="url" name="' + namePrefix + '[' + index + '][url]" ' +
                        'class="regular-text simplepco-link-url" placeholder="https://..." />' +
                    '<button type="button" class="button simplepco-remove-speaker-link" title="Remove">&times;</button>' +
                '</div>'
            );

            $container.append($row);
        });

        $(document).on('click', '.simplepco-remove-speaker-link', function() {
            var $container = $('#simplepco-speaker-links');
            var $row = $(this).closest('.simplepco-speaker-link-row');

            if ($container.find('.simplepco-speaker-link-row').length <= 1) {
                $row.find('input').val('');
                return;
            }

            $row.remove();
        });

        // =====================================================================
        // Image Upload via WordPress Media Library
        // =====================================================================

        // Pattern 1: .simplepco-image-upload wrapper (existing admin templates)
        $('.simplepco-image-upload').each(function() {
            var $wrap      = $(this);
            var $input     = $wrap.find('input[type="hidden"]');
            var $preview   = $wrap.find('.simplepco-image-preview');
            var $uploadBtn = $wrap.find('.simplepco-upload-btn');
            var $removeBtn = $wrap.find('.simplepco-remove-btn');
            var uploadType = $wrap.data('upload-type') || '';

            $uploadBtn.on('click', function(e) {
                e.preventDefault();

                // Set custom upload directory param before opening the frame
                if (uploadType && wp.Uploader) {
                    wp.Uploader.defaults.multipart_params.simplepco_upload_type = uploadType;
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
                        delete wp.Uploader.defaults.multipart_params.simplepco_upload_type;
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
        $(document).on('click', '.simplepco-upload-image-btn', function(e) {
            e.preventDefault();

            var $btn     = $(this);
            var $target  = $($btn.data('target'));
            var $preview = $btn.data('preview') ? $($btn.data('preview')) : null;
            var $remove  = $btn.siblings('.simplepco-remove-image-btn');

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

        $(document).on('click', '.simplepco-remove-image-btn', function(e) {
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
        if ($('#simplepco-message-files').length && $.fn.sortable) {
            $('#simplepco-message-files').sortable({
                handle: '.simplepco-file-drag',
                items: '.simplepco-file-row',
                axis: 'y',
                tolerance: 'pointer',
                update: function() {
                    reindexFileRows();
                }
            });
        }

        // Add a new file row
        $(document).on('click', '#simplepco-add-file', function() {
            var $container = $('#simplepco-message-files');
            var index = $container.find('.simplepco-file-row').length;

            var $row = $(
                '<div class="simplepco-file-row" data-index="' + index + '">' +
                    '<span class="simplepco-file-drag dashicons dashicons-menu" title="Drag to reorder"></span>' +
                    '<input type="text" name="simplepco_message_files[' + index + '][name]" ' +
                        'class="regular-text simplepco-file-name" placeholder="Name" />' +
                    '<input type="url" name="simplepco_message_files[' + index + '][url]" ' +
                        'class="regular-text simplepco-file-url" placeholder="File URL" />' +
                    '<button type="button" class="button simplepco-file-upload-btn">Upload</button>' +
                    '<button type="button" class="button simplepco-file-remove-btn" title="Remove">&times;</button>' +
                '</div>'
            );

            $container.append($row);
        });

        // Remove a file row
        $(document).on('click', '.simplepco-file-remove-btn', function() {
            var $container = $('#simplepco-message-files');
            var $row = $(this).closest('.simplepco-file-row');

            if ($container.find('.simplepco-file-row').length <= 1) {
                // Clear the last row instead of removing it
                $row.find('input').val('');
                return;
            }

            $row.remove();
            reindexFileRows();
        });

        // Upload file via WP Media Library
        $(document).on('click', '.simplepco-file-upload-btn', function(e) {
            e.preventDefault();

            var $btn  = $(this);
            var $row  = $btn.closest('.simplepco-file-row');
            var $url  = $row.find('.simplepco-file-url');
            var $name = $row.find('.simplepco-file-name');

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
            $('#simplepco-message-files .simplepco-file-row').each(function(idx) {
                $(this).attr('data-index', idx);
                $(this).find('.simplepco-file-name').attr('name', 'simplepco_message_files[' + idx + '][name]');
                $(this).find('.simplepco-file-url').attr('name', 'simplepco_message_files[' + idx + '][url]');
            });
        }
    });
})(jQuery);
