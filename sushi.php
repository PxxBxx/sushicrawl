<?php
echo "Please paste your multi-line curl command below. Press Enter on an empty line when you're done:\n";

// Initialize a variable to capture the user's curl command
$userCurlCommand = "";

// Read lines until the user enters an empty line
while (true) {
    $line = fgets(STDIN); // Read a line from standard input
    if (trim($line) === '') {
        break; // Stop reading if the user enters an empty line
    }
    $userCurlCommand .= $line; // Append each line to the variable
}

callAndParsePage($userCurlCommand);

// FUNCTION PARSE HTML
function callAndParsePage($userCurlCommand, $nextUrl = null) {

    // Initialize variables for URL and headers
    $url = "";
    $headers = [];

    // Parse the curl command
    $lines = explode("\\", $userCurlCommand); // Split by backslash line continuation
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, 'curl') === 0) {
            // Extract URL from the main curl line and strip quotes if present
            preg_match('/curl\s+["\']?([^"\']+)["\']?/', $line, $matches);
            $url = $matches[1] ?? '';
        } elseif (strpos($line, '-H') === 0) {
            // Extract headers
            preg_match('/-H\s+["\']?([^"\']+)["\']?/', $line, $matches);
            if (isset($matches[1])) {
                $headers[] = $matches[1];
            }
        }
    }

    // Make sure we have a URL to work with
    if (empty($url)) {
        echo "No URL found in the curl command.\n";
        exit;
    }

    // Use the nextPage URL :)
    if (!empty($nextUrl)) {
        $url = $nextUrl;
    }

    // Get page name
    $pageName = 'output';
    if (preg_match("#/([^/]+)/$#", $url, $m)) {
        $pageName = $m[1];
    }

    // Initialize cURL
    $ch = curl_init($url);

    // Set headers in the cURL request
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute the cURL request and capture the response
    $response = curl_exec($ch);

    // Check for errors
    if ($response === false) {
        echo "cURL error: " . curl_error($ch) . "\n";
    } else {
        // Extract JSON data between <script>ts_reader.run( and );</script>
        preg_match('/<script>ts_reader\.run\((.*?)\);<\/script>/s', $response, $matches);
        if (isset($matches[1])) {
            $jsonString = $matches[1];
            $data = json_decode($jsonString); // Decode JSON into PHP array
            if (json_last_error() === JSON_ERROR_NONE) {
                //echo "\nExtracted JSON Data:\n";
                //var_dump($data);
                $nextUrl = $data->nextUrl;
                $sources = $data->sources;
                // Fetch sources
                echo $pageName;
                fetchSources($sources, $pageName, $headers);
                if (!empty($nextUrl)) {
                    callAndParsePage($userCurlCommand, $nextUrl);
                }
            } else {
                echo "\nFailed to decode JSON. Raw JSON string:\n$jsonString\n";
            }
        } else {
            echo "\nNo JSON string found in the specified format.\n";
        }
    }

    // Close the cURL session
    curl_close($ch);
}

function fetchSources($sources, $outputDir = 'output', $headers = []) {
    if (!is_dir($outputDir)) {
        mkdir($outputDir);
    }
    foreach ($sources as $source) {
        foreach ($source->images as $image) {
            $filename = 'output.jpg';
            if (preg_match("#/([^/]+)$#", $image, $m)) {
                $filename = $m[1];
            }
            $tempFP = fopen('./'.$outputDir.'/'.$filename, 'w+');
            $ch = curl_init($image);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FILE , $tempFP);
            curl_exec($ch);
            curl_close($ch);
            fclose($tempFP);
            echo '.';
        }
    }
    echo " OK\n";
}

?>
