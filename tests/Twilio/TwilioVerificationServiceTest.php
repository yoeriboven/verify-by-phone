<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Propaganistas\LaravelPhone\PhoneNumber;
use Twilio\Rest\Client;
use Worksome\VerifyByPhone\Contracts\PhoneVerificationService;
use Worksome\VerifyByPhone\Exceptions\VerificationCodeExpiredException;
use Worksome\VerifyByPhone\Services\Twilio\TwilioVerificationService;
use Worksome\VerifyByPhone\Tests\Concerns\FakesTwilioRequests;

uses(FakesTwilioRequests::class);

beforeEach(function () {
    $this->service = new TwilioVerificationService(
        $this->app->make(Client::class),
        'aniofandioancdioscnaopdjnaocaejopiof'
    );
});

it('can resolve a Twilio client object from the container', function () {
    /** @var Client $client */
    $client = $this->app->make(Client::class);

    // Set in the base TestCase
    expect($client->getAccountSid())->toBe('aniofandioancdioscnaopdjnaocaejopiof');
});

it('can resolve the twilio service via the configured driver', function () {
    config()->set('verify-by-phone.driver', 'twilio');

    expect($this->app->make(PhoneVerificationService::class))
        ->toBeInstanceOf(TwilioVerificationService::class);
});

it('can send a Twilio verification SMS', function () {
    $this->fakeSendRequest('+44 01234567890');

    $this->service->send(new PhoneNumber('+44 01234567890'));

    Http::assertSent(function (Request $request) {
        $url = Str::of($request->url());

        return $url->startsWith('https://verify.twilio.com/v2/Services/')
            && $url->endsWith('/Verifications');
    });
});

it('throws an exception if the code has expired', function () {
    $this->fakeVerifyRequestWithExpiredCode();

    $this->service->verify(new PhoneNumber('+44 01234567890'), '1234');
})->throws(VerificationCodeExpiredException::class);

it('returns true if the code is acceptable', function () {
    $this->fakeVerifyRequest('+44 01234567890');

    $result = $this->service->verify(new PhoneNumber('+44 01234567890'), '1234');

    expect($result)->toBeTrue();
});

it('returns false if the code is incorrect', function () {
    $this->fakeVerifyRequest('+44 01234567890', false);

    $result = $this->service->verify(new PhoneNumber('+44 01234567890'), '1234');

    expect($result)->toBeFalse();
});
