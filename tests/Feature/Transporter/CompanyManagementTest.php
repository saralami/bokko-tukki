<?php

use App\Models\Transporter;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('a transporter can view their company page', function () {
    $transporter = Transporter::factory()->create();

    $this->actingAs($transporter->user)
        ->get(route('transporter.company.edit'))
        ->assertOk();
});

test('a transporter can update their own company information', function () {
    $transporter = Transporter::factory()->create();

    $this->actingAs($transporter->user)
        ->patch(route('transporter.transporters.update', $transporter), [
            'company_name' => 'Sénégal Voyages',
            'email' => 'contact@senegal-voyages.sn',
            'phone' => '+221338223344',
            'address' => 'Avenue Bourguiba, Dakar',
        ])->assertRedirect(route('transporter.dashboard'));

    $this->assertDatabaseHas('transporters', [
        'id' => $transporter->id,
        'company_name' => 'Sénégal Voyages',
        'email' => 'contact@senegal-voyages.sn',
        'address' => 'Avenue Bourguiba, Dakar',
    ]);
});

test('company email must be valid', function () {
    $transporter = Transporter::factory()->create();

    $this->actingAs($transporter->user)
        ->patch(route('transporter.transporters.update', $transporter), [
            'company_name' => 'Sénégal Voyages',
            'email' => 'not-an-email',
        ])->assertSessionHasErrors(['email']);
});

test('a transporter cannot update another company', function () {
    $companyA = Transporter::factory()->create();
    $companyB = Transporter::factory()->create();

    $this->actingAs($companyB->user)
        ->patch(route('transporter.transporters.update', $companyA), [
            'company_name' => 'Piraté SARL',
        ])->assertForbidden();

    expect($companyA->fresh()->company_name)->not->toBe('Piraté SARL');
});

test('a transporter without a company record is denied the company page', function () {
    $user = User::factory()->transporter()->create();

    $this->actingAs($user)
        ->get(route('transporter.company.edit'))
        ->assertForbidden();
});
