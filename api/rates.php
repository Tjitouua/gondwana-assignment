<?php
// Set response type to JSON
header("Content-Type: application/json");

// Get the JSON input and decode it into an array
$input = json_decode(file_get_contents("php://input"), true);

// Check if required fields are missing
if (
    !$input ||
    !isset($input['Unit Name'], $input['Arrival'], $input['Departure'], $input['Occupants'], $input['Ages'])
) {
    http_response_code(400); // Bad request
    echo json_encode(["error" => "Missing or invalid data"]);
    exit;
}

// Change date from dd/mm/yyyy to yyyy-mm-dd
function formatDate($date) {
    $d = explode('/', $date);
    return "$d[2]-$d[1]-$d[0]";
}

// Format arrival and departure dates
$arrival = formatDate($input['Arrival']);
$departure = formatDate($input['Departure']);

// Create guest list with age group info
$guests = [];
foreach ($input['Ages'] as $age) {
    $age = (int)$age;
    $guests[] = [
        "Age Group" => $age < 13 ? "Child" : "Adult",
        "Age" => $age
    ];
}

// Convert unit name to lowercase and remove spaces
$unit = strtolower(trim($input['Unit Name']));

// Map unit names to IDs
$unitIds = [
    "kalahari" => -2147483637,
    "estosha" => -2147483456
];

// Get the unit ID, default if not found
$unitId = $unitIds[$unit] ?? -2147483637;

// Prepare data to send to external API
$dataToSend = [
    "Unit Type ID" => $unitId,
    "Arrival" => $arrival,
    "Departure" => $departure,
    "Guests" => $guests
];

// Send request to Gondwana API
$ch = curl_init("https://dev.gondwana-collection.com/Web-Store/Rates/Rates.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataToSend));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
$result = curl_exec($ch);
curl_close($ch);

// Return the API response
echo $result;
