<?php
echo "<!DOCTYPE html><html><head><title>Test Booking System</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; padding: 20px; }
.success { color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #f0f0f8; padding: 10px; border-radius: 5px; margin: 10px 0; }
button { background: #4ca6a8; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
input { padding: 10px; width: 300px; margin: 5px; }
</style></head><body>";

echo "<h1>🧪 Test Booking System (Fixed)</h1>";

echo "<div class='info'>";
echo "<h3>✅ FIXED: No More API Connection Issues!</h3>";
echo "<p>The booking system now connects directly to your WordPress database instead of trying to use API endpoints that don't exist.</p>";
echo "</div>";

echo "<h2>Test a Booking Number</h2>";
echo "<input type='text' id='bookingNumber' placeholder='Enter booking number (e.g., BB-20250117-1234)' />";
echo "<button onclick='testBooking()'>Test Booking</button>";

echo "<div id='result'></div>";

echo "<h2>📋 What's Fixed</h2>";
echo "<div class='success'>";
echo "<ul>";
echo "<li>✅ Removed all WordPress REST API calls</li>";
echo "<li>✅ Uses direct database connection to WordPress database</li>";
echo "<li>✅ No more 'Unable to connect to booking system' errors</li>";
echo "<li>✅ Faster response times (no network calls)</li>";
echo "<li>✅ More reliable (no dependency on WordPress plugins)</li>";
echo "</ul>";
echo "</div>";

?>

<script>
function testBooking() {
    const bookingNumber = document.getElementById('bookingNumber').value.trim();
    const resultDiv = document.getElementById('result');
    
    if (!bookingNumber) {
        resultDiv.innerHTML = '<div class="error">Please enter a booking number</div>';
        return;
    }
    
    resultDiv.innerHTML = '<div class="info">Testing booking: ' + bookingNumber + '...</div>';
    
    fetch('verify_booking.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            booking_number: bookingNumber
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="success">
                    <h3>✅ SUCCESS!</h3>
                    <p><strong>Message:</strong> ${data.message}</p>
                    <p><strong>Hunt:</strong> ${data.hunt_data.hunt_name}</p>
                    <p><strong>Location:</strong> ${data.hunt_data.location}</p>
                    <p><strong>Booking Code:</strong> ${data.booking_data.booking_code}</p>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="error">
                    <h3>❌ ERROR</h3>
                    <p>${data.message}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div class="error">
                <h3>❌ NETWORK ERROR</h3>
                <p>Could not connect to booking system: ${error}</p>
            </div>
        `;
    });
}
</script>

</body></html>
