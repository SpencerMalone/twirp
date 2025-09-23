<?php

declare(strict_types=1);

namespace Tests\Twirp\Complete;

use GuzzleHttp\Psr7\ServerRequest;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Twirp\Context;
use Twirp\Tests\Complete\Proto\Haberdasher;
use Twirp\Tests\Complete\Proto\HaberdasherServer;
use Twirp\Tests\Complete\Proto\Hat;
use Twirp\Tests\Complete\Proto\Size;

final class HaberdasherServerTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

    public function testItReturnsAnInternalErrorWhenTheServiceThrowsAnException(): void
    {
        $haberdasher = $this->prophesize(Haberdasher::class);
        $hooks = new ServerHooksErrorStub();

        $haberdasherServer = new HaberdasherServer($haberdasher->reveal(), $hooks);

        $e = new \Exception('message');

        $haberdasher->MakeHat(Argument::any(), Argument::type(Size::class))->willThrow($e);

        $req = new ServerRequest(
            'POST',
            '/twirp/twirp.tests.complete.proto.Haberdasher/MakeHat',
            ['Content-Type' => 'application/json'],
            '{}'
        );

        $haberdasherServer->handle($req);

        $this->assertSame($e, $hooks->error);
        $this->assertEquals(500, Context::statusCode($hooks->ctx));
    }

    public function testItAcceptsAnEmptyPathPrefix(): void
    {
        $haberdasher = $this->prophesize(Haberdasher::class);

        $haberdasherServer = new HaberdasherServer($haberdasher->reveal(), null, null, null, '');

        $hat = new Hat();
        $hat->setSize(1);

        $haberdasher->MakeHat(Argument::any(), Argument::type(Size::class))->willReturn($hat);

        $req = new ServerRequest(
            'POST',
            '/twirp.tests.complete.proto.Haberdasher/MakeHat',
            ['Content-Type' => 'application/json'],
            '{}'
        );

        $resp = $haberdasherServer->handle($req);

        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('{"size":1}', $resp->getBody()->getContents());
    }

    public function testItAcceptsACustomPathPrefix(): void
    {
        $haberdasher = $this->prophesize(Haberdasher::class);

        $haberdasherServer = new HaberdasherServer($haberdasher->reveal(), null, null, null, '/custom/path');

        $hat = new Hat();
        $hat->setSize(1);

        $haberdasher->MakeHat(Argument::any(), Argument::type(Size::class))->willReturn($hat);

        $req = new ServerRequest(
            'POST',
            '/custom/path/twirp.tests.complete.proto.Haberdasher/MakeHat',
            ['Content-Type' => 'application/json'],
            '{}'
        );

        $resp = $haberdasherServer->handle($req);

        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('{"size":1}', $resp->getBody()->getContents());
    }

    public function testItEmitsDefaultValuesWhenEmitJsonDefaultsIsTrue(): void
    {
        $haberdasher = $this->prophesize(Haberdasher::class);

        $haberdasherServer = new HaberdasherServer($haberdasher->reveal(), null, null, null, '/twirp', true);

        $hat = new Hat();
        $hat->setSize(1);
        // Don't set color or name, so they will have default values

        $haberdasher->MakeHat(Argument::any(), Argument::type(Size::class))->willReturn($hat);

        $req = new ServerRequest(
            'POST',
            '/twirp/twirp.tests.complete.proto.Haberdasher/MakeHat',
            ['Content-Type' => 'application/json'],
            '{}'
        );

        $resp = $haberdasherServer->handle($req);

        $this->assertEquals(200, $resp->getStatusCode());
        // With emitJsonDefaults=true, all fields should be included even if they have default values
        $this->assertEquals('{"size":1,"color":"","name":""}', $resp->getBody()->getContents());
    }

    public function testItSkipsDefaultValuesWhenEmitJsonDefaultsIsFalse(): void
    {
        $haberdasher = $this->prophesize(Haberdasher::class);

        $haberdasherServer = new HaberdasherServer($haberdasher->reveal(), null, null, null, '/twirp', false);

        $hat = new Hat();
        $hat->setSize(1);
        // Don't set color or name, so they will have default values

        $haberdasher->MakeHat(Argument::any(), Argument::type(Size::class))->willReturn($hat);

        $req = new ServerRequest(
            'POST',
            '/twirp/twirp.tests.complete.proto.Haberdasher/MakeHat',
            ['Content-Type' => 'application/json'],
            '{}'
        );

        $resp = $haberdasherServer->handle($req);

        $this->assertEquals(200, $resp->getStatusCode());
        // With emitJsonDefaults=false, only non-default values should be included
        $this->assertEquals('{"size":1}', $resp->getBody()->getContents());
    }

    public function testEmitJsonDefaultsGetterAndSetter(): void
    {
        $haberdasher = $this->prophesize(Haberdasher::class);
        $haberdasherServer = new HaberdasherServer($haberdasher->reveal(), null, null, null, '/twirp', false);

        // Test initial value
        $this->assertFalse($haberdasherServer->getEmitJsonDefaults());

        // Test setter
        $haberdasherServer->setEmitJsonDefaults(true);
        $this->assertTrue($haberdasherServer->getEmitJsonDefaults());

        // Test setter again
        $haberdasherServer->setEmitJsonDefaults(false);
        $this->assertFalse($haberdasherServer->getEmitJsonDefaults());
    }
}
