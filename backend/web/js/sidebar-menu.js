(function ($) {
    'use strict';

    function isCollapsed() {
        return $('body').hasClass('nav-sm');
    }

    function persistCollapsedState() {
        var collapsed = isCollapsed();
        document.cookie = 'menuIsCollapsed=' + encodeURIComponent(collapsed) + '; path=/';
    }

    function closeSubmenu($item) {
        $item.removeClass('active current-page');
        $item.children('ul.child_menu').stop(true, true).slideUp(180);
    }

    function openSubmenu($item) {
        $item.addClass('active current-page');
        $item.children('ul.child_menu').stop(true, true).slideDown(180);
    }

    function initSidebarMenu() {
        var $sidebarMenu = $('#sidebar-menu .nav.side-menu');
        if (!$sidebarMenu.length) {
            return;
        }

        var $itemsWithChildren = $sidebarMenu.find('li').has('ul.child_menu');

        $itemsWithChildren.each(function () {
            var $item = $(this);
            var $submenu = $item.children('ul.child_menu');
            var hasActiveChild = $item.find('li.active').length > 0;

            if (hasActiveChild && !isCollapsed()) {
                $item.addClass('active current-page');
                $submenu.show();
            } else {
                $submenu.hide();
            }
        });

        $sidebarMenu.off('click.sidebarMenu').on('click.sidebarMenu', 'li > a', function (event) {
            var $link = $(this);
            var $item = $link.parent('li');
            var $submenu = $item.children('ul.child_menu');

            if (!$submenu.length) {
                return;
            }

            event.preventDefault();

            if ($item.hasClass('active')) {
                closeSubmenu($item);
                return;
            }

            var $siblings = $item.siblings('li').has('ul.child_menu');
            $siblings.each(function () {
                closeSubmenu($(this));
            });

            openSubmenu($item);
        });

        $('#menu_toggle').off('click.sidebarToggle').on('click.sidebarToggle', function () {
            window.setTimeout(function () {
                persistCollapsedState();
                if (isCollapsed()) {
                    $itemsWithChildren.children('ul.child_menu').hide();
                }
            }, 0);
        });
    }

    $(initSidebarMenu);
})(jQuery);

