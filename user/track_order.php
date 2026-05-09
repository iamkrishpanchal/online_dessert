<?php
session_start();
include 'connection.php';
$order_id = intval($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    echo "Invalid order";
    exit;
}
// verify the order belongs to the logged in user if needed
if (!empty($_SESSION['user_id'])) {
    $stmt = mysqli_prepare($conn, "SELECT order_id FROM tbl_orders WHERE order_id=? AND user_id=?");
    mysqli_stmt_bind_param($stmt,'ii',$order_id,$_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) === 0) {
        echo "Unauthorized";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Track Order</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background: #f8f9fa; }
  #map { position: relative; height: 420px; border: 1px solid #e1e4e8; border-radius: .75rem; overflow: hidden; }
  #map-overlay { display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.9); z-index: 1000; }
  #map-overlay-text { color: #343a40; font-weight: 600; font-size: 1rem; }
  @media (max-width: 576px) {
    #map { height: 260px; }
  }
  .status-badge { font-size: 0.85rem; }
</style>
</head>
<body>
<?php include 'header.php'; ?>
<main class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1">Track Order</h4>
      <div class="text-muted">Order #<?php echo htmlspecialchars($order_id); ?></div>
    </div>
    <a href="/" class="btn btn-outline-secondary btn-sm">Back to home</a>
  </div>
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><strong>Live Map</strong></span>
          <span id="status-badge" class="badge bg-secondary status-badge">Loading…</span>
        </div>
        <div class="card-body p-0">
          <div id="map"></div>
          <div id="map-overlay" class="d-flex align-items-center justify-content-center">
            <span id="map-overlay-text"></span>
          </div>
        </div>
        <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
          <div>
            <div class="text-muted" style="font-size:.85rem;">Rider position:</div>
            <div id="rider-coords" class="fw-medium">(waiting for rider)</div>
          </div>
          <a id="open-in-maps" class="btn btn-primary btn-sm disabled" target="_blank" rel="noopener" href="#">
            Open in Google Maps
          </a>
        </div>
      </div>
      <div class="card shadow-sm mt-4">
        <div class="card-header"><strong>Tracking history</strong></div>
        <ul class="list-group list-group-flush" id="track-list"></ul>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-header"><strong>Order details</strong></div>
        <div class="card-body">
          <p class="mb-2">
            <small class="text-muted">Destination</small><br>
            <span id="dest-address" class="d-block text-body"></span>
          </p>
          <p class="mb-2">
            <small class="text-muted">Vendor</small><br>
            <span id="vendor-address" class="d-block text-body"></span>
          </p>
          <p class="mb-0">
            <small class="text-muted">Rider</small><br>
            <span id="rider-id" class="d-block text-body">Not assigned</span>
          </p>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
const orderId = <?php echo $order_id; ?>;
const GOOGLE_MAPS_API_KEY = 'YOUR_GOOGLE_MAPS_API_KEY_HERE';
const DEFAULT_MAP_CENTER = { lat: 20, lng: 78 };

window.onerror = function(message, source, line, col, error) {
  console.error('TrackOrder JS error', message, source, line, col, error);
  const list = document.getElementById('track-list');
  if (list) {
    const li = document.createElement('li');
    li.className = 'list-group-item list-group-item-danger';
    li.textContent = `JS Error: ${message} (${source}:${line}:${col})`;
    list.insertBefore(li, list.firstChild);
  }
};

let destinationLatLng = null;
let destinationAddress = '';
let vendorLatLng = null;
let vendorAddress = '';
let openInMapsLink = null;
let statusBadge = null;
let destAddressEl = null;
let vendorAddressEl = null;
let riderIdEl = null;
let riderCoordsEl = null;

function showMapMessage(message) {
  const overlay = document.getElementById('map-overlay');
  const overlayText = document.getElementById('map-overlay-text');
  if (!overlay || !overlayText) return;

  if (!message) {
    overlay.style.display = 'none';
    overlayText.textContent = '';
    return;
  }

  overlayText.textContent = message;
  overlay.style.display = 'flex';
}

function renderIframeMap(lat, lng) {
  const mapEl = document.getElementById('map');
  if (!mapEl) return;

  // Use a small bbox around the point (approx ~1km)
  const delta = 0.02;
  const bbox = [
    lng - delta,
    lat - delta,
    lng + delta,
    lat + delta
  ].join(',');

  const src = `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat},${lng}`;

  mapEl.innerHTML = `<iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="${src}"></iframe>`;
}

function initMap(center = DEFAULT_MAP_CENTER) {
  // Create a basic view so the user sees something immediately
  renderIframeMap(center.lat, center.lng);
}

function setStatusBadge(status) {
  if (!statusBadge) {
    statusBadge = document.getElementById('status-badge');
  }
  if (!statusBadge) return;

  const normalized = (status || '').toString().trim();
  const lower = normalized.toLowerCase();

  let colorClass = 'bg-secondary';
  if (lower.includes('delivered')) colorClass = 'bg-success';
  else if (lower.includes('out for') || lower.includes('en route') || lower.includes('on the way') || lower.includes('picked')) colorClass = 'bg-info';
  else if (lower.includes('assigned') || lower.includes('accepted')) colorClass = 'bg-primary';
  else if (lower.includes('cancel') || lower.includes('failed') || lower.includes('error')) colorClass = 'bg-danger';

  statusBadge.className = `badge ${colorClass} status-badge`;
  statusBadge.textContent = normalized || 'Waiting for status…';
}

function setOrderDetails({ riderId, vendorAddress: vendorAddr, destinationAddress: destAddr }) {
  if (!destAddressEl) destAddressEl = document.getElementById('dest-address');
  if (!vendorAddressEl) vendorAddressEl = document.getElementById('vendor-address');
  if (!riderIdEl) riderIdEl = document.getElementById('rider-id');

  if (destAddressEl) destAddressEl.textContent = destAddr || '—';
  if (vendorAddressEl) vendorAddressEl.textContent = vendorAddr || '—';
  if (riderIdEl) riderIdEl.textContent = riderId ? `#${riderId}` : 'Not assigned';
}

// No route drawing is supported in iframe mode. This function is kept for compatibility.
function updateRoute(riderLatLng, destLatLng) {
  // No-op
}

function formatLogLine(line) {
  return `<li class="list-group-item d-flex justify-content-between align-items-start">
            <div>
              <div class="fw-semibold">${line.status}</div>
              <div class="text-muted" style="font-size:0.85rem;">${line.created_at}${line.message ? ` · ${line.message}` : ''}</div>
            </div>
          </li>`;
}

function buildGoogleMapsUrl(riderPos, destPos, destAddress) {
  if (!riderPos) return null;

  const origin = `${riderPos.lat},${riderPos.lng}`;
  let destination;

  if (destPos) {
    destination = `${destPos.lat},${destPos.lng}`;
  } else if (destAddress) {
    destination = encodeURIComponent(destAddress);
  }

  if (!destination) return null;

  return `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${destination}&travelmode=driving`;
}

function setOpenInMapsLink(url) {
  if (!openInMapsLink) {
    openInMapsLink = document.getElementById('open-in-maps');
  }
  if (!openInMapsLink) return;

  if (!url) {
    openInMapsLink.href = 'javascript:void(0)';
    openInMapsLink.classList.add('disabled');
    openInMapsLink.setAttribute('aria-disabled', 'true');
    openInMapsLink.textContent = 'Waiting for rider location...';
    return;
  }

  openInMapsLink.href = url;
  openInMapsLink.classList.remove('disabled');
  openInMapsLink.removeAttribute('aria-disabled');
  openInMapsLink.textContent = 'Open in Google Maps';
}

function fetchTrackingLogs() {
  fetch(`order_tracking_logs.php?order_id=${orderId}`)
    .then(r => r.json())
    .then(resp => {
      if (!resp.success) return;
      const logs = resp.logs || [];
      // Show most recent logs at the top
      const html = logs.slice().reverse().map(formatLogLine).join('');
      document.getElementById('track-list').innerHTML = html;

      if (logs.length) {
        const latest = logs[logs.length - 1];
        setStatusBadge(latest.status || 'Updating…');
      }
    })
    .catch(err => {
      console.error('Tracking log fetch error', err);
      setStatusBadge('Error');
    });
}

function fetchRiderLocation() {
  fetch(`rider_location.php?order_id=${orderId}`, { cache: 'no-store' })
    .then(r => r.json())
    .then(resp => {
      if (!resp.success) {
        const msg = resp.message || 'Unable to fetch rider location';
        console.warn('rider_location response:', msg);
        showMapMessage(msg);
        setStatusBadge('Unavailable');
        return;
      }

      destinationAddress = resp.destination?.address || '';
      vendorAddress = resp.vendor?.address || '';
      const riderId = resp.rider_id;
      const riderPos = resp.rider && resp.rider.lat != null && resp.rider.lng != null
        ? { lat: parseFloat(resp.rider.lat), lng: parseFloat(resp.rider.lng) }
        : null;

      setOrderDetails({ riderId, vendorAddress, destinationAddress });

      if (!riderId) {
        showMapMessage('No rider is assigned to this order yet.');
        setStatusBadge('Waiting for assignment');
      } else if (!riderPos) {
        showMapMessage('Rider assigned, waiting for location update...');
        setStatusBadge('Rider assigned');
      }

      if (riderPos) {
        showMapMessage('');
        updateRider(riderPos);

        if (!riderCoordsEl) riderCoordsEl = document.getElementById('rider-coords');
        if (riderCoordsEl) {
          riderCoordsEl.textContent = `${riderPos.lat.toFixed(6)}, ${riderPos.lng.toFixed(6)}`;
        }
      }

      const mapsUrl = buildGoogleMapsUrl(riderPos, null, destinationAddress);
      setOpenInMapsLink(mapsUrl);
    })
    .catch(err => {
      console.error('Rider location fetch error', err);
      showMapMessage('Error fetching rider location');
      setStatusBadge('Error');
    });
}

function updateRider(latLng) {
  // Always render an embedded OpenStreetMap iframe centered on the rider.
  renderIframeMap(latLng.lat, latLng.lng);
}

function loadAll() {
  fetchTrackingLogs();
  fetchRiderLocation();
}

function init() {
  // Cache common elements
  openInMapsLink = document.getElementById('open-in-maps');
  statusBadge = document.getElementById('status-badge');
  riderCoordsEl = document.getElementById('rider-coords');

  // Initialize map; will use default center if no location exists yet
  initMap();

  loadAll();
  setInterval(loadAll, 5000);
}

window.init = init;

// Start tracker
init();
</script>
</body>
</html>