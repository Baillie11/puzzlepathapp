<?php
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Generate HTML email template for booking confirmations
 * This function creates a properly styled HTML email that fixes the white text on white background issue
 */
function get_email_template($booking, $booking_code, $event, $quest_link = '') {
    $event_title = $event ? $event->title : 'Your Event';
    $event_date = $event ? date('F j, Y \a\t g:i A', strtotime($event->event_date)) : 'TBD';
    $quest_button_html = '';
    
    if ($quest_link) {
        $quest_button_html = '
        <div style="margin: 30px 0; padding: 25px; background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); border: 2px solid #4fc3f7; border-radius: 15px; text-align: center;">
            <h3 style="color: #1976d2; margin: 0 0 15px 0; font-size: 20px;">🎯 Ready to start your quest?</h3>
            <p style="color: #424242; margin: 0 0 20px 0;">Click the button below to visit ' . ($event ? $event->hunt_name : 'your adventure') . ':</p>
            <a href="' . $quest_link . '?booking=' . $booking_code . '" 
               style="display: inline-block; background: linear-gradient(135deg, #6a82fb 0%, #fc5c7d 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 18px; margin: 10px 0; text-align: center; transition: transform 0.3s ease;">
                🚀 Start Quest Now!
            </a>
            <div style="margin-top: 20px; padding: 15px; background: #fff3e0; border: 1px solid #ffcc02; border-radius: 8px;">
                <p style="color: #e65100; font-weight: bold; margin: 0 0 10px 0; font-size: 14px;">📝 Important: Please save your booking code:</p>
                <p style="color: #bf360c; font-family: \'Courier New\', monospace; font-size: 16px; font-weight: bold; margin: 0; letter-spacing: 1px; background: #ffffff; padding: 8px; border-radius: 4px; border: 1px dashed #ff9800;">' . $booking_code . '</p>
                <p style="color: #e65100; margin: 10px 0 0 0; font-size: 12px;">You\'ll need this to access your quest on the day of your adventure!</p>
            </div>
        </div>';
    }
    
    $total_paid = '$' . number_format($booking->total_price, 2);
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Your PuzzlePath Booking Confirmation</title>
        <style>
            body { margin: 0; padding: 0; background-color: #f5f5f5; font-family: \'Arial\', \'Helvetica\', sans-serif; }
            .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; }
            .logo { max-width: 200px; height: auto; }
            .content { padding: 30px; }
            .greeting { font-size: 24px; color: #333333; margin-bottom: 20px; font-weight: bold; }
            .message { font-size: 16px; color: #555555; line-height: 1.6; margin-bottom: 25px; }
            .booking-details { background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 25px 0; }
            .booking-details h3 { color: #495057; margin: 0 0 15px 0; font-size: 18px; }
            .detail-row { display: flex; justify-content: space-between; margin: 8px 0; padding: 8px 0; border-bottom: 1px dotted #dee2e6; }
            .detail-label { font-weight: bold; color: #495057; }
            .detail-value { color: #212529; }
            .booking-code { font-family: \'Courier New\', monospace; font-size: 18px; font-weight: bold; color: #dc3545; background: #ffffff; padding: 5px 8px; border-radius: 4px; border: 1px solid #dc3545; }
            .footer { background-color: #343a40; color: #ffffff; padding: 20px; text-align: center; }
            .footer p { margin: 5px 0; font-size: 14px; }
            @media only screen and (max-width: 600px) {
                .detail-row { flex-direction: column; }
                .detail-label, .detail-value { margin: 2px 0; }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="header">
                <img src="https://www.puzzlepath.com.au/wp-content/uploads/2023/05/puzzlepath-logo-web.png" alt="Puzzle Path" class="logo" />
                <h1 style="color: white; margin: 20px 0 0 0; font-size: 28px;">Booking Confirmation</h1>
            </div>
            
            <div class="content">
                <div class="greeting">Hello ' . esc_html($booking->customer_name) . '! 👋</div>
                
                <div class="message">
                    Thank you for booking your PuzzlePath adventure. We\'re excited to have you join us for an unforgettable treasure hunt experience!
                </div>
                
                <div class="booking-details">
                    <h3>📋 Booking Details</h3>
                    <div class="detail-row">
                        <span class="detail-label">Event:</span>
                        <span class="detail-value">' . esc_html($event_title) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date & Time:</span>
                        <span class="detail-value">' . esc_html($event_date) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total Paid:</span>
                        <span class="detail-value">' . esc_html($total_paid) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Booking Code:</span>
                        <span class="detail-value booking-code">' . esc_html($booking_code) . '</span>
                    </div>
                </div>
                
                ' . $quest_button_html . '
                
                <div class="message">
                    We look forward to seeing you soon for your adventure!<br><br>
                    If you have any questions or need to make changes to your booking, please don\'t hesitate to contact us.
                </div>
            </div>
            
            <div class="footer">
                <p><strong>PuzzlePath Team</strong></p>
                <p>📧 info@puzzlepath.com.au | 📞 Contact us through our website</p>
                <p>🌐 www.puzzlepath.com.au</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>