/*
|--------------------------------------------------------------------------
| FIH Device Location
|--------------------------------------------------------------------------
|
| Gets the user's current device location.
|
| The browser will ask the user for permission.
|
|--------------------------------------------------------------------------
*/

function getDeviceLocation() {

    return new Promise(function (resolve, reject) {

        if (!navigator.geolocation) {

            reject(
                new Error(
                    'Location services are not supported by this browser.'
                )
            );

            return;
        }


        navigator.geolocation.getCurrentPosition(

            function (position) {

                resolve({
                    latitude:
                        position.coords.latitude,

                    longitude:
                        position.coords.longitude,

                    accuracy:
                        position.coords.accuracy
                });

            },

            function (error) {

                let message =
                    'Unable to determine your location.';


                switch (error.code) {

                    case error.PERMISSION_DENIED:

                        message =
                            'Location permission was denied.';

                        break;


                    case error.POSITION_UNAVAILABLE:

                        message =
                            'Your location is currently unavailable.';

                        break;


                    case error.TIMEOUT:

                        message =
                            'Location request timed out.';

                        break;

                }


                reject(
                    new Error(message)
                );

            },

            {
                enableHighAccuracy: true,

                timeout: 10000,

                maximumAge: 300000
            }

        );

    });

}