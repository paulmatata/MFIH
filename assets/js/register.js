/**
 * FIH Farmer Registration
 *
 * Handles the visual three-step registration process.
 *
 * Important:
 * JavaScript only controls the user experience.
 * Server-side PHP remains responsible for validation
 * and account creation.
 */

document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById(
        'farmerRegistrationForm'
    );

    if (!form) {
        return;
    }


    const steps = Array.from(
        form.querySelectorAll('.registration-step')
    );

    const nextButtons = form.querySelectorAll(
        '[data-next-step]'
    );

    const previousButtons = form.querySelectorAll(
        '[data-previous-step]'
    );


    let currentStep = 0;


    /**
     * Display a particular registration step.
     */
    function showStep(stepIndex) {

        if (
            stepIndex < 0 ||
            stepIndex >= steps.length
        ) {
            return;
        }


        steps.forEach((step, index) => {

            step.classList.toggle(
                'active',
                index === stepIndex
            );

        });


        currentStep = stepIndex;


        /*
        |--------------------------------------------------------------------------
        | Scroll back to top
        |--------------------------------------------------------------------------
        */

        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });


        /*
        |--------------------------------------------------------------------------
        | Focus first usable field
        |--------------------------------------------------------------------------
        */

        const firstField = steps[
            currentStep
        ].querySelector(
            'input:not([type="hidden"]), select'
        );

        if (firstField) {

            setTimeout(() => {
                firstField.focus();
            }, 250);

        }
    }


    /**
     * Validate visible fields before moving forward.
     */
    function validateCurrentStep() {

        const currentSection = steps[
            currentStep
        ];

        const fields = currentSection.querySelectorAll(
            'input, select, textarea'
        );


        for (const field of fields) {

            /*
            |--------------------------------------------------------------------------
            | Ignore disabled fields
            |--------------------------------------------------------------------------
            */

            if (field.disabled) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Ignore hidden fields
            |--------------------------------------------------------------------------
            */

            if (
                field.offsetParent === null
            ) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Browser validation
            |--------------------------------------------------------------------------
            */

            if (!field.checkValidity()) {

                field.reportValidity();

                return false;
            }
        }


        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | Continue buttons
    |--------------------------------------------------------------------------
    */

    nextButtons.forEach(button => {

        button.addEventListener(
            'click',
            () => {

                if (!validateCurrentStep()) {
                    return;
                }

                showStep(
                    currentStep + 1
                );

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | Back buttons
    |--------------------------------------------------------------------------
    */

    previousButtons.forEach(button => {

        button.addEventListener(
            'click',
            () => {

                showStep(
                    currentStep - 1
                );

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | Password confirmation
    |--------------------------------------------------------------------------
    */

    const password = document.getElementById(
        'password'
    );

    const confirmation = document.getElementById(
        'password_confirmation'
    );


    if (password && confirmation) {

        confirmation.addEventListener(
            'input',
            () => {

                if (
                    confirmation.value !==
                    password.value
                ) {

                    confirmation.setCustomValidity(
                        'Passwords do not match.'
                    );

                } else {

                    confirmation.setCustomValidity(
                        ''
                    );

                }

            }
        );

    }

    /*
|--------------------------------------------------------------------------
| Location cascading
|--------------------------------------------------------------------------
*/

const countySelect = document.getElementById(
    'county_id'
);

const subCountySelect = document.getElementById(
    'sub_county_id'
);

const wardSelect = document.getElementById(
    'ward_id'
);


/*
|--------------------------------------------------------------------------
| Helper: reset select
|--------------------------------------------------------------------------
*/

function resetSelect(select, placeholder) {

    select.innerHTML = '';

    const option = document.createElement(
        'option'
    );

    option.value = '';
    option.textContent = placeholder;

    select.appendChild(option);

    select.disabled = true;
}


/*
|--------------------------------------------------------------------------
| Helper: populate select
|--------------------------------------------------------------------------
*/

function populateSelect(
    select,
    items,
    placeholder
) {

    select.innerHTML = '';

    const firstOption =
        document.createElement('option');

    firstOption.value = '';
    firstOption.textContent = placeholder;

    select.appendChild(firstOption);


    items.forEach(item => {

        const option =
            document.createElement('option');

        option.value = item.id;

        option.textContent = item.name;

        select.appendChild(option);

    });


    select.disabled = false;
}


/*
|--------------------------------------------------------------------------
| County → Sub-county
|--------------------------------------------------------------------------
*/

if (countySelect) {

    countySelect.addEventListener(
        'change',
        async () => {

            const countyId =
                countySelect.value;


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

                const response =
                    await fetch(
                        `${FIH_BASE_URL}/api/locations/sub_counties.php?county_id=${encodeURIComponent(countyId)}`
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


                populateSelect(
                    subCountySelect,
                    result.data,
                    'Select sub-county'
                );

            } catch (error) {

                console.error(error);

                resetSelect(
                    subCountySelect,
                    'Unable to load sub-counties'
                );

                resetSelect(
                    wardSelect,
                    'Select ward'
                );

            }

        }
    );

}


/*
|--------------------------------------------------------------------------
| Sub-county → Ward
|--------------------------------------------------------------------------
*/

if (subCountySelect) {

    subCountySelect.addEventListener(
        'change',
        async () => {

            const subCountyId =
                subCountySelect.value;


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

                const response =
                    await fetch(
                        `${FIH_BASE_URL}/api/locations/wards.php?sub_county_id=${encodeURIComponent(subCountyId)}`
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


                populateSelect(
                    wardSelect,
                    result.data,
                    'Select ward'
                );

            } catch (error) {

                console.error(error);

                resetSelect(
                    wardSelect,
                    'Unable to load wards'
                );

            }

        }
    );

}

/*start from step 1*/
    showStep(0);

});