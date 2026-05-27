<?php

use App\Mail\LoginOtpMail;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Subscription;
use App\Models\TrustedDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('tenant login from new device requires otp and verification returns token', function (): void {
    Mail::fake();

    $company = Company::query()->create(['name' => 'Sher Tours']);
    createActiveSubscription($company);

    CompanyUser::query()->create([
        'company_id' => $company->id,
        'name' => 'Robin',
        'email' => 'robin@example.com',
        'password' => Hash::make('password123'),
        'status' => 'active',
    ]);

    $login = $this
        ->withHeaders(['X-Device-Id' => 'macbook-robin'])
        ->postJson('/api/tenant/login', [
            'company_id' => $company->id,
            'email' => 'robin@example.com',
            'password' => 'password123',
        ]);

    $login
        ->assertStatus(202)
        ->assertJsonPath('requires_otp', true);

    $challengeId = $login->json('otp_challenge_id');
    $otpCode = null;

    Mail::assertSent(LoginOtpMail::class, function (LoginOtpMail $mail) use (&$otpCode): bool {
        $otpCode = $mail->otpCode;

        return true;
    });

    expect($challengeId)->not->toBeEmpty();
    expect($otpCode)->not->toBeEmpty();

    $verify = $this->postJson('/api/tenant/verify-login-otp', [
        'challenge_id' => $challengeId,
        'otp' => $otpCode,
    ]);

    $verify
        ->assertOk()
        ->assertJsonStructure(['token', 'user']);

    $secondLogin = $this
        ->withHeaders(['X-Device-Id' => 'macbook-robin'])
        ->postJson('/api/tenant/login', [
            'company_id' => $company->id,
            'email' => 'robin@example.com',
            'password' => 'password123',
        ]);

    $secondLogin
        ->assertOk()
        ->assertJsonStructure(['token', 'user'])
        ->assertJsonMissing(['requires_otp' => true]);

    expect(
        TrustedDevice::query()
            ->where('guard', 'tenant')
            ->where('user_id', $verify->json('user.id'))
            ->exists()
    )->toBeTrue();
});

test('tenant login supports email and password only when there is a single company match', function (): void {
    Mail::fake();

    $company = Company::factory()->create([
        'name' => 'Technoguru Digital Systems Ltd',
        'company_code' => 'TECHNOGURU',
        'email' => 'ict@technoguru.co.tz',
    ]);

    createActiveSubscription($company);

    CompanyUser::query()->create([
        'company_id' => $company->id,
        'name' => 'Master User',
        'email' => 'tenant.master@example.com',
        'password' => Hash::make('password123'),
        'status' => 'active',
    ]);

    $login = $this
        ->withHeaders(['X-Device-Id' => 'macbook-master'])
        ->postJson('/api/tenant/login', [
            'email' => 'tenant.master@example.com',
            'password' => 'password123',
        ]);

    $login
        ->assertStatus(202)
        ->assertJsonPath('requires_otp', true)
        ->assertJsonMissing(['requires_company_selection' => true]);
});

test('tenant login returns company picker when multiple companies match email and password', function (): void {
    Mail::fake();

    $companyA = Company::factory()->create([
        'name' => 'Technoguru A',
        'company_code' => 'TECHA',
        'email' => 'a@technoguru.co.tz',
    ]);

    $companyB = Company::factory()->create([
        'name' => 'Technoguru B',
        'company_code' => 'TECHB',
        'email' => 'b@technoguru.co.tz',
    ]);

    createActiveSubscription($companyA);
    createActiveSubscription($companyB);

    CompanyUser::query()->create([
        'company_id' => $companyA->id,
        'name' => 'Shared User A',
        'email' => 'shared.user@example.com',
        'password' => Hash::make('password123'),
        'status' => 'active',
    ]);

    CompanyUser::query()->create([
        'company_id' => $companyB->id,
        'name' => 'Shared User B',
        'email' => 'shared.user@example.com',
        'password' => Hash::make('password123'),
        'status' => 'active',
    ]);

    $login = $this->postJson('/api/tenant/login', [
        'email' => 'shared.user@example.com',
        'password' => 'password123',
    ]);

    $login
        ->assertStatus(409)
        ->assertJsonPath('requires_company_selection', true)
        ->assertJsonCount(2, 'companies');

    $codes = collect($login->json('companies'))->pluck('company_code')->all();

    expect($codes)->toContain('TECHA');
    expect($codes)->toContain('TECHB');
});

test('tenant login can continue with selected company code after company picker response', function (): void {
    Mail::fake();

    $companyA = Company::factory()->create([
        'name' => 'Technoguru Alpha',
        'company_code' => 'TG-ALPHA',
        'email' => 'alpha@technoguru.co.tz',
    ]);

    $companyB = Company::factory()->create([
        'name' => 'Technoguru Beta',
        'company_code' => 'TG-BETA',
        'email' => 'beta@technoguru.co.tz',
    ]);

    createActiveSubscription($companyA);
    createActiveSubscription($companyB);

    CompanyUser::query()->create([
        'company_id' => $companyA->id,
        'name' => 'Shared Alpha',
        'email' => 'shared.select@example.com',
        'password' => Hash::make('password123'),
        'status' => 'active',
    ]);

    CompanyUser::query()->create([
        'company_id' => $companyB->id,
        'name' => 'Shared Beta',
        'email' => 'shared.select@example.com',
        'password' => Hash::make('password123'),
        'status' => 'active',
    ]);

    $login = $this
        ->withHeaders(['X-Device-Id' => 'macbook-shared-select'])
        ->postJson('/api/tenant/login', [
            'email' => 'shared.select@example.com',
            'password' => 'password123',
            'company_code' => 'TG-BETA',
        ]);

    $login
        ->assertStatus(202)
        ->assertJsonPath('requires_otp', true)
        ->assertJsonMissing(['requires_company_selection' => true]);
});

test('inactive tenant user cannot log in even with valid email and password', function (): void {
    Mail::fake();

    $company = Company::factory()->create([
        'name' => 'Inactive Access Co',
        'company_code' => 'INACTIVE-CO',
    ]);

    createActiveSubscription($company);

    CompanyUser::query()->create([
        'company_id' => $company->id,
        'name' => 'Inactive User',
        'email' => 'inactive.user@example.com',
        'password' => Hash::make('password123'),
        'status' => 'inactive',
    ]);

    $login = $this->postJson('/api/tenant/login', [
        'email' => 'inactive.user@example.com',
        'password' => 'password123',
    ]);

    $login
        ->assertStatus(403)
        ->assertJsonPath('message', 'Your account is inactive. Please contact your company administrator.');

    Mail::assertNothingSent();
});

function createActiveSubscription(Company $company): void
{
    Subscription::query()->create([
        'company_id' => $company->id,
        'license_key' => 'TEST-' . strtoupper(Str::random(16)),
        'billing_cycle' => 'monthly',
        'starts_at' => now()->subDay()->toDateString(),
        'ends_at' => now()->addDays(30)->toDateString(),
        'status' => 'active',
        'activated_at' => now(),
        'amount_paid' => 0,
        'currency' => 'USD',
    ]);
}
