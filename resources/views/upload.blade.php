<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Street Group Test</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Street Group Test</h2>
    <h4>By Jack Smith</h4>
    <form action="{{ route('upload.process') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label for="file" class="form-label">Choose CSV file</label>
            <input class="form-control" type="file" name="file" id="file" required>
        </div>
        <button type="submit" class="btn btn-primary">Upload and Parse</button>
    </form>

    @if(isset($parsedRecords))
        <h3 class="mt-5">Parsed Homeowner Data:</h3>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>First Name</th>
                    <th>Initial</th>
                    <th>Last Name</th>
                </tr>
            </thead>
            <tbody>
                @foreach($parsedRecords as $person)
                    <tr>
                        <td>{{ $person['title'] }}</td>
                        <td>{{ $person['first_name'] ?? '-' }}</td>
                        <td>{{ $person['initial'] ?? '-' }}</td>
                        <td>{{ $person['last_name'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
</body>
</html>
