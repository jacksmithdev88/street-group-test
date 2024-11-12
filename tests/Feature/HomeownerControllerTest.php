<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Homeowner;

class HomeownerControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_shows_the_upload_form()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('upload');
    }

    /** @test */
    public function it_uploads_and_parses_CSV_with_initial_user()
    {
        Storage::fake('local');

        $fileContent = "Mrs F. Smith";
        $file = UploadedFile::fake()->createWithContent('homeowners.csv', $fileContent);

        $response = $this->post('/', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('upload');
        $response->assertViewHas('parsedRecords');

        $parsedRecords = $response->viewData('parsedRecords');
        $this->assertCount(1, $parsedRecords);

        $this->assertDatabaseHas('homeowners', [
            'title' => 'Mrs',
            'initial' => 'F',
            'last_name' => 'Smith',
        ]);
    }

    /** @test */
    public function it_skips_invalid_1_part_data() {
        Storage::fake('local');

        $fileContent = "Homeowners";
        $file = UploadedFile::fake()->createWithContent('homeowners.csv', $fileContent);

        $response = $this->post('/', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('upload');
        $response->assertViewHas('parsedRecords');

        $parsedRecords = $response->viewData('parsedRecords');
        $this->assertCount(0, $parsedRecords);
    }

    /** @test */
    public function it_uploads_and_parses_CSV_with_double_user()
    {
        Storage::fake('local');

        $fileContent = "Mrs Jane Smith and Mr John Smith";
        $file = UploadedFile::fake()->createWithContent('homeowners.csv', $fileContent);

        $response = $this->post('/', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('upload');
        $response->assertViewHas('parsedRecords');

        $parsedRecords = $response->viewData('parsedRecords');
        $this->assertCount(2, $parsedRecords);

        $this->assertDatabaseHas('homeowners', [
            'title' => 'Mrs',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $this->assertDatabaseHas('homeowners', [
            'title' => 'Mr',
            'first_name' => 'John',
            'last_name' => 'Smith',
        ]);
    }

    /** @test */
    public function it_uploads_and_parses_2_users_with_initials() { 
        Storage::fake('local');

        $fileContent = "Mrs F. Smith and Mr J. Smith";
        $file = UploadedFile::fake()->createWithContent('homeowners.csv', $fileContent);

        $response = $this->post('/', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('upload');
        $response->assertViewHas('parsedRecords');

        $parsedRecords = $response->viewData('parsedRecords');
        $this->assertCount(2, $parsedRecords);

        $this->assertDatabaseHas('homeowners', [
            'title' => 'Mrs',
            'initial' => 'F',
            'last_name' => 'Smith',
        ]);

        $this->assertDatabaseHas('homeowners', [
            'title' => 'Mr',
            'initial' => 'J',
            'last_name' => 'Smith',
        ]);

    }
}