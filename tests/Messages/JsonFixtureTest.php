<?php

namespace Muzzle\Messages;

use Muzzle\HttpMethod;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class JsonFixtureTest extends TestCase
{

    /** @test */
    public function itMakesTheBodyOfAJsonResponseArrayAccessible()
    {

        $fixture = new JsonFixture(HttpMethod::GET, [], json_encode(['data' => ['foo' => 'bar']]));

        $this->assertSame('bar', $fixture['data.foo']);
    }

    /** @test */
    public function itReturnsTheBodyAsAStream()
    {

        $fixture = new JsonFixture(HttpMethod::GET, [], json_encode(['data' => ['foo' => 'bar']]));

        $this->assertInstanceOf(StreamInterface::class, $fixture->getBody());
    }

    /** @test */
    public function itCanReplaceAValueByArrayKey()
    {

        $fixture = new JsonFixture(HttpMethod::GET, [], json_encode(['data' => ['foo' => 'bar']]));

        $fixture['data.foo'] = 'baz';

        $decoded = json_decode($fixture->getBody(), true);

        $this->assertSame([
            'data' => [
                'foo' => 'baz',
            ],
        ], $decoded);
    }

    /** @test */
    public function itCanReturnTheBodyAsAnArray()
    {

        $payload = ['data' => ['foo' => 'bar']];
        $fixture = new JsonFixture(HttpMethod::GET, [], json_encode($payload));

        $this->assertSame($payload, $fixture->asArray());
    }

    /** @test */
    public function itCanForgetAnArrayKey()
    {

        $fixture = new JsonFixture(HttpMethod::GET, [], json_encode(['data' => [
            'foo' => 'bar',
            'baz' => 'qux',
        ]]));

        $fixture->forget('data.foo');
        unset($fixture['data.baz']);

        $decoded = json_decode($fixture->getBody(), true);

        $this->assertSame([
            'data' => [],
        ], $decoded);
    }

    /** @test */
    public function itCanGetASetOfValuesFromTheBody()
    {

        $fixture = new JsonFixture(HttpMethod::GET, [], json_encode([
            'foo' => 'bar',
            'baz' => 'qux',
            'a' => 'b',
        ]));

        $this->assertSame([
            'foo' => 'bar',
            'baz' => 'qux',
        ], $fixture->only(['foo', 'baz']));
    }

    /** @test */
    public function itCanCheckIfTheBodyContainsAKey()
    {

        $fixture = new JsonFixture(HttpMethod::GET, [], json_encode(['data' => ['foo' => 'bar']]));

        $this->assertTrue($fixture->has('data.foo'));
        $this->assertFalse(isset($fixture['data.missing']));
    }
}