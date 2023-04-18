<!DOCTYPE html>
<html lang="en">

<head>
    <title>Dashboard</title>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Dewsense Dashboard Page">
    <link rel="icon" type="image/x-icon" href="/resources/favicon.ico" />
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="https://api.tiles.mapbox.com/mapbox-gl-js/v2.13.0/mapbox-gl.css" />
    <script src="https://api.tiles.mapbox.com/mapbox-gl-js/v2.13.0/mapbox-gl.js"></script>
    <style>
        #dashboard-page {
            position: relative;
            display: flex;
            flex: 1;
        }

        #controls-menu {
            display: flex;
            flex-wrap: wrap;
            flex-direction: row;
            gap: 10px;
            height: fit-content;
            margin: 0 20px;
            padding: 10px;
            border-radius: 25px;
            background-color: #101010;
            position: absolute;
            bottom: 20px;
        }

        .controls-menu-control-button {
            background-color: #06f;
            outline: unset;
            border: unset;
            height: 30px;
            padding: 0 15px;
            border-radius: 15px;
            color: #fff;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 0.8px;
            cursor: pointer;
            white-space: nowrap;
            -webkit-tap-highlight-color: transparent;
        }

        .controls-menu-control-button:hover {
            background-color: #05f;
        }

        .controls-menu-control-button:active {
            background-color: #fff;
            color: #06f;
        }

        #controls-menu-auto-update-button.active {
            background-color: rgb(0, 255, 208);
            color: #fff;
        }

        #controls-menu-date-button {
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #06f;
            border: unset;
            border-radius: 14px;
            width: fit-content;
            height: 30px;
            color: #fff;
            padding: 0 15px;
            text-decoration: #fff;
            outline: unset;
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 0.8px;
            white-space: nowrap;
            -webkit-tap-highlight-color: transparent;
        }

        #humidity-range {
            position: absolute;
            bottom: 20px;
            right: 20px;
            width: 350px;
            z-index: 1000;
            display: none;
            pointer-events: none;
            user-select: none;
        }

        @media only screen and (min-width: 860px) and (min-height:200px) {
            #humidity-range {
                display: block;
            }
        }

        #map {
            height: 100%;
            width: 100%;
        }

        .marker {
            height: 12px;
            width: 12px;
            border-radius: 6px;
            background-color: rgb(0, 255, 208);
            border: 1px solid #fff;
        }

        .mapboxgl-ctrl-logo {
            display: none !important;
        }

        .mapboxgl-ctrl-bottom-right {
            display: none !important;
        }

        ::-webkit-calendar-picker-indicator {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="15" viewBox="0 0 24 24"><path fill="%23bbbbbb" d="M20 3h-1V1h-2v2H7V1H5v2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 18H4V8h16v13z"/></svg>');
        }
    </style>
</head>

<body>
    <?php \Core\Render::component("/header"); ?>
    <div id="dashboard-page" class="page-content">
        <div id="map"></div>
        <div id="controls-menu">
            <button id="controls-menu-locate-position-button" class="controls-menu-control-button">Locate</button>
            <button id="controls-menu-fly-to-nicosia-button" class="controls-menu-control-button">Nicosia</button>
            <input id="controls-menu-date-button" type="date" name="date">
            <button id="controls-menu-auto-update-button" class="controls-menu-control-button">Auto</button>
        </div>
        <img id="humidity-range" src="/resources/dewsense-range.png" alt="humidity-range">
    </div>
    <?php \Core\Render::component("/footer"); ?>
    <script>
        // Mapbox API key
        mapboxgl.accessToken = ``

        let data = null
        let popup = null;
        let autoIntervalId = null
        let marker = null;

        const nicosiaCoordinates = [33.359180, 35.152764]
        const sourceIdToRemove = `humidityData`
        const cooldownThreshold = 50_000

        const markerElement = document.createElement(`div`)
        const locateButton = document.getElementById(`controls-menu-locate-position-button`)
        const nicosiaButton = document.getElementById(`controls-menu-fly-to-nicosia-button`)
        const dateButton = document.getElementById(`controls-menu-date-button`)
        const autoButton = document.getElementById(`controls-menu-auto-update-button`)

        const map = new mapboxgl.Map({
            container: `map`,
            style: `mapbox://styles/mapbox/dark-v11`,
            center: nicosiaCoordinates,
            zoom: 12,
            attributionControl: false
        })

        // Mapbox constraints
        map.dragRotate.disable()
        map.touchZoomRotate.disableRotation()
        map.setMinZoom(10)
        map.setMaxZoom(19)

        const generateHeatmap = (data) => {
            map.addSource('humidityData', {
                'type': 'geojson',
                'data': {
                    'type': 'FeatureCollection',
                    'features': data.map((item) => ({
                        'type': 'Feature',
                        'geometry': {
                            'type': 'Point',
                            'coordinates': [item.longitude, item.latitude]
                        },
                        'properties': {
                            'humidity': item.humidity
                        }
                    }))
                }
            })

            map.addLayer({
                    id: 'humidity-heat',
                    type: 'heatmap',
                    source: 'humidityData',
                    maxzoom: 15,
                    paint: {
                        // Increase weight as diameter breast height increases
                        'heatmap-weight': {
                            property: 'humidity',
                            type: 'exponential',
                            stops: [
                                [1, 0],
                                [62, 1]
                            ]
                        },
                        // Increase intensity as zoom level increases
                        'heatmap-intensity': {
                            stops: [
                                [11, 1],
                                [15, 3]
                            ]
                        },
                        // Assign color values be applied to points depending on their density
                        'heatmap-color': [
                            'interpolate',
                            ['linear'],
                            ['heatmap-density'],
                            0,
                            'rgba(255,255,255,0)',
                            0.2,
                            'rgb(191,217,255)',
                            0.4,
                            'rgb(128,178,255)',
                            0.6,
                            'rgb(64,140,255)',
                            0.8,
                            'rgb(0,102,255)'
                        ],
                        // Increase radius as zoom increases
                        'heatmap-radius': {
                            stops: [
                                [11, 15],
                                [15, 20]
                            ]
                        },
                        // Decrease opacity to transition into the circle layer
                        'heatmap-opacity': {
                            default: 1,
                            stops: [
                                [14, 1],
                                [15, 0]
                            ]
                        }
                    }
                },
                'waterway-label'
            )

            map.addLayer({
                    id: 'humidity-point',
                    type: 'circle',
                    source: 'humidityData',
                    minzoom: 14,
                    paint: {
                        // Increase the radius of the circle as the zoom level and humidity value increases
                        'circle-radius': {
                            property: 'humidity',
                            type: 'exponential',
                            stops: [
                                [{
                                    zoom: 15,
                                    value: 1
                                }, 5],
                                [{
                                    zoom: 15,
                                    value: 62
                                }, 10],
                                [{
                                    zoom: 22,
                                    value: 1
                                }, 20],
                                [{
                                    zoom: 22,
                                    value: 62
                                }, 50]
                            ]
                        },
                        'circle-color': {
                            property: 'humidity',
                            type: 'exponential',
                            stops: [
                                [0, 'rgb(255, 255, 255)'],
                                [10, 'rgb(229, 242, 255)'],
                                [20, 'rgb(204, 229, 255)'],
                                [30, 'rgb(153, 204, 255)'],
                                [40, 'rgb(102, 153, 255)'],
                                [50, 'rgb(51, 102, 255)'],
                                [60, 'rgb(0, 51, 255)']
                            ]
                        },
                        'circle-stroke-color': 'white',
                        'circle-stroke-width': 1,
                        'circle-opacity': {
                            stops: [
                                [14, 0],
                                [15, 1]
                            ]
                        }
                    }
                },
                'waterway-label'
            )
        }

        // Get the data for a given date
        const fetchData = async (date) => {
            let apiURL = `/api/get-humidity`

            if (date !== undefined) {
                apiURL += `?date=${date}`
            }

            const response = await fetch(apiURL)
            const data = await response.json()

            return data
        }

        // Get the latest map
        const fetchLatestMap = async () => {
            map.getStyle().layers.forEach((layer) => {
                if (layer.source === sourceIdToRemove) {
                    map.removeLayer(layer.id)
                }
            })

            // Remove popup if active
            if (popup) {
                popup.remove();
            }

            // Remove the source
            if (map.getSource(sourceIdToRemove)) {
                map.removeSource(sourceIdToRemove)
            }

            data = await fetchData(dateButton.value)
            generateHeatmap(data)
        };

        // Once the map loads, get the data and render it
        map.on('load', async () => {
            const date = new Date().toISOString().slice(0, 10)
            data = await fetchData(date);
            generateHeatmap(data);
        });

        // Once the DOM loads set current date
        document.addEventListener(`DOMContentLoaded`, async (event) => {
            const date = new Date().toISOString().slice(0, 10)
            document.getElementById(`controls-menu-date-button`).value = date
        })

        // Show humidity at point when clicked
        map.on(`click`, `humidity-point`, (event) => {
            popup = new mapboxgl.Popup()
                .setLngLat(event.features[0].geometry.coordinates)
                .setHTML(`<strong>RH: </strong> ${Math.round(event.features[0].properties.humidity)}%`)
                .addTo(map)
        })

        // Move to your current position
        locateButton.addEventListener(`click`, () => {
            markerElement.className = `marker`

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((position) => {
                    const latitude = position.coords.latitude
                    const longitude = position.coords.longitude

                    // Remove the existing marker if it exists
                    if (marker) {
                        marker.remove();
                    }

                    // Add a marker at the specified coordinates
                    marker = new mapboxgl.Marker({
                            element: markerElement
                        })
                        .setLngLat([longitude, latitude])
                        .addTo(map)

                    // Fly to the marker location
                    map.flyTo({
                        center: [longitude, latitude],
                        essential: true,
                        zoom: 14
                    })
                })
            }
        })

        // Fly to Nicosia location
        nicosiaButton.addEventListener(`click`, () => {
            map.flyTo({
                center: nicosiaCoordinates,
                essential: true,
                zoom: 12
            })
        })

        // Update points in map by selected date
        dateButton.addEventListener(`change`, async (event) => {
            if (autoButton.classList.contains(`active`)) {
                autoButton.classList.toggle(`active`)
                clearInterval(autoIntervalId)
            }

            fetchLatestMap()
        })

        // Check for latest points
        autoButton.addEventListener(`click`, () => {
            const active = autoButton.classList.contains(`active`)
            const date = new Date().toISOString().slice(0, 10)

            if (!active) {
                if (dateButton.value !== date) {
                    dateButton.value = date
                }

                autoButton.classList.toggle(`active`)
                fetchLatestMap()
                autoIntervalId = setInterval(() => fetchLatestMap(), cooldownThreshold);
            } else {
                autoButton.classList.toggle(`active`)
                clearInterval(autoIntervalId);
                autoIntervalId = null;
            }
        })

        // Prevent right click
        document.addEventListener(`contextmenu`, (event) => event.preventDefault())
    </script>
</body>

</html>