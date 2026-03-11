/**
 * Signups Module - Admin Scripts
 *
 * JavaScript for event signups and registration management.
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        /**
         * Form field toggles
         */
        
        // Payment fields toggle
        window.togglePaymentFields = function(checkbox) {
            const fields = document.getElementById('payment_fields');
            if (fields) {
                fields.style.display = checkbox.checked ? '' : 'none';
            }
        };
        
        // Partial payment fields toggle
        window.togglePartialPayment = function(checkbox) {
            const fields = document.getElementById('partial_payment_fields');
            if (fields) {
                fields.style.display = checkbox.checked ? '' : 'none';
            }
        };
        
        /**
         * Auto-populate event data from dropdown
         */
        window.populateEventData = function(select) {
            if (!select.value) return;

            try {
                const event = JSON.parse(select.value);
                
                // Populate form fields
                if (document.getElementById('event_id')) {
                    document.getElementById('event_id').value = event.instance_id || event.event_id;
                }
                if (document.getElementById('event_name')) {
                    document.getElementById('event_name').value = event.name;
                }
                
                // Convert ISO date to datetime-local format
                if (document.getElementById('event_date') && event.starts_at) {
                    const date = new Date(event.starts_at);
                    const localDate = new Date(date.getTime() - (date.getTimezoneOffset() * 60000));
                    document.getElementById('event_date').value = localDate.toISOString().slice(0, 16);
                }
            } catch (e) {
                console.error('Error populating event data:', e);
            }
        };
        
        /**
         * Auto-extract Google Form ID from URL
         */
        const googleFormUrlField = document.getElementById('google_form_url');
        const googleFormIdField = document.getElementById('google_form_id');
        
        if (googleFormUrlField && googleFormIdField) {
            googleFormUrlField.addEventListener('blur', function() {
                const url = this.value;
                
                // Handle both patterns:
                // /d/FORMID/ (edit URL)
                // /d/e/FORMID/ (shareable/view URL)
                const match = url.match(/\/d\/(?:e\/)?([a-zA-Z0-9_-]+)/);
                
                if (match && match[1]) {
                    googleFormIdField.value = match[1];
                    console.log('Extracted Form ID:', match[1]);
                } else if (url) {
                    console.log('Could not extract form ID from URL');
                }
            });
        }
        
        /**
         * Confirm deletions
         */
        $('a[onclick*="confirm"]').on('click', function(e) {
            const message = this.getAttribute('onclick').match(/'([^']+)'/);
            if (message && !confirm(message[1])) {
                e.preventDefault();
                return false;
            }
        });
        
        /**
         * Form validation
         */
        $('form').on('submit', function(e) {
            const paymentRequired = $('#payment_required');
            const paymentAmount = $('#payment_amount');
            
            if (paymentRequired && paymentRequired.is(':checked')) {
                if (paymentAmount && (!paymentAmount.val() || parseFloat(paymentAmount.val()) <= 0)) {
                    alert('Please enter a payment amount.');
                    e.preventDefault();
                    return false;
                }
            }
            
            const partialPayment = $('#allow_partial_payment');
            const minimumPayment = $('#minimum_payment');
            
            if (partialPayment && partialPayment.is(':checked')) {
                if (minimumPayment && (!minimumPayment.val() || parseFloat(minimumPayment.val()) <= 0)) {
                    alert('Please enter a minimum payment amount.');
                    e.preventDefault();
                    return false;
                }
                
                if (paymentAmount && minimumPayment && 
                    parseFloat(minimumPayment.val()) > parseFloat(paymentAmount.val())) {
                    alert('Minimum payment cannot exceed the total payment amount.');
                    e.preventDefault();
                    return false;
                }
            }
        });

    });

})(jQuery);
