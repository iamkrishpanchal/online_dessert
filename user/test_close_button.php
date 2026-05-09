<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Notification Close Button Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        .test-section {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .test-section h2 {
            margin-top: 0;
            color: #333;
        }
        .notification {
            background: white;
            border: 1px solid #ddd;
            border-left: 4px solid #27ae60;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notification.cancel {
            border-left-color: #e63946;
        }
        .close-btn {
            cursor: pointer;
            color: #e63946;
            font-size: 24px;
            font-weight: bold;
            background: none;
            border: none;
            padding: 0 10px;
            line-height: 1;
        }
        .close-btn:hover {
            transform: scale(1.3);
            transition: transform 0.2s ease;
        }
        .info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 4px;
            padding: 12px;
            color: #0056b3;
            margin: 10px 0;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            padding: 12px;
            color: #155724;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 12px;
            color: #721c24;
            margin: 10px 0;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <h1>🧪 Notification Close Button Test</h1>
    
    <div class="test-section">
        <h2>Test 1: Manual Click</h2>
        <p>Click the ✕ button to dismiss this notification:</p>
        <div class="notification" id="test1">
            <div>
                <strong>✓ Order Completed</strong><br>
                <small>Your order has been delivered</small>
            </div>
            <button class="close-btn" onclick="removeNotification('test1')">✕</button>
        </div>
    </div>
    
    <div class="test-section">
        <h2>Test 2: Cancelled Order</h2>
        <p>Click the ✕ button on this cancelled order notification:</p>
        <div class="notification cancel" id="test2">
            <div>
                <strong>✗ Order Cancelled</strong><br>
                <small>Your order has been cancelled</small>
            </div>
            <button class="close-btn" onclick="removeNotification('test2')">✕</button>
        </div>
    </div>
    
    <div class="test-section">
        <h2>Browser Console Test</h2>
        <p>Open your browser's Developer Tools (F12) and run these commands in the Console tab:</p>
        <pre style="background: #333; color: #0f0; padding: 10px; border-radius: 4px; overflow-x: auto;">
// Test if close button events work
var testBtn = document.querySelector('[data-dismiss-id]');
if (testBtn) {
    console.log('✓ Close button found:', testBtn);
} else {
    console.log('✗ Close button NOT found');
}

// Check event listener
console.log('Click any notification close button and check for console logs');
        </pre>
    </div>
    
    <div class="test-section">
        <h2>Expected Behavior</h2>
        <div class="success">
            ✓ Clicking the ✕ button removes the notification<br>
            ✓ Button has hover effect (scales up)<br>
            ✓ Button is clickable even inside the notification box<br>
            ✓ Notification disappears smoothly
        </div>
    </div>
    
    <div class="test-section">
        <h2>Troubleshooting</h2>
        <div class="info">
            <strong>If close button doesn't work:</strong><br>
            1. Open browser console (F12)<br>
            2. Check for JavaScript errors (red messages)<br>
            3. Try clicking the button again and watch console<br>
            4. Take screenshot of any error messages
        </div>
    </div>
    
    <div class="test-section">
        <h2>Return to App</h2>
        <button onclick="window.location.href='index.php'">← Back to Shop</button>
    </div>

    <script>
        function removeNotification(id) {
            console.log('Removing notification:', id);
            var element = document.getElementById(id);
            if (element) {
                element.style.opacity = '0.5';
                element.style.textDecoration = 'line-through';
                setTimeout(function() {
                    element.remove();
                    console.log('✓ Notification removed successfully');
                }, 300);
            }
        }
        
        // Log when page loads
        console.log('Notification Close Button Test Page Loaded');
        console.log('Try clicking the ✕ buttons on notifications');
    </script>
</body>
</html>
