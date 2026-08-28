/* =====================================================
   FARMERS INNOVATION HUB
   GLOBAL HOMEPAGE JAVASCRIPT
===================================================== */

document.addEventListener("DOMContentLoaded", function () {

    /*
     * Scroll reveal
     * -----------------------------------------------
     * Sections/cards using .reveal-on-scroll become
     * visible when they enter the viewport.
     */

    const revealElements =
        document.querySelectorAll(".reveal-on-scroll");


    if ("IntersectionObserver" in window) {

        const revealObserver =
            new IntersectionObserver(
                function (entries, observer) {

                    entries.forEach(function (entry) {

                        if (entry.isIntersecting) {

                            entry.target.classList.add(
                                "is-visible"
                            );

                            observer.unobserve(
                                entry.target
                            );

                        }

                    });

                },
                {
                    threshold: 0.15
                }
            );


        revealElements.forEach(function (element) {

            revealObserver.observe(element);

        });

    } else {

        /*
         * Older-browser fallback
         */

        revealElements.forEach(function (element) {

            element.classList.add(
                "is-visible"
            );

        });

    }

});
/*update alerts*/



/*nabar js*/
/*
|--------------------------------------------------------------------------
| FIH Mobile Navbar
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', function () {

    const toggle = document.getElementById('navbarToggle');
    const menu = document.getElementById('navbarMenu');

    if (!toggle || !menu) {
        return;
    }

    toggle.addEventListener('click', function () {

        const isOpen = menu.classList.toggle('is-open');

        toggle.setAttribute(
            'aria-expanded',
            isOpen ? 'true' : 'false'
        );

    });

});

/*
|--------------------------------------------------------------------------
| Farmer Dashboard Mobile Navigation
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', function () {

    const menuButton =
        document.getElementById('farmerMenuButton');

    const sidebar =
        document.getElementById('farmerSidebar');

    const closeButton =
        document.getElementById('farmerSidebarClose');

    const overlay =
        document.getElementById('farmerSidebarOverlay');


    if (!menuButton || !sidebar) {
        return;
    }


    function openFarmerMenu() {

        sidebar.classList.add('is-open');

        if (overlay) {
            overlay.classList.add('is-visible');
        }

        menuButton.setAttribute(
            'aria-expanded',
            'true'
        );

        document.body.style.overflow = 'hidden';
    }


    function closeFarmerMenu() {

        sidebar.classList.remove('is-open');

        if (overlay) {
            overlay.classList.remove('is-visible');
        }

        menuButton.setAttribute(
            'aria-expanded',
            'false'
        );

        document.body.style.overflow = '';
    }


    menuButton.addEventListener(
        'click',
        openFarmerMenu
    );


    if (closeButton) {

        closeButton.addEventListener(
            'click',
            closeFarmerMenu
        );

    }


    if (overlay) {

        overlay.addEventListener(
            'click',
            closeFarmerMenu
        );

    }


    /*
     * Close the menu after selecting a navigation item.
     */

    const sidebarLinks =
        sidebar.querySelectorAll('.sidebar-link');


    sidebarLinks.forEach(function (link) {

        link.addEventListener(
            'click',
            function () {

                if (
                    window.innerWidth <= 768 &&
                    !link.classList.contains('sidebar-coming-soon')
                ) {
                    closeFarmerMenu();
                }

            }
        );

    });


    /*
     * Restore normal desktop state when the screen
     * becomes wider.
     */

    window.addEventListener(
        'resize',
        function () {

            if (window.innerWidth > 768) {

                closeFarmerMenu();

            }

        }
    );

});

/*
|--------------------------------------------------------------------------
| FIH Location Selection
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', function () {

    const countySelect =
        document.getElementById('county_id');

    const subCountySelect =
        document.getElementById('sub_county_id');

    const wardSelect =
        document.getElementById('ward_id');


    if (
        !countySelect ||
        !subCountySelect ||
        !wardSelect
    ) {
        return;
    }


    const baseUrl =
        document.querySelector(
            'meta[name="fih-base-url"]'
        )?.getAttribute('content') || '';


    function resetSelect(select, text) {

        select.innerHTML = '';

        const option =
            document.createElement('option');

        option.value = '';

        option.textContent = text;

        select.appendChild(option);

        select.disabled = true;
    }


    async function loadSubCounties(
        countyId,
        selectedId = ''
    ) {

        resetSelect(
            subCountySelect,
            'Loading sub-counties...'
        );

        resetSelect(
            wardSelect,
            'Select ward'
        );


        if (!countyId) {

            resetSelect(
                subCountySelect,
                'Select sub-county'
            );

            return;
        }


        try {

            const response = await fetch(
                baseUrl +
                '/api/location.php?action=sub_counties&county_id=' +
                encodeURIComponent(countyId)
            );


            if (!response.ok) {
                throw new Error(
                    'Failed to load sub-counties.'
                );
            }


            const result =
                await response.json();


            if (!result.success) {
                throw new Error(
                    result.message ||
                    'Unable to load sub-counties.'
                );
            }


            subCountySelect.innerHTML = '';


            const defaultOption =
                document.createElement('option');

            defaultOption.value = '';

            defaultOption.textContent =
                'Select sub-county';

            subCountySelect.appendChild(
                defaultOption
            );


            result.data.forEach(function (item) {

                const option =
                    document.createElement('option');

                option.value = item.id;

                option.textContent = item.name;

                if (item.id === selectedId) {
                    option.selected = true;
                }

                subCountySelect.appendChild(
                    option
                );

            });


            subCountySelect.disabled =
                result.data.length === 0;


            if (selectedId) {

                await loadWards(
                    selectedId,
                    wardSelect.dataset.selected || ''
                );

            }


        } catch (error) {

            console.error(
                'Sub-county loading error:',
                error
            );

            resetSelect(
                subCountySelect,
                'Unable to load sub-counties'
            );
        }

    }


    async function loadWards(
        subCountyId,
        selectedId = ''
    ) {

        resetSelect(
            wardSelect,
            'Loading wards...'
        );


        if (!subCountyId) {

            resetSelect(
                wardSelect,
                'Select ward'
            );

            return;
        }


        try {

            const response = await fetch(
                baseUrl +
                '/api/location.php?action=wards&sub_county_id=' +
                encodeURIComponent(subCountyId)
            );


            if (!response.ok) {
                throw new Error(
                    'Failed to load wards.'
                );
            }


            const result =
                await response.json();


            if (!result.success) {
                throw new Error(
                    result.message ||
                    'Unable to load wards.'
                );
            }


            wardSelect.innerHTML = '';


            const defaultOption =
                document.createElement('option');

            defaultOption.value = '';

            defaultOption.textContent =
                'Select ward';

            wardSelect.appendChild(
                defaultOption
            );


            result.data.forEach(function (item) {

                const option =
                    document.createElement('option');

                option.value = item.id;

                option.textContent = item.name;

                if (item.id === selectedId) {
                    option.selected = true;
                }

                wardSelect.appendChild(
                    option
                );

            });


            wardSelect.disabled =
                result.data.length === 0;


        } catch (error) {

            console.error(
                'Ward loading error:',
                error
            );

            resetSelect(
                wardSelect,
                'Unable to load wards'
            );
        }

    }


    /*
    |--------------------------------------------------------------------------
    | County changed
    |--------------------------------------------------------------------------
    */

    countySelect.addEventListener(
        'change',
        function () {

            loadSubCounties(
                this.value
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Sub-county changed
    |--------------------------------------------------------------------------
    */

    subCountySelect.addEventListener(
        'change',
        function () {

            loadWards(
                this.value
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Existing location
    |--------------------------------------------------------------------------
    */

    const existingSubCounty =
        subCountySelect.dataset.selected || '';

    const existingWard =
        wardSelect.dataset.selected || '';


    if (countySelect.value) {

        loadSubCounties(
            countySelect.value,
            existingSubCounty
        );

    } else {

        resetSelect(
            subCountySelect,
            'Select sub-county'
        );

        resetSelect(
            wardSelect,
            'Select ward'
        );

    }

});