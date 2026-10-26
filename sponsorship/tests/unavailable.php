<?php
http_response_code(503); header('Content-Type: application/json');
echo json_encode(['success' => false, 'data' => ['message' => 'This local review does not send enquiries.']]);
