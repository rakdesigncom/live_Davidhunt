function rootFunction() {
    document.getElementById("megamenu_contact_form").reset();
}
var desktopStartFrom = 769;
var mobileMaxWidth = 768;
require(['jquery','Rootways_Megamenu/js/masonry.pkgd.min', 'matchMedia'],function($,Masonry){
    $(document).ready(function() {

        window.onscroll = function() {myFunction()};
        var navbar = document.getElementsByClassName("nav-sections")[0];
        function myFunction() {
            if (navbar != null) {
                if (window.pageYOffset >= navbar.getBoundingClientRect().top && window.pageYOffset > 0) {
                    navbar.classList.add("sticky");
                } else {
                    navbar.classList.remove("sticky");
                }
            }
        }

        if (('ontouchstart' in window)) {
            $('.rootmenu-list li a').click(function(e) {
                if(window.innerWidth >= mobileMaxWidth) {
                    if (($(this).closest("li").children(".halfmenu").length) ||
                        ($(this).closest("li").children(".megamenu").length) ||
                        ($(this).closest("li").children("ul").length) ||
                        ($(this).closest("li").children(".verticalopen").length) ||
                        ($(this).closest("li").children(".verticalopen02").length)
                    )
                    {
                        var clicks = $(this).data('clicks');
                        if (clicks) {
                        } else {
                            e.preventDefault();
                        }
                        $(this).data("clicks", !clicks);
                    }
                }
            });
        }
        $('.rw-dropdownclose').click(function(e) {
            jQuery(this).closest('li').removeClass('hover');
        });
        $('.rw-navclose').click(function(e) {
            jQuery('[data-action="toggle-nav"]').click();
        });

        jQuery('.rootmenu-list li').has('.rootmenu-submenu, .rootmenu-submenu-sub, .rootmenu-submenu-sub-sub').prepend('<span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>');
        jQuery('.rootmenu-list li').has('.megamenu').prepend('<span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>');
        jQuery('.rootmenu-list li').has('.halfmenu').prepend('<span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>');
        jQuery('.tabmenu02 li').has('.verticalopen02').prepend('<span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>');

        /* For All Categories mega menu */
        jQuery('.verticalmenu02 .vertical-list li').has('.varticalmenu_main').prepend('<span class="desktop-vertical-click"><i class="verticalmenu-arrow fa fa-angle-right" aria-hidden="true"></i></span><span class="vertical-click"><i class="verticalmenu-arrow fa fa-angle-down" aria-hidden="true"></i></span>');
        jQuery('.vertical-click').click(function() {
            jQuery(this).siblings('.varticalmenu_main').slideToggle('slow');
            jQuery(this).siblings('ul.menu').slideToggle('slow');
            jQuery(this).siblings('.level3-popup').slideToggle('slow');
            jQuery(this).children('.verticalmenu-arrow').toggleClass('verticalmenu-rotate');
            loadMasonry();
            setTimeout(function() {
                loadMasonry();
            }, 500);
        });

        jQuery('.rootmenu-click').click(function() {
            jQuery(this).siblings('.rootmenu-submenu').slideToggle('slow');
            jQuery(this).children('.rootmenu-arrow').toggleClass('rootmenu-rotate');
            jQuery(this).siblings('.rootmenu-submenu-sub').slideToggle('slow');
            jQuery(this).siblings('.rootmenu-submenu-sub-sub').slideToggle('slow');
            jQuery(this).siblings('.megamenu').slideToggle('slow');
            jQuery(this).siblings('.halfmenu').slideToggle('slow');
            jQuery(this).siblings('.verticalopen02').slideToggle('slow');
            jQuery(this).siblings('.verticalmenu02').slideToggle('slow');
            jQuery(this).siblings('.tabbing-sidebyside').slideToggle('slow');
            jQuery(this).siblings('.colultabtwo').slideToggle('slow');
            jQuery(this).siblings('.colultabthree').slideToggle('slow');
            jQuery(this).siblings('.resultdiv').slideToggle('slow');
            loadMasonry();
            jQuery(this).siblings('.level4-listing').slideToggle('slow');
            setTimeout(function() {
                loadMasonry();
            }, 500);
        });
        jQuery('.level2-popup .rootmenu-click').click(function(){
            jQuery(this).closest('li').find('.level3-popup').slideToggle('slow');
        });
        jQuery('.level3-popup .rootmenu-click').click(function(){
            jQuery(this).closest('li').find('.level4-popup').slideToggle('slow');
        });

        var elemsToMove = [
            '.logo',
            '.minicart-wrapper',
            '.panel.header',
            '.block.block-search',
        ];
        var mobileContainerName = '#mobile-header-sidebar';
        var desktopContainerName = '.page-header .header.content';
        var movedToSidebar = false;
        jQuery(".nav-toggle").click(function() {
            if (jQuery("html").hasClass("nav-open")) {
                jQuery("html").removeClass('nav-before-open nav-open');
                if (movedToSidebar) {
                    $.each(elemsToMove, function (_, item) {
                        $(mobileContainerName + ' ' + item).appendTo($(desktopContainerName));
                    });
                    movedToSidebar = false;
                }
            } else {
                jQuery("html").addClass('nav-before-open nav-open');
                if (!movedToSidebar) {
                    $.each(elemsToMove, function (_, item) {
                        $(desktopContainerName + ' ' + item).appendTo($(mobileContainerName));
                    });
                    movedToSidebar = true;
                }
            }
        });
        mediaCheck({
            media: '(max-width: 768px)',
            entry: function () {
                if (!movedToSidebar && $("html").hasClass("nav-open")) {
                    $.each(elemsToMove, function (_, item) {
                        $(desktopContainerName + ' ' + item).appendTo($(mobileContainerName));
                    });
                    movedToSidebar = true;
                }
            },
            exit: function () {
                if (movedToSidebar) {
                    $.each(elemsToMove, function (_, item) {
                        $(mobileContainerName + ' ' + item).appendTo($(desktopContainerName));
                    });
                    movedToSidebar = false;
                }
            }
        });

        setmenuheight();
        setmenuheight_horizontal();
        setmenuheight_multitab('1');
        setAllCategoryMenuHeight();
        jQuery(window).bind("load resize", function() {
            var w_height = jQuery( window ).width();
            if(w_height <= desktopStartFrom) {
                jQuery(".tabmenu").css('height','100%');
                jQuery(".tabmenu02").css('height','100%');
                jQuery(".verticalopen").css('height','100%');
            } else {
                setmenuheight();
            }
            setAllCategoryMenuHeight();
            loadMasonry();
        });

        jQuery(".rootmenu-list > li").hover(
            function() {
                jQuery( this ).addClass("hover");
                setmenuheight();
                loadMasonry();
                if(jQuery(this).has(".fourcoltab")) {
                    setmenuheight_multitab('1');
                }
            }, function() {
                jQuery(this).removeClass("hover");
            }
        );

        jQuery(".vertical-menu > li").hover(
            function() {
                loadMasonry();
            }, function() {
            }
        );

        jQuery(".all-category-wrapper .verticalmenu02 .vertical-list > li, .all-category-wrapper").hover(
            function() {
                loadMasonry();
                setAllCategoryMenuHeight();
                jQuery(this).addClass("hover_allcategories");
            }, function() {
                jQuery(this).removeClass("hover_allcategories");
            }
        );

        jQuery(".vertical-menu02 > li > a").hover(
            function() {
                setmenuheight_horizontal();
            }, function() {
            }
        );
        var event = ('ontouchstart' in window) ? 'click' : 'mouseenter mouseleave';
        jQuery('.vertical-menu02 > li').on(event, function () {
            jQuery('.hover .vertical-menu02 > li').removeClass('main_openactive02');
            jQuery(this).addClass('main_openactive02');
        });
        jQuery('.vertical-menu > li').on(event, function () {
            jQuery('.hover .vertical-menu > li').removeClass('main_openactive01');
            jQuery(this).addClass('main_openactive01');
            setmenuheight();
        });

        jQuery(".colultabone > li > a").hover(
            function() {
                setmenuheight_multitab('1');
            }, function() {

            }
        );
        /*
        jQuery('.colultabone > li').on(event, function () {
            jQuery('.colultabone > li').removeClass('main_openactive03');
            jQuery('.colultabtwo > li').removeClass('main_openactive03_sub1');
            jQuery('.colultabthree > li').removeClass('main_openactive03_sub2');
            jQuery(this).addClass('main_openactive03');
            setmenuheight_multitab('1');
        });
        jQuery('.colultabtwo > li').on(event, function () {
            jQuery('.colultabtwo > li').removeClass('main_openactive03_sub1');
            jQuery('.colultabthree > li').removeClass('main_openactive03_sub2');
            jQuery(this).addClass('main_openactive03_sub1');
            setmenuheight_multitab('2');
        });
         jQuery('.colultabthree > li').on(event, function () {
            jQuery('.colultabthree > li').removeClass('main_openactive03_sub2');
            jQuery(this).addClass('main_openactive03_sub2');
             setmenuheight_multitab('3')
        });
        */
        jQuery('.colultabone > li').on(event, function () {
            jQuery('.colultabone > li').removeClass('main_openactive03');
            jQuery(this).addClass('main_openactive03');

            if (!jQuery(this).find('.colultabtwo').find('li').hasClass('main_openactive03_sub1')) {
                //jQuery(this).find('.colultabtwo').find('li').removeClass('main_openactive03_sub1');
                //jQuery(this).find('.colultabthree').find('li').removeClass('main_openactive03_sub2');

                jQuery(this).find('.colultabtwo').find('li').first().addClass('main_openactive03_sub1');
                jQuery(this).find('.colultabthree').find('li').first().addClass('main_openactive03_sub2');
            }
            setmenuheight_multitab('1');
        });
        jQuery('.colultabtwo > li').on(event, function () {
            jQuery('.colultabtwo > li').removeClass('main_openactive03_sub1');
            jQuery(this).addClass('main_openactive03_sub1');

            if (!jQuery(this).find('.colultabthree').find('li').hasClass('main_openactive03_sub2')) {
                jQuery(this).find('.colultabthree').find('li').first().addClass('main_openactive03_sub2');
            }
            setmenuheight_multitab('2');
        });

        jQuery('.colultabthree > li').on(event, function () {
            jQuery('.colultabthree > li').removeClass('main_openactive03_sub2');
            jQuery(this).addClass('main_openactive03_sub2');

            setmenuheight_multitab('3')
        });

        jQuery("#megamenu_submit").click(function(){
            var name = jQuery("#name").val();
            var menuemail = document.getElementById('menuemail');
            var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            if (!filter.test(menuemail.value)) {
                alert('Please provide a valid email address');
                menuemail.focus;
                return false;
            }

            var menuemail = jQuery("#menuemail").val();
            var comment = jQuery("#comment").val();
            var telephone = jQuery("#telephone").val();
            var hideit = jQuery("#hideit").val();
            var base_url = jQuery("#base_url").val();

            var dataString = 'name='+ name + '&email='+ menuemail + '&comment='+ comment + '&telephone='+ telephone + '&hideit='+ hideit;
            if(name==''||menuemail==''||comment==''){
                alert("Please Fill All Fields");
            } else {
                jQuery('#megamenu_submit').attr('id','menu_submit_loader');
                jQuery.ajax({
                    type: "POST",
                    url: base_url+"contact/index/post/",
                    data: dataString,
                    cache: false,
                    success: function(result){
                        alert('Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.');
                        jQuery('#menu_submit_loader').attr('id','megamenu_submit');
                    }
                });
            }
            return false;
        });
        jQuery(".categoriesmenu li > a").hover(
            function() {
                var c_block = jQuery(this).closest('li').find('.categoryblockcontent').html();
                //var m_c_block = jQuery(this).closest('.megamenu').find('.main_categoryblockcontent').html();
                if (c_block){
                    jQuery(this).closest('.megamenu').find('.right.rootmegamenu_block').html(c_block);
                } else {
                    //jQuery(this).closest('.megamenu').find('.right.rootmegamenu_block').html(m_c_block);
                }

            }, function() {
                //jQuery( this ).removeClass( "hover" );
            }
        );
    });

    var msnry;
    function loadMasonry() {
        if (document.getElementsByClassName("rootmenu")[0]) {
            var rootmenu_cls = document.getElementsByClassName("rootmenu")[0];
            var elem = rootmenu_cls.getElementsByClassName("grid");
            var n = elem.length;
            for (var i = 0; i < n; i++) {
                /*msnry = new Masonry( elem[i], {

                });
                if(window.innerWidth < desktopStartFrom) {
                    msnry.destroy();
                }*/
                if(typeof msnry !== "undefined" && window.innerWidth < desktopStartFrom) {
                    msnry.destroy();
                    elem[i].removeAttribute('style', '');
                    jQuery('.grid > div').attr('style', '');
                } else {
                    msnry = new Masonry( elem[i], {
                        //horizontalOrder: true,
                    });
                }
            }
        }
    }
    loadMasonry();
});

function setmenuheight() {
    var w_inner_width = window.innerWidth;
    if(w_inner_width <= desktopStartFrom){
        jQuery(".tabmenu").css('height','100%');
        jQuery(".tabmenu02").css('height','100%');
        jQuery(".verticalopen").css('height','100%');
    } else {
        var MultitabMaxHeight = jQuery(".hover .tabmenu .mainmenuwrap").innerHeight();
        var MultitabMaxHeight2 = jQuery(".hover .tabmenu .main_openactive01 .verticalopen").innerHeight();
        MultitabMaxHeight = MultitabMaxHeight2 > MultitabMaxHeight ? MultitabMaxHeight2 : MultitabMaxHeight;
        jQuery(".hover .tabmenu .main_openactive01 .verticalopen").css('min-height',MultitabMaxHeight);
        jQuery(".hover .tabmenu").css('height',MultitabMaxHeight+20);
    }
}

function setmenuheight_horizontal() {
    var w_inner_width = window.innerWidth;
    if(w_inner_width <= desktopStartFrom){
        jQuery(".tabmenu02").css('height','100%');
        jQuery(".verticalopen02").css('height','100%');
    } else {
        var final_hor_width = jQuery('.main_openactive02 .verticalopen02').innerHeight();
        //console.log('hegith--'+final_hor_width);
        jQuery(".main_openactive02 .verticalopen02").css('height',final_hor_width);
        jQuery(".hover .tabmenu02").css('height',final_hor_width+80);
    }
}


function setmenuheight_multitab(val) {
    var w_inner_width = window.innerWidth;
    if(w_inner_width <= desktopStartFrom){
        jQuery(".fourcoltab").css('height','100%');
    } else {
        var MultitabMaxHeight = jQuery(".hover .fourcoltab .colultabone").innerHeight();
        var MultitabMaxHeight2 = jQuery(".hover .fourcoltab .main_openactive03 .colultabtwo").innerHeight();



        var MultitabMaxHeight3 = jQuery(".hover .fourcoltab .main_openactive03 .main_openactive03_sub1 .colultabthree").innerHeight();
        var MultitabMaxHeight4 = jQuery(".hover .fourcoltab .main_openactive03 .main_openactive03_sub1 .colultabthree .main_openactive03_sub2 .resultdiv").innerHeight();

        MultitabMaxHeight = MultitabMaxHeight2 > MultitabMaxHeight ? MultitabMaxHeight2 : MultitabMaxHeight;
        MultitabMaxHeight = MultitabMaxHeight3 > MultitabMaxHeight ? MultitabMaxHeight3 : MultitabMaxHeight;
        MultitabMaxHeight = MultitabMaxHeight4 > MultitabMaxHeight ? MultitabMaxHeight4 : MultitabMaxHeight;

        jQuery(".hover .fourcoltab .main_openactive03 .colultabtwo").css('min-height',MultitabMaxHeight);
        jQuery(".hover .fourcoltab .main_openactive03 .main_openactive03_sub1 .colultabthree").css('min-height',MultitabMaxHeight);
        jQuery(".hover .fourcoltab .main_openactive03 .main_openactive03_sub1 .resultdiv").css('min-height',MultitabMaxHeight);
        jQuery(".hover .fourcoltab .colultabonenofound").css('min-height',MultitabMaxHeight);
        jQuery(".hover .fourcoltab").css('height',MultitabMaxHeight+20);



        /*
        var MultitabMaxHeight3;

        MultitabMaxHeight = MultitabMaxHeight2 > MultitabMaxHeight ? MultitabMaxHeight2 : MultitabMaxHeight;
        if (val == '2') {
            MultitabMaxHeight3 = jQuery(".hover .fourcoltab .main_openactive03_sub1 .colultabthree").innerHeight();
            MultitabMaxHeight = MultitabMaxHeight3 > MultitabMaxHeight ? MultitabMaxHeight3 : MultitabMaxHeight;

            jQuery(".hover .fourcoltab .main_openactive03_sub1 .colultabthree").css('min-height',MultitabMaxHeight);
            jQuery(".hover .fourcoltab .colultabonenofound").css('min-height',MultitabMaxHeight);
            jQuery(".hover .fourcoltab").css('height',MultitabMaxHeight+20);
        } else if (val == '3') {

            MultitabMaxHeight3 = jQuery(".hover .fourcoltab .main_openactive03_sub1 .colultabthree").innerHeight();
            MultitabMaxHeight = MultitabMaxHeight3 > MultitabMaxHeight ? MultitabMaxHeight3 : MultitabMaxHeight;

            var MultitabMaxHeight4 = jQuery(".hover .fourcoltab .main_openactive03_sub1 .colultabthree .main_openactive03_sub2 .resultdiv").innerHeight();
            MultitabMaxHeight = MultitabMaxHeight4 > MultitabMaxHeight ? MultitabMaxHeight4 : MultitabMaxHeight;

            jQuery(".hover .fourcoltab .main_openactive03_sub1 .resultdiv").css('min-height',MultitabMaxHeight);
            jQuery(".hover .fourcoltab .main_openactive03_sub1 .colultabonenofound").css('min-height',MultitabMaxHeight);
            jQuery(".hover .fourcoltab").css('height',MultitabMaxHeight+20);
        } else {
            jQuery(".hover .fourcoltab .main_openactive03 .colultabtwo").css('min-height',MultitabMaxHeight);
            jQuery(".hover .fourcoltab").css('height',MultitabMaxHeight+20);
        }
        */
    }
}

function setAllCategoryMenuHeight() {
    var wrapper_width =  jQuery(".rootmenu").innerWidth();
    var left_side_width =  jQuery(".verticalmenu02").innerWidth();
    if (jQuery( window ).width() < desktopStartFrom) {
        jQuery(".varticalmenu_main.fullwidth").css('margin-left',0);
        jQuery(".varticalmenu_main.fullwidth").css('width','100%');
        jQuery(".varticalmenu_main.fullwidth").css('min-height','auto');
        jQuery(".varticalmenu_main.halfwidth").css('margin-left',0);
        jQuery(".varticalmenu_main.halfwidth").css('width','100%');
        jQuery(".varticalmenu_main.halfwidth").css('min-height','auto');
    } else {
        var MultitabMaxHeight = jQuery(".all-category-wrapper ul.vertical-list").innerHeight();
        var MultitabMaxHeight2 = jQuery(".all-category-wrapper ul.vertical-list .hover_allcategories").innerHeight();
        MultitabMaxHeight = MultitabMaxHeight2 > MultitabMaxHeight ? MultitabMaxHeight2 : MultitabMaxHeight;

        jQuery('.all-category-wrapper .varticalmenu_main').css("display", "");
        jQuery(".all-category-wrapper .varticalmenu_main").css('min-height', MultitabMaxHeight);
        jQuery(".all-category-wrapper .varticalmenu_main").css('margin-left', left_side_width);
        jQuery(".all-category-wrapper .varticalmenu_main.halfwidth").css('width', wrapper_width/2);
        jQuery(".all-category-wrapper .varticalmenu_main.fullwidth").css('width', wrapper_width-left_side_width);
    }
}
