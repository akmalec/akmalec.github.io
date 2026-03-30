/*
Script  : Main JS - XSS-Hardened Version
Version : 1.1
Author  : Surjith S M, modifications by Copilot
URI     : http://themeforest.net/user/surjithctly
Copyright © All rights Reserved
*/

/* global $, WOW, Modernizr, $.validator */
$(function() {
    "use strict";

    // On Scroll Menu Reveal
    $(window).scroll(function() {
        if ($(window).scrollTop() > 600) {
            $('.js-reveal-menu').removeClass('reveal-menu-hidden').addClass('reveal-menu-visible');
        } else {
            $('.js-reveal-menu').removeClass('reveal-menu-visible').addClass('reveal-menu-hidden');
        }
    });

    // Parallax Header
    if ($('.parallax-bg').length) {
        $('.parallax-bg').parallax({ speed: 0.20 });
    }

    // Flex Slider
    if ($('.flexslider').length) {
        $('.flexslider').flexslider({
            animation: "slide",
            useCSS: Modernizr.touch
        });
    }

    // Countdown Timer (data-event-date must be hardcoded, not user-provided)
    var get_date = $('#countdown').data('event-date');
    if (get_date) {
        $("#countdown").countdown({
            date: get_date,
            format: "on"
        });
    }

    // Tabs (static anchors only)
    $('#schedule-tabs a').on("click",function(e) {
        e.preventDefault();
        $(this).tab('show');
    });

    // Stat Counter
    $('#stats-counter').appear(function() {
        $('.count').countTo({ refreshInterval: 50 });
    });

    // Slick Sliders
    if ($('.slick-slider').length) {
        $('.slick-slider').slick({
            slidesToShow: 6,
            slidesToScroll: 6,
            infinite: true,
            autoplay: false,
            arrows: true,
            dots: true,
            responsive: [
                { breakpoint: 1200, settings: { arrows: true, slidesToShow: 5, slidesToScroll: 5 } },
                { breakpoint: 992, settings: { slidesToShow: 3, slidesToScroll: 3 } },
                { breakpoint: 520, settings: { slidesToShow: 1, slidesToScroll: 1 } }
            ]
        });
    }
    if ($('.sponsor-slider').length) {
        $('.sponsor-slider').slick({
            centerMode: true,
            centerPadding: '30px',
            slidesToShow: 2,
            autoplay: true,
            arrows: true,
            responsive: [
                { breakpoint: 768, settings: { arrows: false, centerMode: true, centerPadding: '40px', slidesToShow: 3 } },
                { breakpoint: 480, settings: { arrows: false, centerMode: true, centerPadding: '40px', slidesToShow: 1 } }
            ]
        });
    }
    if ($('.speaker-slider').length) {
        $('.speaker-slider').slick({
            slidesToShow: 6,
            autoplay: false,
            arrows: true,
            responsive: [
                { breakpoint: 1200, settings: { arrows: true, slidesToShow: 5 } },
                { breakpoint: 992, settings: { slidesToShow: 3 } },
                { breakpoint: 520, settings: { slidesToShow: 1 } }
            ]
        });
    }

    // Scroll functions - Restrict anchor to valid IDs only
    $(window).scroll(function() {
        if ($(window).scrollTop() > 1000) {
            $('.back_to_top').fadeIn('slow');
        } else {
            $('.back_to_top').fadeOut('slow');
        }
    });
    $('nav a[href^="#"]:not([href="#"]), .back_to_top').on('click', function(event) {
        var target = $(this).attr('href');
        // Only allow anchors which are valid HTML IDs.
        if (!target || !/^#[a-zA-Z][\w\-\:\.]*$/.test(target)) return;
        var $tgt = $(target);
        if ($tgt.length) {
            $('html, body').stop().animate({
                scrollTop: $tgt.offset().top - 50
            }, 1500);
        }
        event.preventDefault();
    });

    // Initialize WOW JS for static effect
    if ($('body').hasClass('animate-page')) {
        var wow = new WOW({
            animateClass: 'animated',
            offset: 100,
            mobile: false
        });
        wow.init();
    }
});

// =====================
// Video Gallery - SAFE only for trusted URLs
// =====================
$(".play-video").on("click",function(e) {
    e.preventDefault();
    var videourl = $(this).data("video-url");

    // Only allow HTTPS YouTube & Vimeo (static HTML only)
    try {
        var url = new URL(videourl, window.location.origin);
        if (url.protocol !== 'https:') return;
        var allowedDomains = ['youtube.com', 'youtu.be', 'youtube-nocookie.com', 'vimeo.com'];
        // Strict check only at domain end (.com) for static site.
        var domainAllowed = allowedDomains.some(function(domain) {
            return url.hostname === domain || url.hostname.endsWith('.' + domain);
        });
        if (!domainAllowed) return;
    } catch(err) {
        return;
    }

    // Insert a static spinner (no user-data)
    $(this).append($('<i>').addClass('video-loader fa fa-spinner fa-spin'));
    $('.media-video iframe').attr('src', videourl);
    setTimeout(function() {
        $('.video-loader').remove();
    }, 1000);
});

// =====================
// Magnific Popup - Remove user-data from strings
// =====================
if ($('.popup-gallery').length) {
    $('.popup-gallery').magnificPopup({
        delegate: 'a',
        type: 'image',
        tLoading: 'Loading image...',
        mainClass: 'mfp-img-mobile',
        gallery: {
            enabled: true,
            navigateByImgClick: true,
            preload: [0, 1]
        },
        image: {
            tError: 'The image could not be loaded.' // do not inject user values
        },
        zoom: {
            enabled: true,
            duration: 300,
            opener: function(element) {
                return element.find('img');
            }
        }
    });
}

// =====================
// jQuery Validate: for design, no XSS vector
// =====================
if (typeof $.validator !== "undefined") {
    $.validator.setDefaults({
        highlight: function(element) {
            $(element).closest('.form-group').addClass('has-error');
        },
        unhighlight: function(element) {
            $(element).closest('.form-group').removeClass('has-error');
        },
        errorElement: 'small',
        errorClass: 'help-block',
        errorPlacement: function(error, element) {
            if (element.parent('.input-group').length) {
                error.insertAfter(element.parent());
            } else if (element.parent('label').length) {
                error.insertAfter(element.parent());
            } else {
                error.insertAfter(element);
            }
        }
    });
    // Paypal registration form validation rules (static input only)
    $("#paypal-regn").validate({
        rules: {
            first_name: "required",
            last_name: "required",
            email: {
                required: true,
                email: true
            },
            os0: "required",
            quantity: "required",
            agree: "required"
        },
        messages: {
            first_name: "Your first name",
            last_name: "Your last name",
            email: "We need your email address",
            os0: "Choose your Pass",
            quantity: "How many seats",
            agree: "Please accept our terms and privacy policy"
        },
        submitHandler: function(form) {
            $("#reserve-btn").attr("disabled", true);
            form.submit();
        }
    });
}

// =====================
// Add to Calendar (3rd party script load, static only)
// =====================
(function() {
    if (window.addtocalendar && typeof window.addtocalendar.start == "function") return;
    if (window.ifaddtocalendar === undefined) {
        window.ifaddtocalendar = 1;
        var d = document,
            s = d.createElement('script'),
            g = 'getElementsByTagName';
        s.type = 'text/javascript';
        s.charset = 'UTF-8';
        s.async = true;
        s.src = (window.location.protocol === 'https:' ? 'https' : 'http') + '://addtocalendar.com/atc/1.5/atc.min.js';
        var h = d[g]('body')[0];
        if(h) h.appendChild(s);
    }
})();

// =====================
// Twitter Widget Loader (from Twitter CDN only)
// =====================
window.twttr = (function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0],
        t = window.twttr || {};
    if (d.getElementById(id)) return t;
    js = d.createElement(s);
    js.id = id;
    js.src = "https://platform.twitter.com/widgets.js";
    fjs.parentNode.insertBefore(js, fjs);

    t._e = [];
    t.ready = function(f) {
        t._e.push(f);
    };
    return t;
}(document, "script", "twitter-wjs"));
