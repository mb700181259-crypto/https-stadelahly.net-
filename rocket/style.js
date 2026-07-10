!function(e,t){"object"==typeof exports&&"undefined"!=typeof module?module.exports=t():"function"==typeof define&&define.amd?define(t):(e=e||self,function(){var n=e.Cookies,o=e.Cookies=t();o.noConflict=function(){return e.Cookies=n,o}}())}(this,(function(){"use strict";function e(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var o in n)e[o]=n[o]}return e}return function t(n,o){function r(t,r,i){if("undefined"!=typeof document){"number"==typeof(i=e({},o,i)).expires&&(i.expires=new Date(Date.now()+864e5*i.expires)),i.expires&&(i.expires=i.expires.toUTCString()),t=encodeURIComponent(t).replace(/%(2[346B]|5E|60|7C)/g,decodeURIComponent).replace(/[()]/g,escape);var c="";for(var u in i)i[u]&&(c+="; "+u,!0!==i[u]&&(c+="="+i[u].split(";")[0]));return document.cookie=t+"="+n.write(r,t)+c}}return Object.create({set:r,get:function(e){if("undefined"!=typeof document&&(!arguments.length||e)){for(var t=document.cookie?document.cookie.split("; "):[],o={},r=0;r<t.length;r++){var i=t[r].split("="),c=i.slice(1).join("=");try{var u=decodeURIComponent(i[0]);if(o[u]=n.read(c,u),e===u)break}catch(e){}}return e?o[e]:o}},remove:function(t,n){r(t,"",e({},n,{expires:-1}))},withAttributes:function(n){return t(this.converter,e({},this.attributes,n))},withConverter:function(n){return t(e({},this.converter,n),this.attributes)}},{attributes:{value:Object.freeze(o)},converter:{value:Object.freeze(n)}})}({read:function(e){return'"'===e[0]&&(e=e.slice(1,-1)),e.replace(/(%[\dA-F]{2})+/gi,decodeURIComponent)},write:function(e){return encodeURIComponent(e).replace(/%(2[346BF]|3[AC-F]|40|5[BDE]|60|7[BCD])/g,decodeURIComponent)}},{path:"/"})}));

a4h = {

    init: function() {
        adminbarHeight = 0;
        headerHeight = 0;
        headerHeightTrue = 0;
        headerStickyRowHeight = 0;
    },

    adminbar: function() {
        const adminbar = document.querySelector('#wpadminbar');
        if ( !adminbar ) return;

        window.addEventListener('scroll', function() {
            const adminbarBounding = adminbar.getBoundingClientRect();

            adminbarHeight = Math.max(Math.min(adminbarBounding.bottom, window.innerHeight), 0);
            
            document.body.style.setProperty('--adminbar-height', adminbarHeight + 'px');
        });
    },

    header: function() {
        const header = document.querySelector('#header');
        if ( !header ) return;

        let lastScroll = 0;
        const stickyLimit = 100;

        window.addEventListener('scroll', function() {
            const currentScroll = window.scrollY;

            if ( currentScroll < stickyLimit ) {
                document.body.classList.remove('scroll-down');
                document.body.classList.remove('scroll-up');
            }
            if ( currentScroll > stickyLimit && currentScroll > lastScroll ) {
                document.body.classList.remove('scroll-up');
                document.body.classList.add('scroll-down');
            }
            if ( currentScroll > stickyLimit && currentScroll < lastScroll ) {
                document.body.classList.remove('scroll-down');
                document.body.classList.add('scroll-up');
            }

            lastScroll = currentScroll;
        });

        window.addEventListener('scroll', function() {
            const headerBounding = header.getBoundingClientRect();

            headerHeight = Math.max(Math.min(headerBounding.bottom, window.innerHeight), 0);
            headerHeightTrue = parseFloat(getComputedStyle(header).height) || 0;

            document.body.style.setProperty('--header-height', headerHeight + 'px');
            document.body.style.setProperty('--header-height-true', headerHeightTrue + 'px');
        });

        window.addEventListener('scroll', function() {
            const headerStickyRows = header.querySelectorAll('.layout-row.sticky');

            headerStickyRows.forEach((elem) => {
                let headerStickyRowStyle = getComputedStyle(elem);
                if ( headerStickyRowStyle.display === 'none' ) return;

                headerStickyRowHeight = parseFloat(headerStickyRowStyle.height) || 0;

                document.body.style.setProperty('--header-sticky-row-height', headerStickyRowHeight + 'px');
            });
        });

        window.addEventListener('resize', function() {
            headerStickyRowHeight = 0;
            document.body.style.setProperty('--header-sticky-row-height', headerStickyRowHeight + 'px');
        });
    },

    searchForm: function() {
        const searchFormDivs = document.querySelectorAll('.search-form .search-form-inner');

        searchFormDivs.forEach((elem) => {
            const searchFormField = elem.querySelector('.search-field');
            const searchFormSelect = elem.querySelector('.search-select');

            if ( !searchFormField ) return;

            elem.addEventListener('click', (event) => {
                if ( !event.target.closest('select') && !event.target.closest('[type="submit"]') ) {
                    searchFormField.focus();
                }
            });

            searchFormField.addEventListener('focus', () => {
                elem.classList.add('active');
            });
            
            searchFormField.addEventListener('blur', () => {
                elem.classList.remove('active');
            });

            if ( searchFormSelect ) {
                searchFormSelect.addEventListener('focus', () => {
                    elem.classList.add('active');
                });

                searchFormSelect.addEventListener('blur', () => {
                    elem.classList.remove('active');
                });
            }
        });
    },

    overlayToggle: function() {
        let openedOverlayPanel;
        let openedOverlayClass;

        const afterOpen = (event) => {
            if ( !event.target.closest(openedOverlayPanel) ) {
                close();
            }
        }

        const open = () => {
            document.querySelector(openedOverlayPanel).setAttribute('aria-hidden', 'false');
            document.body.classList.add('overlay-on');
            document.body.classList.add(openedOverlayClass);
            if ( openedOverlayPanel == '#overlay-search-outer' ) {
                document.querySelector('#overlay-search-outer .search-field').focus();
            }
            document.body.addEventListener('click', afterOpen);
        }

        const close = () => {
            document.querySelector(openedOverlayPanel).setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overlay-on');
            document.body.classList.remove(openedOverlayClass);
            document.body.removeEventListener('click', afterOpen);
            openedOverlayPanel = '';
            openedOverlayClass = '';
        }

        document.body.addEventListener('click', (event) => {
            const target = event.target.closest('.overlay-toggle-btn');
            if ( !target) return;
    
            event.preventDefault();

            const newOverlayPanel = target.getAttribute('data-target');
            const newOverlayClass = target.getAttribute('data-class');

            if ( openedOverlayPanel ) {
                if ( openedOverlayPanel == newOverlayPanel ) {
                    close();
                } else {
                    close();
                    openedOverlayPanel = newOverlayPanel;
                    openedOverlayClass = newOverlayClass;
                    open();
                }
            } else {
                openedOverlayPanel = newOverlayPanel;
                openedOverlayClass = newOverlayClass;  
                open();
            }
        });
    },

    navMenuClickable: function() {
        const clickableMenuItems = document.querySelectorAll('#header .nav-menu ul li.click > a');
        if ( !clickableMenuItems ) return;

        document.body.addEventListener('click', (event) => {
            if ( !event.target.closest('#header .nav-menu ul li.click') ) {
                clickableMenuItems.forEach((elem) => {
                    elem.parentNode.classList.remove('active');
                });
            }
        });
    
        clickableMenuItems.forEach((elem) => {
            elem.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
    
                elem.parentNode.classList.toggle('active');
    
                clickableMenuItems.forEach((otherElem) => {
                    if ( otherElem !== elem ) {
                        otherElem.parentNode.classList.remove('active');
                    }
                });
            });
        });
    },

    navMenuOverlay: function() {
        const menusWithChildren = document.querySelectorAll('#overlay-menu .nav-menu ul li:not(.no-toggle)');
        if ( !menusWithChildren ) return;

        menusWithChildren.forEach(function(menuWithChildren) {
            const subMenu = menuWithChildren.querySelector('ul');
            if ( !subMenu ) return;
            
            const menuWithChildrenCloned = menuWithChildren.cloneNode(true); 
            const subMenuCloned = menuWithChildrenCloned.querySelector('ul');
            const menuArrowCloned = menuWithChildrenCloned.querySelector('.menu-item-arrow');
  
            subMenuCloned.parentNode.removeChild(subMenuCloned);
            menuArrowCloned.parentNode.removeChild(menuArrowCloned);

            if ( !menuWithChildren.classList.contains('not-link') ) {
                subMenu.insertBefore(menuWithChildrenCloned, subMenu.firstChild);
            }

            const parentMenuAnchor = menuWithChildren.querySelector('a');

            parentMenuAnchor.addEventListener('click', (event) => {
                const parentMenu = parentMenuAnchor.closest('li');
                parentMenu.classList.toggle('opened');
                event.preventDefault();
            });
        });
    },

    navMenuSubMenuTheme: function() {
        const subMenuParents = document.querySelectorAll('#header .nav-menu li.theme-dark, #header .nav-menu li.theme-light');

        subMenuParents.forEach(function(elem) {
            let theme = elem.classList.contains('theme-dark') ? 'dark' : 'light';
            let subMenu = elem.querySelector('.sub-menu');
            if ( subMenu ) {
                subMenu.setAttribute('data-theme', theme);
                subMenu.setAttribute('data-bs-theme', theme);
            }
        });
    },

    switchTheme: function() {
        const switchTheme = () => {
            const currentTheme = document.body.getAttribute('data-theme');
            let newTheme = currentTheme == 'light' ? 'dark' : 'light';

            document.body.setAttribute('data-theme', newTheme);
            document.body.setAttribute('data-bs-theme', newTheme);
            
            Cookies.set('site_theme', newTheme, { expires: 30 });
        }

        document.body.addEventListener('click', (event) => {
            if ( event.target.closest('.theme-switch') ) {
                event.preventDefault();
                
                switchTheme();
            }
        });
    },

    ripple: function() {
        const createRipple = (event, targetElement) => {
            const button = targetElement;
            const buttonRect = button.getBoundingClientRect();
            button.classList.add('ripple-outer');

            const ripple = button.querySelector('.ripple');
            if ( ripple ) {
                ripple.remove();
            }
            
            const circle = document.createElement('span');
            const diameter = Math.max(button.clientWidth, button.clientHeight);
            const radius = diameter / 2;
            
            circle.style.width = circle.style.height = `${diameter}px`;
            circle.style.left = `${event.clientX - buttonRect.left - radius}px`;
            circle.style.top = `${event.clientY - buttonRect.top - radius}px`;
            circle.classList.add('ripple');

            button.appendChild(circle);
        }

        document.body.addEventListener('click', (event) => {
            const targetElement = event.target.closest('.link-icon, .nav-menu a div, .archive-links-inner a div, .nav-pages-inner a, .content-tabs-links-inner div, .ripple-link'            );
            if ( targetElement ) {
                createRipple(event, targetElement);
            }
        });     
    },

    jumpToLink: function(source, target, hash) {
        if ( source === undefined || target === undefined ) return;

        let sourcePosition = source.getBoundingClientRect().top + window.scrollY;
        let targetPosition = target.getBoundingClientRect().top + window.scrollY;;

        let offset = 0;
        offset += adminbarHeight;
        offset += 20;

        if ( document.body.classList.contains('header-fixed') ) {
            offset += headerHeightTrue;
        } else if ( document.body.classList.contains('header-dynamic') && sourcePosition > targetPosition ) {
            offset += headerHeightTrue;
        } else if ( document.body.classList.contains('header-dynamic') && sourcePosition < targetPosition ) {
            offset += headerStickyRowHeight;
        }

        if ( hash ) {
            window.location.hash = hash;
        }

        window.scrollTo({
            top: targetPosition - offset,
        });
    },

    singularBodyHashAnchor: function() {
        document.body.addEventListener('click', (event) => {

            let singularCitations = document.querySelectorAll('.singular-citations .singular-citation');
            
            singularCitations.forEach((elem) => {
                elem.classList.remove('active');
            });

            let elem = event.target.closest('.singular-body a[href^="#"]:not([href="#"])');
            
            if ( elem ) {
                let elem_href = elem.getAttribute('href');

                let source = elem;
                let target = elem.closest('.primary').querySelector(elem.getAttribute('href'));
                let hash = elem.getAttribute('href');
    
                if ( elem_href.startsWith('#c_note') ) {
                    let citations = elem.closest('.primary').querySelector('.singular-citations .singular-section-header');
                    if ( citations ) {
                        target.closest('.singular-citation').classList.add('active');
                        target = citations.parentNode;
                        citations.classList.add('active');
                    }
                }
    
                event.preventDefault();

                this.jumpToLink(source, target, hash);

                event.stopPropagation();
            }
        });
    },

    LoadMorePostsArchive: function() {
        if ( typeof theme_js_vars === 'undefined' ) return;
        if ( theme_js_vars.archive_pagination_mode != 'dynamic' && theme_js_vars.archive_pagination_mode != 'auto' ) return;
        const mode = theme_js_vars.archive_pagination_mode;

        const postsListContainer = document.querySelector('.primary-archive .primary-content-primary .posts-list-outer');
        if ( !postsListContainer ) return;

        let navDiv = postsListContainer.closest('.primary-archive').querySelector('.nav-pages-archive[data-position="bottom"]');
        if ( !navDiv ) return;

        let next = navDiv.querySelector('.next');
        if ( !next ) return;

        let nextUrl = next.href;

        let showMorebutton = navDiv.querySelector('.nav-show-more');
        let navLoading = navDiv.querySelector('.content-loading');
        let navDivInner = navDiv.querySelector('.nav-pages-inner');

        navDivInner.innerHTML = '';
        
        navDivInner.appendChild(showMorebutton);
        if ( mode == 'dynamic' ) {
            showMorebutton.style.display = 'block';
        } else {
            navLoading.style.display = 'block';
        }

        const fetchData = async () => {
            let url = nextUrl;
            let response = await fetch(url);
            let data = await response.text();
            let parser = new DOMParser();
            data = parser.parseFromString(data, 'text/html');
            return data;
        };

        const appendData = (data) => {
            let postsListFetched = data.querySelector('.primary-archive .primary-content-primary .posts-list');

            postsListContainer.insertAdjacentHTML('beforeend', postsListFetched.outerHTML);

            this.refresh();

            next = data.querySelector('.nav-pages-archive .nav-pages-inner .next');
            if ( next ) {
                nextUrl = next.href;
            } else {
                nextUrl = '';
            }
        };

        const observerCallback = async (entries) => {
            if ( entries[0].isIntersecting ) {
                if ( nextUrl ) {
                    fetchData().then(appendData).then(() => {
                        observer.disconnect();
                        observer.observe(navLoading);
                    })
                } else {
                    navDiv.remove();
                    observer.disconnect();
                }
            }
        };
            
        const observer = new IntersectionObserver(observerCallback, { threshold: 0.5 });

        if ( mode == 'auto' ) {
            observer.observe(navLoading);
        }
    
        showMorebutton.addEventListener('click', function(event) {

            event.preventDefault();

            showMorebutton.style.display = 'none';
            navLoading.style.display = 'block';
            
            fetchData().then(appendData).then(() => {
                if ( nextUrl ) {
                    showMorebutton.style.display = 'block';
                    navLoading.style.display = 'none';
                } else {
                    navDiv.remove();
                }
            });

        });
    },

    loadNextPostSingular: function() {
        let currentPost = document.querySelector('.primary-singular:last-of-type');
        if ( !currentPost ) return;

        let nextUrl = currentPost.getAttribute('data-next_post');
        if ( !nextUrl ) return;

        let navLoading = document.querySelector('.content-loading');
        if ( !navLoading ) return;
        
        navLoading.style.display = 'block';

        const fetchData = async () => {
            let url = nextUrl;
            let response = await fetch(url);
            let data = await response.text();
            let parser = new DOMParser();
            data = parser.parseFromString(data, 'text/html');
            return data;
        };

        const appendData = (data) => {
            let postFetched = data.querySelector('.primary-singular');
            currentPost = document.querySelector('.primary-singular:last-of-type');
            currentPost.insertAdjacentHTML('afterend', postFetched.outerHTML);

            this.refresh();

            nextUrl = postFetched.getAttribute('data-next_post');

            if ( !nextUrl ) {
                navLoading.remove();
            }
        };

        const observerCallback = async (entries) => {
            if ( entries[0].isIntersecting ) {
                if ( nextUrl ) {
                    fetchData().then(appendData).then(() => {
                        observer.disconnect();
                        observer.observe(navLoading);
                    })
                } else {
                    observer.disconnect();
                }
            }
        };
            
        const observer = new IntersectionObserver(observerCallback, { threshold: 0.5 });

        observer.observe(navLoading);
    
    },
    
    LoadMoreComments: function() {
        const commentsListContainer = document.querySelector('.comments-lists');
        if ( !commentsListContainer ) return;

        const navDiv = document.querySelector('.nav-pages-comments');
        if ( !navDiv ) return;

        let nextAnchor = navDiv.querySelector('.next');
        let prevAnchor = navDiv.querySelector('.prev');
        if ( !nextAnchor && !prevAnchor ) return;

        let target = prevAnchor ? 'prev' : 'next';
        let targetAnchor = prevAnchor ? prevAnchor : nextAnchor;
        let targetUrl = targetAnchor.href;

        let showMorebutton = navDiv.querySelector('.nav-show-more');
        let navLoading = navDiv.querySelector('.content-loading');

        navDivInner = navDiv.querySelector('.nav-pages-inner');
        navDivInner.innerHTML = '';
        
        navDivInner.appendChild(showMorebutton);
        
        showMorebutton.style.display = 'block';

        const fetchData = async () => {
            let url = targetUrl;
            let response = await fetch(url);
            let data = await response.text();
            let parser = new DOMParser();
            data = parser.parseFromString(data, 'text/html');
            return data;
        };

        const appendData = (data) => {
            let commentsListFetched = data.querySelector('#comments .comments-list');

            commentsListContainer.insertAdjacentHTML('beforeend', commentsListFetched.outerHTML);

            this.refresh();

            nextAnchor = data.querySelector('.nav-pages-comments .nav-pages-inner .next');
            prevAnchor = data.querySelector('.nav-pages-comments .nav-pages-inner .prev');

            targetAnchor = target == 'prev' ? prevAnchor : nextAnchor;

            if ( targetAnchor ) {
                targetUrl = targetAnchor.href;
            } else {
                targetUrl = '';
            }
        };
            
        showMorebutton.addEventListener('click', function(event) {
            
            event.preventDefault();

            showMorebutton.style.display = 'none';
            navLoading.style.display = 'block';
            
            fetchData().then(appendData).then(() => {
                if ( targetUrl ) {
                    showMorebutton.style.display = 'block';
                    navLoading.style.display = 'none';
                } else {
                    navDiv.remove();
                }
            });

        });
    },

    singularSections: function() {
        document.body.addEventListener('click', function(event) {
            let sectionHeaderToggleable = event.target.closest('.singular-section-header.toggleable');
            if ( sectionHeaderToggleable ) {
                sectionHeaderToggleable.classList.toggle('active');
            }
        });
    },

    questions: function() {
        document.body.addEventListener('click', function(event) {
            let questionHeader = event.target.closest('.question-header');
            if ( questionHeader ) {
                questionHeader.closest('.singular-question').classList.toggle('active');
            }
        });
    },

    continueReadingSingular: function() {
        let primarySingular = document.querySelectorAll('.primary.primary-singular.continue-reading-on');
    
        primarySingular.forEach((elem) => {
            let continueReadingWrap = elem.querySelector('.continue-reading-wrap');
            if ( !continueReadingWrap ) return;
    
            let continueReadingWrapClonded = continueReadingWrap.cloneNode(true);
            continueReadingWrap.remove();

            let singularBody = elem.querySelectorAll('.singular-body');

            singularBody.forEach((elem2) => {
                if ( elem2.classList.contains('continue-reading-done') ) return;
                elem2.classList.add('continue-reading-done');
                /*
                elem2.style.maxHeight = 'none';
                let height = parseInt(getComputedStyle(elem2).height);

                if ( height < 400 ) {
                    elem.classList.add('continue-reading-removed');
                    elem2.style.maxHeight = 'none';
                    return;
                }

                elem2.style.maxHeight = '400px';
                */
                
                elem2.insertAdjacentHTML('beforeend', continueReadingWrapClonded.outerHTML);
            
                let continueReadingAnchor = elem2.querySelectorAll('a.continue-reading-btn');

                continueReadingAnchor.forEach((elem3) => {
                    if ( elem3.classList.contains('continue-reading-done') ) return;
                    elem3.classList.add('continue-reading-done');

                    elem3.addEventListener('click', removeContinueReading);
                });

            });
        });

        function removeContinueReading(event) {
            event.preventDefault();

            let target = event.currentTarget;
            let singularBody = target.closest('.singular-body');

            singularBody.classList.add('continue-reading-removed');
            singularBody.querySelector('.continue-reading-wrap').remove();
        }
    },
    
    continueReadingArchiveDescription: function() {
        let archiveDescriptionContainer = document.querySelectorAll('.archive-description.continue-reading-on');
    
        archiveDescriptionContainer.forEach((elem) => {
            let continueReadingWrap = elem.querySelector('.continue-reading-wrap');
            if ( !continueReadingWrap ) return;

            let continueReadingAnchor = elem.querySelector('a.continue-reading-btn');
            if ( !continueReadingAnchor ) return;

            continueReadingAnchor.addEventListener('click', (event) => {
                event.preventDefault();
                
                elem.classList.add('continue-reading-removed');
                let continueReadingWrap = elem.querySelector('.continue-reading-wrap');
                if ( continueReadingWrap ) {
                    continueReadingWrap.remove();
                }
            });

        });
    },

    shareLinks: function() {
        document.body.addEventListener('click', (event) => {

            let moreLink = event.target.closest('.singular-share a[data-site_name="more"]');
            
            if ( moreLink ) {
                event.preventDefault();
                let data = {
                    text: moreLink.getAttribute('data-post_title'),
                    url: moreLink.href,
                }
                try {
                    navigator.share(data);
                } catch (err) {
                    console.log(err);
                }
                return false;
            }
        });        
    },

    timeFormat: function() {
        if ( typeof theme_js_vars === 'undefined' ) return;
        if ( theme_js_vars.enable_short_time === undefined ) return;

        let locale = document.documentElement.getAttribute('lang') || 'en-US';
        const rtf = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });
        const now = new Date();
        
        let timeDiv = document.querySelectorAll('time[datetime]');

        timeDiv.forEach((elem) => {
            if ( elem.classList.contains('time-format-done') ) return;
            elem.classList.add('time-format-done');

            let timeStr = elem.getAttribute('datetime');

            const date = new Date(timeStr);

            const diffInSeconds = (date - now) / 1000;
            const diffInMinutes = diffInSeconds / 60;
            const diffInHours = diffInMinutes / 60;
            const diffInDays = diffInHours / 24;
            const diffInWeeks = diffInDays / 7;
            const diffInMonths = diffInDays / 30;
            const diffInYears = diffInDays / 365;

            let output;
        
            if ( Math.abs(diffInSeconds) < 60 ) {
                output = rtf.format(Math.round(diffInSeconds), 'second');
            } else if ( Math.abs(diffInMinutes) < 60 ) {
                output = rtf.format(Math.round(diffInMinutes), 'minute');
            } else if ( Math.abs(diffInHours) < 24 ) {
                output = rtf.format(Math.round(diffInHours), 'hour');
            } else if ( Math.abs(diffInDays) < 7 ) {
                output = rtf.format(Math.round(diffInDays), 'day');
            } else if ( Math.abs(diffInWeeks) < 4 ) {
                output = rtf.format(Math.round(diffInWeeks), 'week');
            } else if ( Math.abs(diffInMonths) < 12 ) {
                output = rtf.format(Math.round(diffInMonths), 'month');
            } else {
                output = rtf.format(Math.round(diffInYears), 'year');
            }

            elem.textContent = output.replace(/\u0642\u0628\u0644/g, '\u0645\u0646\u0630');
        });
    },

    timer: function() {
        let timerDiv = document.querySelectorAll('.timer');

        timerDiv.forEach((elem) => {
            if ( elem.classList.contains('timer-done') ) return;
            elem.classList.add('timer-done');

            const timeStr = elem.getAttribute('datetime');
            const end = new Date(timeStr);

            let daysDiv = elem.querySelector('.timer-counter-item[data-type="days"] .value');
            let hoursDiv = elem.querySelector('.timer-counter-item[data-type="hours"] .value');
            let minutesDiv = elem.querySelector('.timer-counter-item[data-type="minutes"] .value');
            let secondsDiv = elem.querySelector('.timer-counter-item[data-type="seconds"] .value');

            setInterval(function() {
                const now = new Date();
                const diff = end - now;

                const days = Math.max(Math.floor(diff / (1000 * 60 * 60 * 24)), 0);
                const hours = Math.max(Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)), 0);
                const minutes = Math.max(Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)), 0);
                const seconds = Math.max(Math.floor((diff % (1000 * 60)) / 1000), 0);

                daysDiv.textContent = days;
                hoursDiv.textContent = hours;
                minutesDiv.textContent = minutes;
                secondsDiv.textContent = seconds;

            }, 1000);
        });
    },
    
    newsTicker: function() {
        let newsTickerDiv = document.querySelector('#news-ticker');
        if ( !newsTickerDiv ) return;

        newsTickerDiv.classList.add('active');

        let newsTickerItems = newsTickerDiv.querySelectorAll('.news-ticker-item');

        let randomIndex = Math.floor(Math.random() * newsTickerItems.length);
        newsTickerItems[randomIndex].classList.add('active');

        newsTickerClose = newsTickerDiv.querySelector('.news-ticker-close');

        let dismissedItemsStr = Cookies.get('newsticker_dismissed_items') || '';
        
        let dismissedItemsArr = dismissedItemsStr.split(',');
        dismissedItemsArr = dismissedItemsArr.filter(function(value) {
            return value.trim() !== '';
        });

        newsTickerClose.addEventListener('click', (event) => {
            event.preventDefault();

            dismissedItemsArr.push(newsTickerItems[randomIndex].getAttribute('data-id'));
            dismissedItemsStr = dismissedItemsArr.join(',');

            Cookies.set('newsticker_dismissed_items', dismissedItemsStr, { expires: 30 });

            newsTickerDiv.remove();
        });

    },

    tabbedWidgets: function() {
        let tabs = document.querySelectorAll('.widgets-list.tabbed');

        tabs.forEach((elem) => {
            if ( elem.classList.contains('tabs-done') ) return;
            elem.classList.add('tabs-done');

            elem.querySelector('.widget-tabs a').classList.add('active');
            elem.querySelector('.widgets-area-inner .widget').classList.add('active');

            let tabsAnchors = elem.querySelectorAll('.widget-tabs a');

            tabsAnchors.forEach((elem2) => {
                elem2.addEventListener('click', (event) => {
                    let href = elem2.getAttribute('href');

                    for ( const tabsAnchor of tabsAnchors ) {
                        if ( tabsAnchor !== elem2 ) {
                            tabsAnchor.classList.remove('active');
                        } else {
                            tabsAnchor.classList.add('active');
                        }
                    }

                    const tabsWidgets = elem.querySelectorAll('.widgets-area-inner .widget');
                    for ( const tabsWidget of tabsWidgets ) {
                        if ( '#' + tabsWidget.id !== href ) {
                            tabsWidget.classList.remove('active');
                        } else {
                            tabsWidget.classList.add('active');
                        }
                    }

                    event.preventDefault();
                });
            });
        });
    },

    viewsCounter: function() {
        if ( typeof theme_js_vars === 'undefined' ) return;
        if ( theme_js_vars.post_id === undefined ) return;

        let viewedPostsStr = Cookies.get('posts_viewed') || '';
        
        let viewedPostsArr = viewedPostsStr.split(',');
        viewedPostsArr = viewedPostsArr.filter(function(value) {
            return value.trim() !== '';
        });

        if ( viewedPostsArr.includes(theme_js_vars.post_id) ) return;
        
        viewedPostsArr.unshift(theme_js_vars.post_id);
        viewedPostsStr = viewedPostsArr.join(',');
        Cookies.set('posts_viewed', viewedPostsStr, { expires: 30 });

        if ( theme_js_vars.count_views === undefined ) return;

        fetch(theme_js_vars.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Cache-Control': 'no-cache',
            },
            body: new URLSearchParams(
                {
                    nonce: theme_js_vars.nonce,
                    action: 'a4h_count_post_views',
                    post_id: theme_js_vars.post_id,
                }
            ),
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
        })
        .catch(function(error) {
        });        
    },

    viewedPosts: function() {
        if ( typeof theme_js_vars === 'undefined' ) return;
        if ( theme_js_vars.post_id === undefined ) return;
        if ( theme_js_vars.count_views === undefined ) return;

        let referringPost = Cookies.get('post_referring') || '';
        if ( !referringPost ) {
            Cookies.set('post_referring', JSON.stringify({'type': theme_js_vars.post_type, 'id': theme_js_vars.post_id}), { expires: 30 })
            referringPost = Cookies.get('post_referring')
        }
        referringPost = JSON.parse(referringPost);
           
        if ( theme_js_vars.post_id == referringPost.id ) return;
        if ( theme_js_vars.post_type != referringPost.type ) return;
             
        fetch(theme_js_vars.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Cache-Control': 'no-cache',
            },
            body: new URLSearchParams(
                {
                    nonce: theme_js_vars.nonce,
                    action: 'a4h_add_to_viewed_posts',
                    referring_post_id: referringPost.id,
                    current_post_id: theme_js_vars.post_id,
                }
            ),
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
        })
        .catch(function(error) {
        });
    },

    stickyContent: function() {
        let sticky = document.querySelectorAll('.content-sticky');
        if ( !sticky ) return;

        window.addEventListener('scroll', function() {
            sticky.forEach((elem) => {
                let rect = elem.getBoundingClientRect();

                requestAnimationFrame(function() {
                    if ( elem.classList.contains('sticky-top') ) {
                        if ( rect.top - headerHeight <= adminbarHeight ) {
                            elem.classList.add('pinned');
                        } else {
                            elem.classList.remove('pinned');
                        }   
                    }
                    if ( elem.classList.contains('sticky-bottom') ) {
                        if ( rect.bottom < window.innerHeight ) {
                            elem.classList.remove('pinned');
                        } else {
                            elem.classList.add('pinned');
                        }
                    }
                });
            });
        });
    },

    stickyAdClose: function() {
        let stickyAds = document.querySelectorAll('.inserted[data-location="sticky"]');
        stickyAds.forEach((elem) => {
            let stickyAdClose = elem.querySelector('.inserted-sticky-close');

            stickyAdClose.addEventListener('click', (event) => {
                event.preventDefault();
                
                elem.remove();
            });
        });
    },

    toolTips: function() {
        if ( typeof bootstrap === 'undefined' ) return;
        if ( bootstrap.Tooltip === undefined ) return;

        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    },

    scrollTop: function() {
        let scrollTop = document.querySelector('#scroll-top');
        if ( !scrollTop ) return;

        scrollTop.addEventListener('click', (event) => {
            document.documentElement.scrollTop = 0;
            event.preventDefault();
        });
    },

    refresh: function() {
        this.toolTips();
        this.continueReadingSingular();
        this.continueReadingArchiveDescription();
        this.LoadMorePostsArchive();
        this.timeFormat();
        this.timer();
        this.tabbedWidgets();
        this.stickyContent();
    },

}

addEventListener('DOMContentLoaded', function() {
    Object.keys(a4h).forEach(function(key) {
        if ( key == 'refresh' ) return;
        a4h[key]();
    });
});

addEventListener('load', function() {
    window.dispatchEvent(new Event('scroll'));
});

addEventListener('resize', function() {
    window.dispatchEvent(new Event('scroll'));
});