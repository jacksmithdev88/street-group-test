<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Homeowner;

class HomeownerController extends Controller
{
    public function showForm()
    {
        // just return upload view
        return view('upload');
    }

    public function upload(Request $request)
    {
        // send file for validation
        $this->validateFile($request);

        // store the file in temp storage
        $path = $this->storeFile($request);

        // get the file content
        $content = Storage::get($path);

        // parse the file content
        $lines = $this->parseFile($content);

        // process the parsed lines into valid Homeowner objects
        $parsedRecords = $this->processLines($lines);

        // return view with parsed records passed
        return view('upload', compact('parsedRecords'));
    }

    private function validateFile(Request $request)
    {
        // allow only csv, ensure file is present and not larger than 2MB
        $request->validate([
            'file' => 'required|mimes:csv|max:2048',
        ]);
    }

    private function storeFile(Request $request)
    {
        // store the file in the uploads directory
        return $request->file('file')->store('uploads');
    }

    private function parseFile($content)
    {
        // split the content into lines
        $lines = explode(PHP_EOL, $content);
        // remove trailing commas as this can be a problem when parsing names, also causes eyesore when displaying
        return array_map('rtrim', $lines, array_fill(0, count($lines), ','));
    }

    private function processLines($lines)
    {
        // initiate an empty array to store lines
        $parsedRecords = [];
        // loop through each line
        foreach ($lines as $line) {
            // if it is not an empty line then parse the name and add to the array
            if (!empty(trim($line))) {
                $parsedRecords = array_merge($parsedRecords, $this->parseName($line));
            }
        }
        return $parsedRecords;
    }

    private function parseName($line)
    {
        $parsed = [];
        $line = trim($line);
        
        // Check if the line contains 'and' or '&'
        $isAndFormat = preg_match('/\s+and\s+|\s*&\s*/i', $line);
        
        // Use getNameParts to process each part of the name
        $people = $this->getNameParts($line, $isAndFormat);
    
        if ($people !== false) {
            foreach ($people as $person) {
                $parsed[] = $person;
                // Save to database
                $homeowner = new Homeowner($person);
                $homeowner->save();
            }
        }
    
        return $parsed;
    }

    private function getNameParts($line, $isAndFormat = false)
    {
        if ($isAndFormat) {
            // Split the line by 'and' or '&'
            $segments = preg_split('/\s+and\s+|\s*&\s*/i', $line);

            // Open an array to store people
            $people = [];

            // Process each segment, keeping track of the index
            foreach ($segments as $index => $segment) {
                $parts = explode(' ', trim($segment));
                // title will always be first part
                $title = $parts[0];
                $first_name = null;
                $last_name = null;
                $initial = null;

                // Identify structure based on parts count
                if (count($parts) == 2) {
                    // Case 1: Title and Last Name (e.g., "Mr Smith")
                    $last_name = $parts[1];
                } elseif (count($parts) == 3) {
                    if (strlen($parts[1]) === 1 || (strlen($parts[1]) === 2 && $parts[1][1] === '.')) { 
                        // Case 2: Title, Initial, and Last Name
                        $initial = rtrim($parts[1], '.');
                        $last_name = $parts[2];
                    } else { 
                        // Case: Title, First Name, and Last Name
                        $first_name = $parts[1];
                        $last_name = $parts[2];
                    }
                } elseif (count($parts) == 1 && isset($segments[$index + 1])) {
                    // Case 3: Title only (e.g., "Mrs") this should be at the first part of the segment
                    // Get last name from next segment
                    $nextParts = explode(' ', trim($segments[$index + 1]));
                    $last_name = end($nextParts);
                } else {
                    echo "Skipping unrecognized format: $segment\n";
                    continue; // Skip if it doesn't match expected patterns
                }

                // Build person record
                $people[] = [
                    'title' => $title,
                    'first_name' => $first_name,
                    'initial' => $initial,
                    'last_name' => $last_name
                ];
            }

            return $people;
        } else {
            // Process single person names
            $parts = explode(' ', $line);
            $person = ['title' => $parts[0], 'first_name' => null, 'initial' => null, 'last_name' => null];

            // Handle based on the number of parts
            switch (count($parts)) {
                case 2:
                    // Case: Title and Last Name only
                    $person['last_name'] = $parts[1];
                    break;
                case 3:
                    // Case: Title, First Name/Initial, and Last Name
                    if (strlen($parts[1]) === 1 || (strlen($parts[1]) === 2 && $parts[1][1] === '.')) {
                        // letter and . will indicate an initial
                        $person['initial'] = rtrim($parts[1], '.');
                    } else {
                        // Assume it's a first name
                        $person['first_name'] = $parts[1];
                    }
                    $person['last_name'] = $parts[2];
                    break;
                default:
                // Skip unrecognized formats
                    return false; 
            }

            return [$person];
        }
    }
}
