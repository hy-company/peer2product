
<?php
session_start();
ob_start();
require_once('vendor/autoload.php');

header('Content-Type: application/json');
\Stripe\Stripe::setApiKey('#'); //enter secret key. see stripe dashboard ->developers-> API keys.

if($_SERVER['REQUEST_METHOD'] != 'POST'){
    echo json_encode(['error' => 'invalid request.']);
    exit;
}

$payload = file_get_contents('php://input');
$event = \Stripe\Event::constructFrom(
json_decode($payload, true)
);


var_dump($event->type);
var_dump($event->data->object);
var_dump($event->data->object->id);
error_log(ob_get_clean(), 4);

echo json_encode(['status' => 'success']);

function handleEventcheckoutCompleted(){
//code that handles the updating of the backend to set the status of an order after a successful checkout.
  
}

// Handle the event
switch ($event->type) {
  case 'checkout.session.completed':
    handleEventCheckoutCompleted($event);
  break;  
    
  // more functionalities can be added here for different event types.
        
  default:
    // Unexpected event type
    echo 'Received unknown event type';
}

http_response_code(200);

?>


