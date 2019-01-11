<?php
require 'flight/Flight.php';

//handle page not found
Flight::map('notFound', function () {
	$result['status'] = 'ERROR';
	$result['msg'] = 'Page not found';
	echo json_encode($result);
});

//connect to db mysql
Flight::register('db', 'PDO', array('mysql:host=localhost;dbname=loket', 'root', '' ));


Flight::route('/', function(){
    echo 'Welcome to loket JSON API!';
});


/*
	Endpoint to create new event
	parameter 	: location_id, event_name, startdate, enddate
	method 		: POST
*/
Flight::route('POST /event/create', function () {

	$location_id = $_POST['location_id'];
	$event = $_POST['event_name'];
	$start = date('Y-m-d', strtotime($_POST['startdate']));
	$end = date('Y-m-d', strtotime($_POST['enddate']));

	//validate input
	if(trim($event) == '' || trim($start) == '' || trim($end) == ''){
		$result['status'] = 'ERROR';
		$result['msg'] = 'All field must be fill';
	}else if(preg_match("/^[a-zA-Z0-9._-]*$/", $event) != true){
		$result['status'] = 'ERROR';
		$result['msg'] = 'Event name invalid character';
	}else if(strtotime($start) >= strtotime($end)){
		$result['status'] = 'ERROR';
		$result['msg'] = 'Enddate must be greater than startdate';
	}else{
		$sql = "insert into event (location_id, event_name, startdate, enddate, created_date) values ('$location_id', '$event', '$start', '$end', NOW())";
		//insert to db
		$res = Flight::db()->query($sql);
		if($res != false){
			$result['status'] = 'OK';
			$result['msg'] = 'Event created';
		}else{
			$result['status'] = 'ERROR';
			$result['msg'] = 'Insert error';
		}
	}

	echo json_encode($result);
});


/*
	Endpoint to create new location
	parameter 	: location
	method 		: POST
*/
Flight::route('POST /location/create', function () {
	$location = $_POST['location'];

	//validate input
	if(trim($location) == ''){
		$result['status'] = 'ERROR';
		$result['msg'] = 'Location must be fill';
	}else if(preg_match("/^[a-zA-Z0-9._-]*$/", $location)){
		$sql = "insert into location (location_name, created_date) values ('$location', NOW())";
		//insert to db
		$res = Flight::db()->query($sql);
		if($res != false){
			$result['status'] = 'OK';
			$result['msg'] = 'Location created';
		}else{
			$result['status'] = 'ERROR';
			$result['msg'] = 'Insert error';
		}
	}else{
		$result['status'] = 'ERROR';
		$result['msg'] = 'Invalid character';
	}
	echo json_encode($result);
});


/*
	Endpoint to create new ticket type on one specific event
	parameter 	: event_id, type, price, quota
	method 		: POST
*/
Flight::route('POST /location/ticket/create', function () {
	$event_id = $_POST['event_id'];
	$type = $_POST['type'];
	$price = $_POST['price'];
	$quota = $_POST['quota'];

	//validation
	if(trim($type) == '' || trim($price) == '' || trim($quota) == ''){
		$result['status'] = 'ERROR';
		$result['msg'] = 'All field must be fill';
	}else if(preg_match("/^[a-zA-Z0-9._-]*$/", $type) != true){
		$result['status'] = 'ERROR';
		$result['msg'] = 'Ticket type invalid character';
	}else if(ctype_digit($price) != true || ctype_digit($quota) != true){
		$result['status'] = 'ERROR';
		$result['msg'] = 'Price and quota must be number';
	}else{
		$sql = "insert into ticket (event_id, type, price, quota, created_date) values ('$event_id', '$type', '$price', '$quota', NOW())";
		//insert to db
		$res = Flight::db()->query($sql);
		if($res != false){
			$result['status'] = 'OK';
			$result['msg'] = 'Ticket created';
		}else{
			$result['status'] = 'ERROR';
			$result['msg'] = 'Insert error';
		}
	}

	echo json_encode($result);
});


/*
	Endpoint to retrieve event information, including location data and ticket data
	parameter 	: -
	method 		: GET
*/
Flight::route('GET /event/get_info', function () {
	$sql = "select a.type, a.price, a.quota, b.event_name, b.startdate, b.enddate, c.location_name
			from ticket a 
			left join event b on a.event_id = b.id
			left join location c on b.location_id=c.id";
	$res = Flight::db()->query($sql);
	if($res != false){
		$result['status'] = 'OK';
		$result['msg'] = 'Data selected';
		foreach($res->fetchAll(PDO::FETCH_ASSOC) as $key => $value){
			$data[$value['event_name']][] = $value;
		}
		$result['data'] = $data;
	}else{
		$result['status'] = 'ERROR';
		$result['msg'] = 'Retrieve data error';
	}

	echo json_encode($result);
});


/*
	Endpoint to make a new purchase, customer data is sent via this API
	parameter 	: username, email, address, payment, detail => array(ticket_id, qty)
	method 		: POST
*/
Flight::route('POST /transaction/purchase', function () {
	$username = $_POST['username'];
	$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
	$address = $_POST['address'];
	$payment = $_POST['payment'];
	$detail = json_decode($_POST['detail'], true);
	
	//validation
	if(trim($username) == '' || trim($email) == '' || trim($address) == '' || trim($payment) == ''){
		$result['status'] = 'ERROR';
		$result['msg'] = 'All field must be fill';
	}else if(preg_match("/^[a-zA-Z0-9._-]*$/", $username) != true || preg_match("/^[a-zA-Z0-9._-]*$/", $address) != true){
		$result['status'] = 'ERROR';
		$result['msg'] = 'Username and address invalid character';
	}else if(filter_var($email, FILTER_VALIDATE_EMAIL) != true){
		$result['status'] = 'ERROR';
		$result['msg'] = 'Email invalid format';
	}else if(empty($detail)){
		$result['status'] = 'ERROR';
		$result['msg'] = 'Please add ticket to purchase';
	}else{
		$sqlTrans = "insert into transaction (username, email, address, payment, status, created_date) values ('$username', '$email', '$address', '$payment', 1, NOW())";
		Flight::db()->beginTransaction();
		$stat = 1;
		//insert to db transaction
		$resTrans = Flight::db()->query($sqlTrans);
		if($resTrans != false){
			$transaction_id = Flight::db()->lastInsertId();
			$sqlTransDetail = '';
			$sqlUpdateQuota = '';
			foreach($detail as $key => $value){
				$ticket_id = $value['ticket_id'];
				$qty = $value['qty'];
				if($qty == '' || ctype_digit($qty) != true){
					Flight::db()->rollBack();
					$result['status'] = 'ERROR';
					$result['msg'] = 'Insert transaction error';
					$stat = 0;
					break;
				}else{
					//select current ticket quota
					$sqlSelectQuota = 'select (quota - '.$qty.') as quota, price from ticket WHERE id = '.$ticket_id;
					$resQuota = Flight::db()->query($sqlSelectQuota);
					$dataTicket = $resQuota->fetch(PDO::FETCH_ASSOC);
					$quota = $dataTicket['quota'];
					$price = $dataTicket['price'] * $qty;
					if($quota < 0){
						Flight::db()->rollBack();
						$result['status'] = 'ERROR';
						$result['msg'] = 'Ticket out of stock';
						$stat = 0;
						break;
					}else{
						$sqlTransDetail .= "insert into transaction_details (ticket_id, transaction_id, qty, price) values ('$ticket_id', '$transaction_id', '$qty', '$price'); ";
						$sqlUpdateQuota .= "update ticket set quota = $quota where id=$ticket_id; ";
					}
				}
			}
			if($stat == 1){
				Flight::db()->commit();
				//insert to db transaction details and update stock ticket
				$resTransDetail = Flight::db()->query($sqlTransDetail.$sqlUpdateQuota);
				$result['status'] = 'OK';
				$result['msg'] = 'Transaction success';
			}
		}else{
			$result['status'] = 'ERROR';
			$result['msg'] = 'Insert transaction error';
		}
	}

	echo json_encode($result);
});


/*
	Endpoint to retrieve transaction created using endpoint Purchase Ticket
	parameter 	: -
	method 		: GET
*/
Flight::route('GET /transaction/get_info', function () {
	$sql = "select a.transaction_id, a.qty, a.price, b.username, b.email, b.payment, b.address,
			b.created_date, c.type, d.event_name, e.location_name
			from transaction_details a 
			left join transaction b on a.transaction_id = b.id
			left join ticket c on a.ticket_id=c.id
			left join event d on c.event_id=d.id
			left join location e on d.location_id=e.id";
	$res = Flight::db()->query($sql);
	if($res != false){
		$result['status'] = 'OK';
		$result['msg'] = 'Data selected';
		foreach($res->fetchAll(PDO::FETCH_ASSOC) as $key => $value){
			$data[$value['transaction_id']][] = $value;
		}
		$result['data'] = $data;
	}else{
		$result['status'] = 'ERROR';
		$result['msg'] = 'Retrieve data error';
	}

	echo json_encode($result);
});


Flight::start();
