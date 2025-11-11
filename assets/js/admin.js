(function ($) {
    'use strict';

    $(function () {
        var emailList = $('#b2b-inquiry-email-list');
        var webhookList = $('#b2b-inquiry-webhook-list');

        emailList.data('remove-label', B2BInquiryAdmin.removeLabel);
        webhookList.data('remove-label', B2BInquiryAdmin.removeLabel);

        $('#b2b-inquiry-add-email').on('click', function () {
            var field = $('<div class="b2b-inquiry-repeatable"><input type="email" name="' + B2BInquiryAdmin.optionKey + '[emails][]" class="regular-text" placeholder="notification@example.com" /> <button type="button" class="button b2b-inquiry-remove">' + B2BInquiryAdmin.removeLabel + '</button></div>');
            emailList.append(field);
        });

        $('#b2b-inquiry-add-webhook').on('click', function () {
            var field = $('<div class="b2b-inquiry-repeatable"><input type="url" name="' + B2BInquiryAdmin.optionKey + '[webhooks][]" class="regular-text" placeholder="https://example.com/webhook" /> <button type="button" class="button b2b-inquiry-remove">' + B2BInquiryAdmin.removeLabel + '</button></div>');
            webhookList.append(field);
        });

        $(document).on('click', '.b2b-inquiry-remove', function () {
            $(this).closest('.b2b-inquiry-repeatable').remove();
        });
    });
})(jQuery);
