<?php

use App\Mail\LoginOtpMail;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\TrustedDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('tenant login from new device requires otp and verification returns token', function (): void {
    Mail::fake();

    $company = Company::query()->create(['name' => 'Sher Tours']);

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
