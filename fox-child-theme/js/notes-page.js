/**
 * Notes Page Functionality
 * - Handles loading note content via REST API.
 * - Handles filtering notes by type and hashtag.
 * - Updates UI elements based on selections and loading states.
 * - Ensures hashtag links trigger filtering.
 */
jQuery(document).ready(function($) {

    // === Variables ===
    const notesData = typeof bbbNotesData !== 'undefined' ? bbbNotesData : null;
    const $notesListPane = $('.notes-list-pane');
    const $notesListContainer = $notesListPane.find('.notes-list-container');
    const $notesList = $notesListContainer.find('.notes-list');
    let $noteItems = $notesList.find('.note-item');
    const $notesLoader = $notesListContainer.find('.notes-loader');
    const $noNotesFilteredMessage = $notesListContainer.find('.no-notes-found-filtered');
    const $loadMoreBtn = $notesListContainer.find('.load-more-notes');
    const $searchInput = $notesListPane.find('.notes-search-input');
    const $sortSelect = $notesListPane.find('.notes-sort-select');
    const postsPerPage = notesData && notesData.posts_per_page ? parseInt(notesData.posts_per_page,10) : 10;
    let searchTerm = '';
    let sortOrder = $sortSelect.val();
    let activeType = 'all'; // currently selected note type slug

    const $contentPane = $('.notes-content-pane');
    const $contentArea = $contentPane.find('.note-content-area');
    const $contentPlaceholder = $contentArea.find('.note-content-placeholder');
    const $contentLoader = $contentArea.find('.note-content-loader');
    const $contentDisplay = $contentArea.find('.note-content-display');
    const $contentTitle = $contentDisplay.find('#note-content-title');
    const $contentBody = $contentDisplay.find('#note-content-body');
    const $contentMeta = $contentDisplay.find('.note-content-meta');
    const $contentMetaDate = $contentMeta.find('.meta-date');
    const $contentMetaType = $contentMeta.find('.meta-type');
    const $contentError = $contentArea.find('.note-content-error');

    const $filterButtons = $notesListPane.find('.notes-filters .filter-button');
    const $hashtagFilterDisplay = $notesListPane.find('.note-hashtag-filter-display');
    const $activeHashtagSpan = $hashtagFilterDisplay.find('.active-hashtag');
    const $clearHashtagButton = $hashtagFilterDisplay.find('.clear-hashtag-filter');

    let currentAjaxRequest = null; // To abort previous requests
    let currentPage = 1;
    let readNotes = JSON.parse(localStorage.getItem('readNotes') || '[]');
    let currentFilterType = 'all'; // 'all', 'hashtag'
    let currentFilterValue = 'all'; // hashtag value when filtering by hashtag

    // === Initial Setup ===
    function applyReadStatus() {
        $noteItems.each(function() {
            const id = $(this).data('note-id');
            if (readNotes.includes(id)) {
                $(this).addClass('active');
            }
        });
    }
    applyReadStatus();
    sortNotes($sortSelect.val());
    // Optional: Load the first note automatically if the list is not empty
    // if ($noteItems.length > 0) {
    //     const firstNoteId = $noteItems.first().data('note-id');
    //     if (firstNoteId) {
    //         $noteItems.first().addClass('selected active'); // Mark as selected and read
    //         loadNoteContent(firstNoteId);
    //     }
    // }

    // === Functions ===

    /**
     * Loads note content via REST API
     * @param {number} noteId The ID of the note to load.
     */
    function loadNoteContent(noteId) {
        if (!notesData || !noteId) {
            showErrorState('Geçersiz not verisi veya ID.');
            console.error("Notes Error: Invalid notesData or noteId.", notesData, noteId);
            return;
        }

        showLoadingState();
        console.log("Loading note:", noteId); // Debug

        // Abort previous request if it exists
        if (currentAjaxRequest) {
            console.log("Aborting previous request..."); // Debug
            currentAjaxRequest.abort();
        }

        currentAjaxRequest = $.ajax({
            url: notesData.rest_url + notesData.api_endpoint + noteId,
            method: 'GET',
            dataType: 'json', // Expect JSON response
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', notesData.nonce);
                console.log("Sending AJAX request to:", notesData.rest_url + notesData.api_endpoint + noteId); // Debug
            }
        });

        currentAjaxRequest.done(function(response) {
            console.log("AJAX request successful. Response:", response); // Debug
            if (response && response.title && typeof response.content !== 'undefined') { // Check content existence
                displayNoteContent(response);
            } else {
                console.error("Notes Error: Invalid response structure.", response);
                showErrorState(notesData.text_error || 'Not içeriği alınamadı.');
            }
        });

        currentAjaxRequest.fail(function(jqXHR, textStatus, errorThrown) {
            if (textStatus !== 'abort') {
                let errorMsg = notesData.text_error || 'Not yüklenirken bir hata oluştu.';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    errorMsg = jqXHR.responseJSON.message;
                } else if (jqXHR.statusText) {
                    errorMsg += ` (${jqXHR.status} ${jqXHR.statusText})`;
                }
                console.error("AJAX request failed:", textStatus, errorThrown, jqXHR.responseText); // Debug
                showErrorState(errorMsg);
            } else {
                console.log("Previous AJAX request aborted."); // Debug
            }
        });

        currentAjaxRequest.always(function() {
            currentAjaxRequest = null; // Clear the request variable
        });
    }

    /**
     * Displays the fetched note content in the right pane.
     * @param {object} noteData The note data object from the API.
     */
    function displayNoteContent(noteData) {
        console.log("Displaying note content:", noteData); // Debug
        $contentPlaceholder.hide();
        $contentLoader.hide();
        $contentError.hide();

        $contentTitle.html(noteData.title || '');
        // IMPORTANT: Use .html() to render the HTML tags (like the hashtag links)
        $contentBody.html(noteData.content || '');

        // Display Meta
        let metaHtml = '';
        let typeHtml = '';
        if (noteData.date) {
             $contentMetaDate.text(noteData.date);
             $contentMeta.show(); // Show meta container
        } else {
             $contentMetaDate.text('');
        }

        if (noteData.note_types && noteData.note_types.length > 0) {
            typeHtml = noteData.note_types.map(type =>
                `<button class="meta-type-filter" data-filter-type="${type.slug}" style="background-color:${type.color};border-color:${type.color};color:#000;">${type.name}</button>`
            ).join(', ');
            $contentMetaType.html(typeHtml);
            $contentMeta.show(); // Show meta container
        } else {
            $contentMetaType.html('');
        }

        // Hide meta container if both date and type are empty
        if (!noteData.date && (!noteData.note_types || noteData.note_types.length === 0)) {
             $contentMeta.hide();
        } else {
             $contentMeta.show(); // Ensure it's shown if either exists
        }


        $contentDisplay.show();
    }

    /** Shows the loading indicator in the content pane. */
    function showLoadingState() {
        $contentPlaceholder.hide();
        $contentDisplay.hide();
        $contentError.hide();
        $contentLoader.show();
    }

    /** Shows the placeholder in the content pane. */
    function showPlaceholderState() {
        $contentLoader.hide();
        $contentDisplay.hide();
        $contentError.hide();
        $contentPlaceholder.show();
    }

    /**
     * Shows an error message in the content pane.
     * @param {string} message The error message to display.
     */
    function showErrorState(message) {
        $contentPlaceholder.hide();
        $contentLoader.hide();
        $contentDisplay.hide();
        $contentError.find('p').text(message);
        $contentError.show();
    }

    /**
     * Filters the notes list based on type or hashtag.
     * @param {string} filterType 'all', 'type', or 'hashtag'.
     * @param {string} filterValue The slug of the type or the hashtag text (without #).
     */
    function filterNotesList(filterType, filterValue) {
        console.log(`Filtering list by: ${filterType} - ${filterValue}`); // Debug
        let visibleCount = 0;
        const lowerCaseFilterValue = filterValue.toLowerCase().trim();

        $notesList.find('.note-item').each(function() {
            const $item = $(this);
            let showItem = false;

            if (filterType === 'all') {
                showItem = true;
            } else if (filterType === 'type') {
                const types = $item.data('note-types') || [];
                // Ensure types is an array before checking includes
                if (Array.isArray(types) && types.includes(lowerCaseFilterValue)) {
                    showItem = true;
                }
            } else if (filterType === 'hashtag') {
                const hashtagsAttr = $item.data('hashtags') || '';
                // Split hashtags, trim, and convert to lowercase for comparison
                const hashtags = hashtagsAttr.split(',').map(tag => tag.trim().toLowerCase());
                if (hashtags.includes(lowerCaseFilterValue)) {
                    showItem = true;
                }
            }

            // No search filtering here; handled server-side
            if (showItem) {
                $item.show().removeClass('filtered-out');
                visibleCount++;
            } else {
                $item.hide().addClass('filtered-out');
            }
        });

         // Show/hide "no notes found" message for filters
         if (visibleCount === 0 && filterType !== 'all') {
             $noNotesFilteredMessage.show();
         } else {
             $noNotesFilteredMessage.hide();
         }

         // Update hashtag filter display
         if (filterType === 'hashtag') {
             $activeHashtagSpan.text('#' + filterValue); // Display with #
             $hashtagFilterDisplay.css('display', 'inline-flex'); // Use display style
         } else {
             $hashtagFilterDisplay.hide();
         }

         // Update active class on type filter buttons
        $filterButtons.removeClass('active');
        if (filterType === 'all') {
            $filterButtons.filter('[data-filter-type="all"]').addClass('active');
        } else if (filterType === 'type') {
            $filterButtons.filter(`[data-filter-type="${filterValue}"]`).addClass('active');
        }
        // If hashtag filter is active, no type button should be active
    }

    function sortNotes(order) {
        const items = $notesList.children('.note-item').get();
        items.sort((a, b) => {
            const aDate = parseInt($(a).data('note-date'), 10);
            const bDate = parseInt($(b).data('note-date'), 10);
            return order === 'asc' ? aDate - bDate : bDate - aDate;
        });
        $.each(items, (idx, itm) => $notesList.append(itm));
    }

    function appendNotes(items) {
        items.forEach(note => {
            const typeSlugs = note.types.map(t => t.slug);
            const typeClasses = note.types.map(t => 'note-type-' + t.slug).join(' ');
            const li = $(
                `<li class="note-item ${typeClasses}" data-note-id="${note.id}" data-note-types='${JSON.stringify(typeSlugs)}' data-note-date="${note.timestamp}" data-hashtags="">`+
                    '<div class="note-item-header">'+
                        `<h3 class="note-item-title"><a href="#" class="note-title-link" data-note-id="${note.id}">${note.title}</a></h3>`+
                        `<span class="note-item-date">${note.date}</span>`+
                    '</div>'+
                '</li>'
            );
            if (readNotes.includes(note.id)) { li.addClass('active'); }
            $notesList.append(li);
        });
        $noteItems = $notesList.find('.note-item');
    }

    function fetchNotes(reset = false) {
        if (reset) {
            currentPage = 1;
            $notesList.empty();
        }

        const data = {
            page: currentPage,
            per_page: postsPerPage,
            order: sortOrder
        };
        if (activeType !== 'all') {
            data.note_type = activeType;
        }
        if (searchTerm) {
            data.search = searchTerm;
        }

        $notesLoader.show();
        $.ajax({
            url: notesData.rest_url + notesData.list_endpoint,
            method: 'GET',
            data: data,
            beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', notesData.nonce); }
        }).done(function(resp) {
            if (resp && resp.notes) {
                appendNotes(resp.notes);
                sortNotes(sortOrder);
                currentPage++;
                if (currentPage <= resp.max_pages) {
                    $loadMoreBtn.show();
                } else {
                    $loadMoreBtn.hide();
                }
                filterNotesList(currentFilterType, currentFilterValue);
            }
        }).fail(function() {
            console.error('Failed to fetch notes');
        }).always(function() {
            $notesLoader.hide();
        });
    }

    function loadMoreNotes() {
        fetchNotes(false);
    }
    // === Event Handlers ===

    // Click on a note item in the list
    $notesList.on('click', '.note-item', function(e) {
        // Prevent default if the click was directly on the link inside,
        // but allow the li click itself.
        if (e.target.tagName === 'A') {
             e.preventDefault();
        }
        const $thisItem = $(this);
        const noteId = $thisItem.data('note-id');
        console.log("Note item clicked:", noteId, $thisItem); // Debug

        if (noteId && !$thisItem.hasClass('selected')) { // Only load if not already selected
            $notesList.find('.note-item').removeClass('selected');
            $thisItem.addClass('selected active');
            if (!readNotes.includes(noteId)) {
                readNotes.push(noteId);
                localStorage.setItem('readNotes', JSON.stringify(readNotes));
            }
            loadNoteContent(noteId);
        } else if (!noteId) {
             console.error("Notes Error: Clicked item has no note-id.", $thisItem);
        }
    });

    // Click on a type filter button
    $filterButtons.on('click', function(e) {
        e.preventDefault();
        const $button = $(this);
        const filterSlug = $button.data('filter-type');

        if (filterSlug === activeType) return;

        activeType = filterSlug === 'all' ? 'all' : filterSlug;
        currentFilterType = 'type';
        currentFilterValue = activeType;
        fetchNotes(true);
    });

    $searchInput.on('input', function() {
        searchTerm = $(this).val();
        fetchNotes(true);
    });

    $sortSelect.on('change', function() {
        sortOrder = $(this).val();
        fetchNotes(true);
    });

    $loadMoreBtn.on('click', function(e) {
        e.preventDefault();
        loadMoreNotes();
    });

    // Click on a hashtag link within the note content
    // Use event delegation as content is loaded dynamically
    $contentPane.on('click', '#note-content-body a.note-hashtag-link', function(e) {
        e.preventDefault(); // Prevent the link from navigating to '#'
        const hashtag = $(this).data('hashtag');
        console.log("Hashtag link clicked:", hashtag); // Debug

        if (hashtag) {
            currentFilterType = 'hashtag';
            currentFilterValue = hashtag;
            filterNotesList('hashtag', hashtag);
            // Scroll list pane to top (optional)
            $notesListContainer.scrollTop(0);
        } else {
            console.warn("Hashtag link clicked, but no data-hashtag found.", this); // Debug
        }
    });

     // Click on a type link/button within the note meta area
     $contentPane.on('click', '.note-content-meta button.meta-type-filter', function(e) {
         e.preventDefault();
         const filterSlug = $(this).data('filter-type');
         if (!filterSlug) return;

         if (filterSlug === activeType) return;

         activeType = filterSlug;
         currentFilterType = 'type';
         currentFilterValue = activeType;
         fetchNotes(true);
         $notesListContainer.scrollTop(0);
     });

    // Click on the clear hashtag filter button
    $clearHashtagButton.on('click', function(e) {
        e.preventDefault();
        console.log("Clear hashtag filter clicked"); // Debug
        // Revert to the 'all' filter
        currentFilterType = 'all';
        currentFilterValue = 'all';
        fetchNotes(true);
        filterNotesList('type', activeType);
    });

});
