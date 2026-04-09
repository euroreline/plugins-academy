(function () {

"use strict";


/*
====================================================
GLOBAL STATE
====================================================
*/

var pmpMaps = [];
var googleMapsLoading = false;
var googleMapsLoaded = false;


/*
====================================================
LOAD GOOGLE MAPS API
====================================================
*/

function loadGoogleMaps(callback) {

	if (googleMapsLoaded && window.google && window.google.maps) {
		callback();
		return;
	}

	if (googleMapsLoading) {
		var waitInterval = setInterval(function () {
			if (window.google && window.google.maps) {
				clearInterval(waitInterval);
				googleMapsLoaded = true;
				callback();
			}
		}, 200);
		return;
	}

	if (!window.pmpData || !window.pmpData.apiKey) {
		console.warn("Missing Google Maps API key");
		return;
	}

	googleMapsLoading = true;

	window.pmpInitGoogleMaps = function () {
		googleMapsLoaded = true;
		callback();
	};

	var script = document.createElement("script");
	script.src =
		"https://maps.googleapis.com/maps/api/js?key=" +
		encodeURIComponent(window.pmpData.apiKey) +
		"&callback=pmpInitGoogleMaps";
	script.async = true;
	script.defer = true;

	document.head.appendChild(script);
}


/*
====================================================
TEXT NORMALIZATION
====================================================
*/

function normalizeText(value) {

	if (value === null || value === undefined) {
		return "";
	}

	var text = String(value).toLowerCase().trim();

	if (typeof text.normalize === "function") {
		text = text.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
	}

	text = text.replace(/\s+/g, " ");

	return text;
}


/*
====================================================
ESCAPE HTML
====================================================
*/

function escapeHtml(str) {

	if (typeof str !== "string") return "";

	return str
		.replace(/&/g,"&amp;")
		.replace(/</g,"&lt;")
		.replace(/>/g,"&gt;")
		.replace(/"/g,"&quot;")
		.replace(/'/g,"&#039;");
}


/*
====================================================
INFOWINDOW TEMPLATE
====================================================
*/

function buildInfoWindowContent(partner) {

	var html = '<div class="pmp-infowindow">';

	html += "<strong>" + escapeHtml(partner.title || "") + "</strong><br>";

	if (partner.address) {
		html += escapeHtml(partner.address) + "<br>";
	}

	if (partner.city) {
		html += "Πόλη: " + escapeHtml(partner.city) + "<br>";
	}

	if (partner.postal_code) {
		html += "Τ.Κ.: " + escapeHtml(partner.postal_code) + "<br>";
	}

	if (partner.phone) {
		html += "Τηλ: " + escapeHtml(partner.phone) + "<br>";
	}

	if (partner.type_label) {
		html += "Είδος: " + escapeHtml(partner.type_label);
	}

	html += "</div>";

	return html;
}


/*
====================================================
MATCH LIST HEIGHT WITH MAP (SMART VERSION)
====================================================
*/

function matchListHeightWithMap() {

	document.querySelectorAll(".pmp-list-wrap").forEach(function (list) {

		if (window.innerWidth <= 1024) {

			var firstCard = list.querySelector(".pmp-partner-card");

			if (firstCard) {

				var cardHeight = firstCard.offsetHeight;

				list.style.maxHeight = (cardHeight + 30) + "px";
				list.style.overflowY = "auto";

			}

			return;
		}

		list.style.overflow = "";
		list.style.overflowY = "";
	});


	document.querySelectorAll(".pmp-map").forEach(function (map) {

		var parent = map.closest(".elementor-container, .elementor-widget-wrap, .e-con-inner, .e-con, .elementor-column, .elementor");

		if (!parent) return;

		var list = parent.querySelector(".pmp-list-wrap");

		if (!list) return;

		var mapHeight = map.offsetHeight;

		if (mapHeight > 0) {

			list.style.maxHeight = mapHeight + "px";
			list.style.overflowY = "auto";

		}

	});

}


/*
====================================================
INITIALIZE SINGLE MAP
====================================================
*/

function initSingleMap(mapEl) {

	if (!mapEl || mapEl.dataset.pmpReady === "1") return;

	var partners = [];

	try {
		partners = JSON.parse(mapEl.dataset.partners || "[]");
	} catch(e) {
		console.error("Invalid JSON data");
		return;
	}

	var zoom = parseInt(mapEl.dataset.zoom || "6",10);

	var map = new google.maps.Map(mapEl,{
		center:{lat:37.9838,lng:23.7275},
		zoom:zoom
	});

	var geocoder = new google.maps.Geocoder();
	var bounds = new google.maps.LatLngBounds();
	var infoWindow = new google.maps.InfoWindow();
	var markersByPartnerId = {};
	var validMarkerCount = 0;

	partners.forEach(function(partner){

		if(!partner.full_address) return;

		geocoder.geocode({address:partner.full_address},function(results,status){

			if(status==="OK" && results[0]){

				var marker = new google.maps.Marker({
					map:map,
					position:results[0].geometry.location,
					title:partner.title || "",
					icon:partner.marker_icon || null
				});

				markersByPartnerId[String(partner.id)] = marker;
				bounds.extend(results[0].geometry.location);
				validMarkerCount++;

				marker.addListener("click",function(){
					infoWindow.setContent(buildInfoWindowContent(partner));
					infoWindow.open(map,marker);
				});

				if (validMarkerCount === 1) {
					map.setCenter(results[0].geometry.location);
					map.setZoom(12);
				} else {
					map.fitBounds(bounds);
				}
			}

			matchListHeightWithMap();
		});
	});

	mapEl.dataset.pmpReady="1";

	pmpMaps.push({
		element:mapEl,
		map:map,
		markersByPartnerId:markersByPartnerId,
		infoWindow:infoWindow
	});

	setTimeout(matchListHeightWithMap,500);
	setTimeout(matchListHeightWithMap,1000);
}


/*
====================================================
INIT ALL MAPS
====================================================
*/

function initAllMaps(){
	document.querySelectorAll(".pmp-map").forEach(function(map){
		initSingleMap(map);
	});
}


/*
====================================================
FOCUS MARKER FROM LIST
====================================================
*/

function focusPartnerOnAnyMap(partnerId){

	pmpMaps.forEach(function(entry){

		var marker = entry.markersByPartnerId[String(partnerId)];

		if(!marker) return;

		entry.map.setCenter(marker.getPosition());
		entry.map.setZoom(14);

		google.maps.event.trigger(marker,"click");

		entry.element.scrollIntoView({
			behavior:"smooth",
			block:"center"
		});
	});
}


/*
====================================================
SEARCH FILTER (UPDATED)
====================================================
*/

function initPartnerListSearch(){

	var inputs = document.querySelectorAll('[data-pmp-search-input="1"]');
	var cards = document.querySelectorAll('[data-pmp-partner-card="1"]');
	var emptyState = document.querySelector('[data-pmp-empty-state="1"]');

	if(!inputs.length || !cards.length) return;

	function runSearch(value){

		var normalizedValue = normalizeText(value);

		var visible = 0;

		cards.forEach(function(card){

			var haystack = normalizeText(
				(card.getAttribute("data-search") || "") +
				" " +
				card.textContent
			);

			var match = normalizedValue.length < 3 || haystack.includes(normalizedValue);

			card.style.display = match ? "" : "none";

			if(match) visible++;

		});

		if(emptyState){

			emptyState.classList.toggle("pmp-hidden", visible !== 0);

		}
	}

	inputs.forEach(function(input){

		input.addEventListener("input", function(){

			var value = input.value;

			inputs.forEach(function(syncInput){

				if(syncInput !== input){

					syncInput.value = value;

				}

			});

			runSearch(value);

		});

	});

}


/*
====================================================
FOCUS BUTTON HANDLER
====================================================
*/

function initFocusButtons(){

	document.addEventListener("click",function(e){

		var btn=e.target.closest("[data-pmp-focus-partner]");

		if(!btn) return;

		e.preventDefault();

		focusPartnerOnAnyMap(btn.dataset.pmpFocusPartner);
	});
}


/*
====================================================
INIT SYSTEM
====================================================
*/

function initPmpSystem() {

	initPartnerListSearch();
	initFocusButtons();
	matchListHeightWithMap();

	window.addEventListener("resize", matchListHeightWithMap);

	if(document.querySelector(".pmp-map")){
		loadGoogleMaps(function(){
			initAllMaps();
		});
	}
}

if (document.readyState === "loading") {
	document.addEventListener("DOMContentLoaded", initPmpSystem);
} else {
	initPmpSystem();
}


})();