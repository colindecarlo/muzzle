<?php

namespace Muzzle\Messages;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Muzzle\CliFormatter;
use PHPUnit\Framework\Assert as PHPUnit;

trait ContentAssertions
{

    use ArraySubsetAsserts;

    /**
     * Gets the body of the message.
     *
     * @return \Psr\Http\Message\StreamInterface Returns the body as a stream.
     */
    abstract public function getBody();

    /**
     * Decodes a JSON body to an array.
     *
     * @return array
     */
    abstract public function decode() : array;

    /**
     * Assert that the given string is contained within the response.
     *
     * @param  string $value
     * @return $this
     */
    public function assertSee($value) : self
    {

        PHPUnit::assertStringContainsString($value, (string) $this->getBody());

        return $this;
    }

    /**
     * Assert that the given string is contained within the response text.
     *
     * @param  string $value
     * @return $this
     */
    public function assertSeeText($value) : self
    {

        PHPUnit::assertStringContainsString($value, strip_tags((string) $this->getBody()));

        return $this;
    }

    /**
     * Assert that the given string is not contained within the response.
     *
     * @param  string $value
     * @return $this
     */
    public function assertDoNotSee($value) : self
    {

        PHPUnit::assertStringNotContainsString($value, (string) $this->getBody());

        return $this;
    }

    /**
     * Assert that the given string is not contained within the response text.
     *
     * @param  string $value
     * @return $this
     */
    public function assertDoNotSeeText($value) : self
    {

        PHPUnit::assertStringNotContainsString($value, strip_tags((string) $this->getBody()));

        return $this;
    }

    /**
     * Assert that the response is a superset of the given JSON.
     *
     * @param  array $data
     * @return $this
     */
    public function assertJson(array $data) : self
    {

        static::assertArraySubset(
            $data,
            $this->decode(),
            false,
            $this->assertJsonMessage($data)
        );

        return $this;
    }

    /**
     * Get the assertion message for assertJson.
     *
     * @param  array $data
     * @return string
     */
    protected function assertJsonMessage(array $data) : string
    {

        $expected = CliFormatter::format($data);

        $actual = CliFormatter::format($this->decode());

        return 'Unable to find JSON: ' . PHP_EOL . PHP_EOL .
               "[{$expected}]" . PHP_EOL . PHP_EOL .
               'within response JSON:' . PHP_EOL . PHP_EOL .
               "[{$actual}]." . PHP_EOL . PHP_EOL;
    }

    /**
     * Assert that the response has the exact given JSON.
     *
     * @param  array $data
     * @return $this
     */
    public function assertExactJson(array $data) : self
    {

        $actual = json_encode(Arr::sortRecursive((array) $this->decode()));

        PHPUnit::assertEquals(json_encode(Arr::sortRecursive($data)), $actual);

        return $this;
    }

    /**
     * Assert that the response contains the given JSON fragment.
     *
     * @param  array $data
     * @return $this
     */
    public function assertJsonFragment(array $data) : self
    {

        $actual = json_encode(Arr::sortRecursive((array) $this->decode()));

        foreach (Arr::sortRecursive($data) as $key => $value) {
            $expected = substr(json_encode([$key => $value]), 1, -1);

            PHPUnit::assertTrue(
                Str::contains($actual, $expected),
                'Unable to find JSON fragment: ' . PHP_EOL . PHP_EOL .
                "[{$expected}]" . PHP_EOL . PHP_EOL .
                'within' . PHP_EOL . PHP_EOL .
                "[{$actual}]."
            );
        }

        return $this;
    }

    /**
     * Assert that the response does not contain the given JSON fragment.
     *
     * @param  array $data
     * @return $this
     */
    public function assertJsonMissing(array $data) : self
    {

        $actual = json_encode(Arr::sortRecursive((array) $this->decode()));

        foreach (Arr::sortRecursive($data) as $key => $value) {
            $expected = substr(json_encode([$key => $value]), 1, -1);

            PHPUnit::assertFalse(
                Str::contains($actual, $expected),
                'Found unexpected JSON fragment: ' . PHP_EOL . PHP_EOL .
                "[{$expected}]" . PHP_EOL . PHP_EOL .
                'within' . PHP_EOL . PHP_EOL .
                "[{$actual}]."
            );
        }

        return $this;
    }

    /**
     * Assert that the response has a given JSON structure.
     *
     * @param  array|null $structure
     * @param  array|null $responseData
     * @return $this
     */
    public function assertJsonStructure(array $structure = null, array $responseData = null) : self
    {

        if (is_null($structure)) {
            return $this->assertJson($this->json());
        }

        if (is_null($responseData)) {
            $responseData = $this->decode();
        }

        foreach ($structure as $key => $value) {
            if (is_array($value) && $key === '*') {
                PHPUnit::assertIsArray($responseData);

                foreach ($responseData as $responseDataItem) {
                    $this->assertJsonStructure($structure['*'], $responseDataItem);
                }
            } elseif (is_array($value)) {
                PHPUnit::assertArrayHasKey($key, $responseData, sprintf(
                    'Could not find key [%s] within data subset: %s',
                    $key,
                    CliFormatter::format($responseData)
                ));

                $this->assertJsonStructure($structure[$key], $responseData[$key]);
            } else {
                PHPUnit::assertArrayHasKey($value, $responseData, sprintf(
                    'Could not find key [%s] within data subset: %s',
                    $value,
                    CliFormatter::format($responseData)
                ));
            }
        }

        return $this;
    }

    /**
     * @param string|\Psr\Http\Message\StreamInterface $body
     * @return $this
     */
    public function assertBodyEquals($body) : self
    {

        $body = (string) $body;
        if ($body !== '') {
            PHPUnit::assertEquals($body, (string) $this->getBody());
        }

        return $this;
    }

    /**
     * Return the decoded response JSON.
     *
     * @return array
     */
    public function json() : array
    {

        return $this->decode();
    }

    /**
     * Dump the content from the response.
     *
     * @return void
     * @codeCoverageIgnore
     */
    public function dump() : void
    {

        dd($this->isJson() ? $this->decode() : (string) $this->getBody());
    }
}
