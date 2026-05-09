<?php
session_start();
include 'connection.php';
include 'session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rider Locations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- <style>#map { height: 400px; }</n>@media (max-width:768px){ #map{height:300px;} }</style> -->
</head>
<body>
<?php include 'layout.php'; ?>
<div class="container mt-4">
    <h3>Online Rider Locations</h3>
    <div id="map"></div>
    <table class="table table-bordered mt-3">
        <thead><tr><th>Name</th><th>Lat</th><th>Lon</th><th>Online</th></tr></thead>
        <tbody id="locations-body"></tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<!-- you can replace with Google Maps JS key if available -->
<script>
function refresh(){
    $.getJSON('get_rider_locations.php', function(resp){
        if(!resp.success) return;
        var markers=[];
        $('#locations-body').empty();
        resp.riders.forEach(function(r){
            $('#locations-body').append('<tr><td>'+r.name+'</td><td>'+r.latitude+'</td><td>'+r.longitude+'</td><td>'+ (r.is_online? 'Yes':'No') +'</td></tr>');
            if(r.latitude && r.longitude) markers.push({lat:parseFloat(r.latitude),lng:parseFloat(r.longitude),title:r.name});
        });
        if(window.map && markers.length){
            var bounds = new google.maps.LatLngBounds();
            markers.forEach(function(m){
                var pos=new google.maps.LatLng(m.lat,m.lng);
                new google.maps.Marker({position:pos,map:window.map,title:m.title});
                bounds.extend(pos);
            });
            window.map.fitBounds(bounds);
        }
    });
}

function initMap(){
    window.map=new google.maps.Map(document.getElementById('map'),{center:{lat:20,lng:78},zoom:5});
    refresh();
}

$(function(){
    setInterval(refresh,10000);
});
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap"></script>
</body>
</html>