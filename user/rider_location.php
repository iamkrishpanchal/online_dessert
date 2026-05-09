<?php
session_start();
include 'connection.php';

// Allow a quick (browser-friendly) UI for manual testing. Use ?order_id=123&ui=1
$isUi = isset($_GET['ui']) && ($_GET['ui'] === '1' || strtolower($_GET['ui']) === 'html');

if (!$isUi) {
    // Ensure the endpoint always returns valid JSON (avoids response parsing issues in JS)
    ob_start();

    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Internal server error']);
        exit;
    });

    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
            exit;
        }
    });
}

function sendJson($data) {
    global $isUi;
    if (!$isUi) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
    }
    echo json_encode($data);
    exit;
}

$order_id = intval($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    if ($isUi) {
        echo "<p>Invalid order_id provided. Use ?order_id=&lt;id&gt;&amp;ui=1</p>";
        exit;
    }
    sendJson(['success' => false, 'message' => 'Invalid order_id']);
}

// Ensure user can access this order
$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id) {
    $stmt = mysqli_prepare($conn, "SELECT order_id FROM tbl_orders WHERE order_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $order_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) === 0) {
        mysqli_stmt_close($stmt);
        if ($isUi) {
            echo "<p>Unauthorized to view this order.</p>";
            exit;
        }
        sendJson(['success' => false, 'message' => 'Unauthorized']);
    }
    mysqli_stmt_close($stmt);
}

if ($isUi) {
    // Simple UI to preview rider location in a browser (useful for debugging).
    // This UI calls the same endpoint without &ui=1 to get JSON.
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Rider Location (Order #<?php echo htmlspecialchars($order_id); ?>)</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      <style>
        body { background:#f4f6f8; }
        .card { max-width:900px; margin: 32px auto; }
        #map { height: 360px; border-radius: 0.75rem; overflow: hidden; border: 1px solid #e1e4e8; }
        #map-overlay { display:none; position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.82);z-index:10;align-items:center;justify-content:center; }
        #map-overlay span { font-weight:600; color:#303030; }
        #log-list { max-height: 260px; overflow:auto; }
      </style>
    </head>
    <body>
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">Rider Location</h5>
            <small class="text-muted">Order #<?php echo htmlspecialchars($order_id); ?></small>
          </div>
          <a class="btn btn-sm btn-outline-secondary" href="?order_id=<?php echo intval($order_id); ?>&ui=1">Refresh</a>
        </div>
        <div class="card-body">
          <div class="row g-4">
            <div class="col-lg-7">
              <div class="position-relative"> 
                <div id="map"></div>
                <div id="map-overlay" class="d-flex"><span id="map-overlay-text"></span></div>
              </div>
              <div class="mt-3 d-flex justify-content-between align-items-center">
                <div>
                  <div class="text-muted" style="font-size:.85rem;">Rider position</div>
                  <div id="coords" class="fw-semibold">Waiting for update...</div>
                </div>
                <a id="open-maps" class="btn btn-sm btn-primary disabled" target="_blank" rel="noopener" href="#">Open in Maps</a>
              </div>
            </div>
            <div class="col-lg-5">
              <div class="mb-3">
                <h6 class="mb-1">Order status</h6>
                <div id="status" class="badge bg-secondary">Loading…</div>
              </div>
              <div class="mb-3">
                <h6 class="mb-1">Vendor</h6>
                <div id="vendor" class="text-muted">—</div>
              </div>
              <div class="mb-3">
                <h6 class="mb-1">Delivery address</h6>
                <div id="destination" class="text-muted">—</div>
              </div>
              <div>
                <h6 class="mb-1">Tracking log</h6>
                <ul id="log-list" class="list-group"></ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <script>
        const orderId = <?php echo intval($order_id); ?>;
        const DEFAULT_CENTER = { lat: 20, lng: 78 };

        function setMapMessage(msg) {
          const overlay = document.getElementById('map-overlay');
          const text = document.getElementById('map-overlay-text');
          if (!msg) {
            overlay.style.display = 'none';
            text.textContent = '';
            return;
          }
          text.textContent = msg;
          overlay.style.display = 'flex';
        }

        function renderMap(lat, lng) {
          const bboxDelta = 0.02;
          const bbox = [lng - bboxDelta, lat - bboxDelta, lng + bboxDelta, lat + bboxDelta].join(',');
          const src = `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat},${lng}`;
          document.getElementById('map').innerHTML = `<iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="${src}"></iframe>`;
        }

        function setStatus(text) {
          const el = document.getElementById('status');
          if (!el) return;
          el.textContent = text || 'Unknown';
          el.className = 'badge ' + (text && text.toLowerCase().includes('delivered') ? 'bg-success' : 'bg-secondary');
        }

        function setText(id, value) {
          const el = document.getElementById(id);
          if (el) el.textContent = value || '—';
        }

        function setOpenMaps(rider, destination) {
          const btn = document.getElementById('open-maps');
          if (!rider) {
            btn.classList.add('disabled');
            btn.href = '#';
            return;
          }
          const origin = `${rider.lat},${rider.lng}`;
          const dest = destination ? encodeURIComponent(destination) : origin;
          btn.href = `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${dest}&travelmode=driving`;
          btn.classList.remove('disabled');
        }

        function renderLogs(logs) {
          const list = document.getElementById('log-list');
          list.innerHTML = '';
          if (!Array.isArray(logs) || logs.length === 0) {
            list.innerHTML = '<li class="list-group-item text-muted">No logs available</li>';
            return;
          }
          logs.slice().reverse().forEach(log => {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.innerHTML = `<div class="fw-semibold">${log.status || 'Update'}</div><div class="text-muted" style="font-size:.85rem;">${log.created_at || ''}${log.message ? ' · ' + log.message : ''}</div>`;
            list.appendChild(li);
          });
        }

        function load() {
          fetch(`rider_location.php?order_id=${orderId}`)
            .then(r => r.json())
            .then(data => {
              if (!data.success) {
                const err = data.message || 'Unable to load rider location';
                console.error('rider_location error:', err);
                setMapMessage(err);
                setStatus('Unavailable');
                return;
              }

              const riderId = data.rider_id || null;
              const rider = data.rider && data.rider.lat != null && data.rider.lng != null ? { lat: data.rider.lat, lng: data.rider.lng } : null;
              const vendor = data.vendor && data.vendor.address ? data.vendor.address : null;
              const destination = data.destination && data.destination.address ? data.destination.address : null;

              setText('vendor', vendor);
              setText('destination', destination);

              if (!riderId) {
                setStatus('Waiting for assignment');
              } else if (!rider) {
                setStatus('Assigned');
              } else {
                setStatus('En route');
              }

              if (rider) {
                setMapMessage('');
                renderMap(rider.lat, rider.lng);
                document.getElementById('coords').textContent = `${rider.lat.toFixed(6)}, ${rider.lng.toFixed(6)}`;
              } else {
                setMapMessage(riderId ? 'Waiting for rider location...' : 'No rider assigned yet.');
              }

              setOpenMaps(rider, destination);
            })
            .catch(err => {
              console.error('rider_location fetch error', err);
              setMapMessage('Unable to load rider location');
              setStatus('Error');
            });
        }

        window.addEventListener('DOMContentLoaded', () => {
          load();
          setInterval(load, 5000);
        });
      </script>
    </body>
    </html>
    <?php
    exit;
}

// Fetch current rider location and delivery target (including vendor/shop location)
$sql = "SELECT o.rider_id, o.vendor_id, o.delivery_address, o.delivery_city, o.delivery_pincode,
        r.latitude AS rider_lat, r.longitude AS rider_lng,
        v.latitude AS vendor_lat, v.longitude AS vendor_lng, v.address AS vendor_address
        FROM tbl_orders o
        LEFT JOIN tbl_riders r ON o.rider_id = r.rider_id
        LEFT JOIN tbl_vendors v ON o.vendor_id = v.vendor_id
        WHERE o.order_id = ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $order_id);
if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    sendJson(['success' => false, 'message' => 'DB execute error: ' . mysqli_stmt_error($stmt)]);
}

// Use bind_result to avoid relying on mysqlnd / mysqli_stmt_get_result
mysqli_stmt_bind_result(
    $stmt,
    $rider_id,
    $vendor_id,
    $delivery_address,
    $delivery_city,
    $delivery_pincode,
    $rider_lat,
    $rider_lng,
    $vendor_lat,
    $vendor_lng,
    $vendor_address
);

if (!mysqli_stmt_fetch($stmt)) {
    mysqli_stmt_close($stmt);
    sendJson(['success' => false, 'message' => 'Order not found']);
}

mysqli_stmt_close($stmt);

$deliveryAddress = trim(($delivery_address ?? '') . ' ' . ($delivery_city ?? '') . ' ' . ($delivery_pincode ?? ''));

echo json_encode([
    'success' => true,
    'rider_id' => $rider_id !== null ? intval($rider_id) : null,
    'vendor' => [
        'id' => $vendor_id !== null ? intval($vendor_id) : null,
        'lat' => $vendor_lat !== null ? floatval($vendor_lat) : null,
        'lng' => $vendor_lng !== null ? floatval($vendor_lng) : null,
        'address' => trim($vendor_address ?? ''),
    ],
    'rider' => [
        'lat' => $rider_lat !== null ? floatval($rider_lat) : null,
        'lng' => $rider_lng !== null ? floatval($rider_lng) : null,
    ],
    'destination' => [
        'address' => $deliveryAddress,
    ]
]);
