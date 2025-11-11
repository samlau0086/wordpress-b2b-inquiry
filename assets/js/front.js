(function ($) {
    'use strict';

    function openModal(wrapper) {
        wrapper.find('.b2b-inquiry-modal').addClass('is-visible').attr('aria-hidden', 'false');
        wrapper.find('textarea[name="message"]').focus();
    }

    function closeModal(wrapper) {
        wrapper.find('.b2b-inquiry-modal').removeClass('is-visible').attr('aria-hidden', 'true');
    }

    function resetForm(wrapper, options) {
        var settings = options || {};
        var form = wrapper.find('.b2b-inquiry-form')[0];
        if (form) {
            form.reset();
        }
        if (settings.clearFeedback !== false) {
            wrapper.find('.b2b-inquiry-feedback').empty();
        }
        if (B2BInquirySettings.email) {
            wrapper.find('input[name="email"]').val(B2BInquirySettings.email);
        }
        if (B2BInquirySettings.defaultMessage) {
            wrapper.find('textarea[name="message"]').val(B2BInquirySettings.defaultMessage);
        }
    }

    function init(wrapper) {
        resetForm(wrapper);

        wrapper.on('click', '.b2b-inquiry-button', function () {
            openModal(wrapper);
        });

        wrapper.on('click', '.b2b-inquiry-close, .b2b-inquiry-overlay', function () {
            closeModal(wrapper);
        });

        wrapper.on('submit', '.b2b-inquiry-form', function (event) {
            event.preventDefault();

            var form = $(this);
            var feedback = wrapper.find('.b2b-inquiry-feedback');
            var submitButton = form.find('button[type="submit"]');

            feedback.text(B2BInquirySettings.labels.sending);
            submitButton.prop('disabled', true);

            var data = form.serializeArray();
            data.push({ name: 'action', value: 'b2b_inquiry_submit' });
            data.push({ name: B2BInquirySettings.nonceField, value: B2BInquirySettings.nonce });

            $.post(B2BInquirySettings.ajaxUrl, data)
                .done(function (response) {
                    if (response && response.success) {
                        feedback.text(B2BInquirySettings.labels.success);
                        resetForm(wrapper, { clearFeedback: false });
                        setTimeout(function () {
                            closeModal(wrapper);
                            resetForm(wrapper);
                        }, 1500);
                    } else {
                        feedback.text((response && response.data && response.data.message) || B2BInquirySettings.labels.error);
                    }
                })
                .fail(function (jqXHR) {
                    var message = B2BInquirySettings.labels.error;
                    if (jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                        message = jqXHR.responseJSON.data.message;
                    }
                    feedback.text(message);
                })
                .always(function () {
                    submitButton.prop('disabled', false);
                });
        });
    }

    $(function () {
        $('.b2b-inquiry-wrapper').each(function () {
            init($(this));
        });
    });
})(jQuery);
