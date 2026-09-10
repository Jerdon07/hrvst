<?php

use App\Enums\ValidIdType;
use App\Models\RegistrationRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function validRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'role' => 'dealer',
        'name' => 'Juan Doe',
        'phone_number' => '09'.fake()->numerify('#########'),
        'email' => null,
        'pin' => '123456',
        'pin_confirmation' => '123456',
        'municipality_id' => null,
        'barangay_id' => null,
        'latitude' => null,
        'longitude' => null,
    ], $overrides);
}

it('stores id_type and id_number when both are provided', function () {
    $this->post('/register', validRegistrationPayload([
        'id_type' => ValidIdType::PhilippineNationalId->value,
        'id_number' => '1234-5678-9012',
    ]))->assertSessionHasNoErrors();

    $request = RegistrationRequest::first();

    // This is the exact regression: id_type/id_number reaching $request->validated()
    // and surviving into the created model, not just existing in the raw payload.
    expect($request)->not->toBeNull()
        ->and($request->id_type)->toBe(ValidIdType::PhilippineNationalId)
        ->and($request->id_number)->toBe('1234-5678-9012');
});

it('treats an empty-string id_type from the client the same as omitted', function () {
    // Reproduces the exact wire payload the Vue form sends when the Select
    // is cleared: id_type as '' rather than absent. Without
    // prepareForValidation() normalizing this, the enum cast throws a
    // ValueError on RegistrationRequest::create().
    $this->post('/register', validRegistrationPayload([
        'id_type' => '',
        'id_number' => '',
    ]))->assertSessionHasNoErrors();

    $request = RegistrationRequest::first();

    expect($request->id_type)->toBeNull()
        ->and($request->id_number)->toBeNull();
});

it('requires id_number when id_type is provided', function () {
    $this->post('/register', validRegistrationPayload([
        'id_type' => ValidIdType::VotersId->value,
        'id_number' => null,
    ]))->assertInvalid(['id_number']);

    expect(RegistrationRequest::count())->toBe(0);
});

it('rejects an id_number that does not match the selected id_type format', function () {
    $this->post('/register', validRegistrationPayload([
        'id_type' => ValidIdType::VotersId->value,
        'id_number' => 'not-a-number',
    ]))->assertInvalid(['id_number']);

    expect(RegistrationRequest::count())->toBe(0);
});

it('accepts a correctly formatted id_number for each id type', function (ValidIdType $type, string $number) {
    $this->post('/register', validRegistrationPayload([
        'id_type' => $type->value,
        'id_number' => $number,
    ]))->assertSessionHasNoErrors();

    expect(RegistrationRequest::first()->id_type)->toBe($type);
})->with([
    'drivers license' => [ValidIdType::DriversLicense, 'N01-23-456789'],
    'national id' => [ValidIdType::PhilippineNationalId, '1234-5678-9012'],
    'passport' => [ValidIdType::PhilippinePassport, 'P1234567'],
    'voters id' => [ValidIdType::VotersId, '123456789'],
]);

it('stores an uploaded supporting document against the registration request', function () {
    Storage::fake(config('media-library.disk_name', 'public'));

    $file = UploadedFile::fake()->createWithContent(
        'permit.pdf',
        "%PDF-1.4\n%\xC3\xA2\xC3\xA3\xC3\x8F\xC3\x93\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF"
    );

    $this->post('/register', validRegistrationPayload([
        'supporting_document' => $file,
    ]))->assertSessionHasNoErrors();

    $request = RegistrationRequest::first();

    expect($request->getFirstMedia('supporting_document'))->not->toBeNull()
        ->and($request->getFirstMedia('supporting_document')->file_name)->toBe('permit.pdf');
});

it('rejects a supporting document with a disallowed mime type', function () {
    Storage::fake(config('media-library.disk_name', 'public'));

    $file = UploadedFile::fake()->create('notes.txt', 10, 'text/plain');

    $this->post('/register', validRegistrationPayload([
        'supporting_document' => $file,
    ]))->assertInvalid(['supporting_document']);

    expect(RegistrationRequest::count())->toBe(0);
});

it('rejects a supporting document larger than 5MB', function () {
    Storage::fake(config('media-library.disk_name', 'public'));

    $file = UploadedFile::fake()->create('permit.pdf', 5121, 'application/pdf');

    $this->post('/register', validRegistrationPayload([
        'supporting_document' => $file,
    ]))->assertInvalid(['supporting_document']);
});

it('succeeds with no id verification or document fields at all', function () {
    if (config('app.auto_approve_registrations')) {
        $this->markTestSkipped('Auto-approve registrations is enabled.');
    }

    $this->post('/register', validRegistrationPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(RegistrationRequest::count())->toBe(1);
});
