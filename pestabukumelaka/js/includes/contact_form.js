$(function() {
    "use strict";

    $("#phpcontactform").submit(function(e) {
        e.preventDefault();
    }).validate({
        rules: {
            first_name: "required",
            last_name: "required",
            email: {
                required: true,
                email: true
            },
            phone: {
                required: true,
                number: true
            },
            message: "required",
        },
        messages: {
            first_name: "Your first name please",
            last_name: "Your last name please",
            email: "We need your email address",
            phone: "Please enter your phone number",
            message: "Please enter your message",
        },
        submitHandler: function(form) {
            $("#js-contact-btn").attr("disabled", true);

            // SECURITY: Get redirect safely - whitelist allowed values
            var redirect = $('#phpcontactform').data('redirect');
            var noredirect = !redirect || redirect === 'none' || redirect === "";
            
            // SECURITY: Validate redirect URL
            var allowedHosts = ['rabaklit.com', 'akmalec.github.io'];
            if (redirect && !noredirect) {
                try {
                    var url = new URL(redirect, window.location.origin);
                    var isAllowed = allowedHosts.some(host => url.hostname.includes(host));
                    if (!isAllowed) {
                        noredirect = true;
                        redirect = null;
                    }
                } catch (e) {
                    noredirect = true;
                    redirect = null;
                }
            }

            $("#js-contact-result").text('Please wait...');

            // SECURITY: Get messages and escape them
            var success_msg = $('<div>').text($('#js-contact-result').data('success-msg') || 
                'Thank you! Your message has been sent successfully.').html();
            var error_msg = $('<div>').text($('#js-contact-result').data('error-msg') || 
                'Sorry! There was an error sending your message.').html();

            var dataString = $(form).serialize();

            $.ajax({
                type: "POST",
                data: dataString,
                url: "php/contact.php",
                cache: false,
                success: function(d) {
                    $(".form-group").removeClass("has-success");
                    if (d === 'success') {
                        if (noredirect) {
                            // SECURITY: Use text() to prevent XSS
                            $('#js-contact-result')
                                .fadeIn('slow')
                                .html('<div class="alert alert-success top-space"></div>')
                                .find('.alert-success').text(success_msg)
                                .delay(3000)
                                .fadeOut('slow');
                        } else {
                            // SECURITY: Validate before redirect
                            window.location.href = redirect;
                        }
                    } else {
                        $('#js-contact-result')
                            .fadeIn('slow')
                            .html('<div class="alert alert-danger top-space"></div>')
                            .find('.alert-danger').text(error_msg)
                            .delay(3000)
                            .fadeOut('slow');
                    }
                    $("#js-contact-btn").attr("disabled", false);
                },
                error: function() {
                    $('#js-contact-result')
                        .fadeIn('slow')
                        .html('<div class="alert alert-danger top-space"></div>')
                        .find('.alert-danger').text(error_msg);
                    $("#js-contact-btn").attr("disabled", false);
                }
            });
            return false;
        }
    });
});
