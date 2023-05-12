/**
 * @file
 * Contains the definition of the OpenAgenda event map behaviour.
 */

$(document).ready(function () {
  // Map.
  let eventMap = () => {
    if (!$('#event-map').length || $('#event-map').hasClass('map-initialized')) {
      return;
    }

    $('#event-map').addClass('map-initialized');

    let map = L.map('event-map').setView([settingsOpenagendaEventLat, settingsOpenagendaEventLon], 12);
    L.tileLayer(settingsOpenagendaMapTilesUrl, {
      minZoom: 5,
      maxZoom: 17
    }).addTo(map);

    // Event marker.
    let icon = L.icon({
      iconUrl: settingsOpenagendamarkerUrl,
      iconSize: [36, 48],
      iconAnchor: [18, 45]
    });

    const marker = L.marker([settingsOpenagendaEventLat, settingsOpenagendaEventLon], {icon: icon}).addTo(map);

    clearInterval(mapInterval);
  }

  let mapInterval = setInterval(eventMap, 500);
});
