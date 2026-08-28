/* ============================================================
   FARMERS INNOVATION HUB
   LOCATION SELECTOR

   County → Sub-county → Ward
============================================================ */

document.addEventListener(
    "DOMContentLoaded",
    () => {

        const countySelect =
            document.getElementById("county_id");

        const subCountySelect =
            document.getElementById("sub_county_id");

        const wardSelect =
            document.getElementById("ward_id");


        if (
            !countySelect ||
            !subCountySelect ||
            !wardSelect
        ) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Values preserved after failed registration
        |--------------------------------------------------------------------------
        */

        const savedSubCounty =
            subCountySelect.dataset.selected || "";

        const savedWard =
            wardSelect.dataset.selected || "";


        /*
        |--------------------------------------------------------------------------
        | Reset select
        |--------------------------------------------------------------------------
        */

        function resetSelect(
            select,
            placeholder
        ) {

            select.innerHTML = "";

            const option =
                document.createElement("option");

            option.value = "";

            option.textContent =
                placeholder;

            select.appendChild(option);

            select.disabled = true;
        }


        /*
        |--------------------------------------------------------------------------
        | Populate select
        |--------------------------------------------------------------------------
        */

        function populateSelect(
            select,
            items,
            placeholder,
            selectedValue = ""
        ) {

            resetSelect(
                select,
                placeholder
            );


            items.forEach(
                item => {

                    const option =
                        document.createElement(
                            "option"
                        );

                    option.value =
                        item.id;

                    option.textContent =
                        item.name;


                    if (
                        item.id ===
                        selectedValue
                    ) {

                        option.selected =
                            true;
                    }


                    select.appendChild(
                        option
                    );

                }
            );


            select.disabled =
                items.length === 0;
        }


        /*
        |--------------------------------------------------------------------------
        | Load sub-counties
        |--------------------------------------------------------------------------
        */

        async function loadSubCounties(
            countyId,
            selectedValue = ""
        ) {

            resetSelect(
                subCountySelect,
                "Loading sub-counties..."
            );

            resetSelect(
                wardSelect,
                "Select sub-county first"
            );


            if (!countyId) {

                resetSelect(
                    subCountySelect,
                    "Select county first"
                );

                return;
            }


            try {

                const response =
                    await fetch(
                        `api/locations/sub-counties.php?county_id=${encodeURIComponent(countyId)}`,
                        {
                            method: "GET",

                            headers: {
                                "Accept":
                                    "application/json"
                            }
                        }
                    );


                if (!response.ok) {

                    throw new Error(
                        "Failed to load sub-counties."
                    );
                }


                const result =
                    await response.json();


                if (!result.success) {

                    throw new Error(
                        result.message ||
                        "Unable to load sub-counties."
                    );
                }


                populateSelect(
                    subCountySelect,
                    result.data,
                    "Select sub-county",
                    selectedValue
                );


            } catch (error) {

                console.error(
                    "Sub-county error:",
                    error
                );


                resetSelect(
                    subCountySelect,
                    "Unable to load sub-counties"
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Load wards
        |--------------------------------------------------------------------------
        */

        async function loadWards(
            subCountyId,
            selectedValue = ""
        ) {

            resetSelect(
                wardSelect,
                "Loading wards..."
            );


            if (!subCountyId) {

                resetSelect(
                    wardSelect,
                    "Select sub-county first"
                );

                return;
            }


            try {

                const response =
                    await fetch(
                        `api/locations/wards.php?sub_county_id=${encodeURIComponent(subCountyId)}`,
                        {
                            method: "GET",

                            headers: {
                                "Accept":
                                    "application/json"
                            }
                        }
                    );


                if (!response.ok) {

                    throw new Error(
                        "Failed to load wards."
                    );
                }


                const result =
                    await response.json();


                if (!result.success) {

                    throw new Error(
                        result.message ||
                        "Unable to load wards."
                    );
                }


                populateSelect(
                    wardSelect,
                    result.data,
                    "Select ward",
                    selectedValue
                );


            } catch (error) {

                console.error(
                    "Ward error:",
                    error
                );


                resetSelect(
                    wardSelect,
                    "Unable to load wards"
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | County changed
        |--------------------------------------------------------------------------
        */

        countySelect.addEventListener(
            "change",
            () => {

                loadSubCounties(
                    countySelect.value
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Sub-county changed
        |--------------------------------------------------------------------------
        */

        subCountySelect.addEventListener(
            "change",
            () => {

                loadWards(
                    subCountySelect.value
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Restore previous selections
        |--------------------------------------------------------------------------
        */

        if (countySelect.value) {

            loadSubCounties(
                countySelect.value,
                savedSubCounty
            ).then(
                () => {

                    if (
                        subCountySelect.value
                    ) {

                        loadWards(
                            subCountySelect.value,
                            savedWard
                        );
                    }

                }
            );
        }

    }
);