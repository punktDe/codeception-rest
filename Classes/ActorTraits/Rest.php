<?php

namespace PunktDe\Codeception\Rest\ActorTraits;

use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\Assert;

trait Rest
{
    /**
     * Normalize a possibly plain path to a JSONPath understood by Codeception
     */
    protected function normalizeJsonPath(string $path): string
    {
        $path = trim($path);
        if ($path === '$' || strpos($path, '$') === 0) {
            return $path;
        }
        // allow top-level key without dot
        return '$.' . ltrim($path, '.');
    }
    /**
     * @Given I do a :requestType request on :url
     */
    public function iDoARequestOn(string $requestType, string $url)
    {
        $availableTypes = [
            'get',
            'post',
            'delete',
            'patch'
        ];

        if (!in_array($requestType, $availableTypes)) {
            throw new \Exception('Request type "' . $requestType . '" not yet implemented', 1693489226);
        }
        $this->send($requestType, $url);
    }

    /**
     * by adding a parameter starting with "$_FILES.", it is possible to upload files
     *
     * @Given I do a :requestType request on :url with parameters
     */
    public function iDoARequestOnWithParameters(string $requestType, string $url, TableNode $parameters)
    {
        $availableTypes = [
            'get',
            'post',
            'delete',
            'patch'
        ];

        if (!in_array($requestType, $availableTypes)) {
            throw new \Exception('Request type "' . $requestType . '" not yet implemented', 1693489230);
        }

        $files = [];
        foreach ($parameters->getRows() as $index => $row) {
            if (strncmp($row[0], '$_FILES.', strlen('$_FILES.')) === 0) {
                $row[0] = substr($row[0], strlen('$_FILES.'));
                $files[] = [$row[0] => $row[1]];

            } else {
                $row[1] = $this->convertStringToValue($row[1]);
                $parameterArray[$row[0]] = $row[1];
            }
        }
        if (count($files) > 0) {
            $this->deleteHeader('Content-Type');
        }
        $this->send($requestType, $url, $parameterArray, $files);
    }

    /**
     * @Given the api response should be valid json
     */
    public function theApiResponseIsValidJson()
    {
        $this->seeResponseIsJson();
    }

    /**
     * @Given the api response should return a JSON string with fields
     */
    public function theApiResponseShouldReturnJsonStringWithFields(TableNode $table)
    {
        foreach ($table->getRows() as $index => $row) {
            $row[1] = $this->convertStringToValue($row[1]);
            $this->seeResponseContainsJson([$row[0] => $row[1]]);
        }
    }

    /**
     * @Given the HTTP status code should be :statusCode
     */
    public function theApiResponseStatusCodeShouldBe(string $statusCode)
    {
        $this->seeResponseCodeIs((int)$statusCode);
    }


    /**
     * @Given the api response json path :jsonPath equals :value
     */
    public function theApiResponseJsonPathFieldIsEqual(string $jsonPath, string $value)
    {
        $data = $this->grabDataFromResponseByJsonPath($jsonPath);
        $value = $this->convertStringToValue($value);
        Assert::assertEquals(
            $value,
            $data[0],
            sprintf('Value of json path %s is not equal expected %s actual %s', $jsonPath, $value, $data[0])
        );
    }


    /**
     * @Given the api response json path :jsonPath does not equal :value
     */
    public function theApiResponseXpathNotEquals(string $jsonPath, string $value)
    {
        $this->dontSeeResponseJsonMatchesJsonPath($jsonPath, $value);
    }


    /**
     * @Given the api response should contain headers
     */
    public function theApiResponseShouldContainHeaders(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            $this->seeHttpHeader($row[0], $row[1]);
        }
    }


    /**
     * @Given the api response equals :value
     */
    public function theApiResponseEquals(string $value)
    {
        $actual = $this->grabResponse();
        Assert::assertEquals(
            $value,
            $actual,
            sprintf('Value of content is not equal expected %s actual %s', $value, $actual)
        );
    }

     /**
     * @Given the api response should return a JSON string with json path
     */
    public function theApiResponseShouldReturnStringWithJsonPath(TableNode $table)
    {
        foreach ($table->getRows() as $index => $row) {
            $data = $this->grabDataFromResponseByJsonPath($row[0]);

            $row[1] = $this->convertStringToValue($row[1]);

            Assert::assertEquals(
                $row[1],
                $data[0],
                 sprintf(
                    'Value of json path %s is not equal expected %s actual %s',
                    $row[0],
                    var_export($row[1], true),
                    var_export($data[0], true)
                )
            );
        }
    }


    /**
     * @And I do not see :text in response
     * @Given I do not see :text in response
     */
    public function iDontSeeResponseContainsText(string $text)
    {
        $this->dontSeeResponseContains($text);
    }

    /**
     * @And I see :text in response
     * @Given I see :text in response
     */
    public function iSeeResponseContainsText(string $text)
    {
        $this->seeResponseContains($text);
    }


    /**
     * @param string $value
     * @return mixed
     */
    protected function convertStringToValue(string $value): mixed
    {
        $value = $value === 'true' ? true : $value;
        $value = $value === 'false' ? false : $value;
        $value = $value === 'null' ? null : $value;
        $value = $value === '""' ? "" : $value;
        $value = $value === '[]' ? [] : $value;
        return $value;
    }


    /**
     * @Then  /^the api response contains arrays with length of$/
     */
    public function apiResponseContainsArraysWithLengthOf(TableNode $lengthsTable)
    {
        foreach ($lengthsTable->getRows() as $row) {
            $path = $row[0];
            $len = $row[1];
            $jsonPath = $this->normalizeJsonPath($path);
            $matches = $this->grabDataFromResponseByJsonPath($jsonPath);
            Assert::assertNotEmpty($matches, sprintf('Path %s did not match any value in response', $jsonPath));
            $array = $matches[0];
            Assert::assertIsArray($array, sprintf('Value at %s is not an array', $jsonPath));
            Assert::assertCount((int)$len, $array, sprintf('Array at %s does not have expected length %d, actual %d', $jsonPath, (int)$len, is_array($array) ? count($array) : -1));
        }
    }

    /**
     * @Then the api response array in json path :jsonPath with subpath :subPath equals :value
     */
    public function apiResponseArrayInJsonPathWithSubpathEquals(string $jsonPath, string $subPath, string $value)
    {
        $this->assertArrayHasItemWithSubpathValue($jsonPath, $subPath, $this->convertStringToValue($value), true);
    }

    /**
     * @Then the api response array in json path :jsonPath with subpath :subPath does not equal :value
     */
    public function apiResponseArrayInJsonPathWithSubpathDoesNotEqual(string $jsonPath, string $subPath, string $value)
    {
        $this->assertArrayHasItemWithSubpathValue($jsonPath, $subPath, $this->convertStringToValue($value), false);
    }

    /**
     * @Then  /^the api response should return a JSON string with json path arrays containing strings$/
     */
    public function apiResponseShouldReturnJsonStringWithJsonPathArraysContainingStrings(TableNode $valuesTable)
    {
        foreach ($valuesTable->getRows() as $row) {
            $path = $row[0];
            $subPath = $row[1];
            $expected = $row[2];
            $this->assertArrayHasItemWithSubpathValue($path, $subPath, $this->convertStringToValue($expected), true);
        }
    }

    /**
     * @Then the api response contains an array at json path :jsonPath with length :length
     */
    public function apiResponseContainsArrayAtJsonPathWithLength(string $jsonPath, string $length)
    {
        $jsonPath = $this->normalizeJsonPath($jsonPath);
        $matches = $this->grabDataFromResponseByJsonPath($jsonPath);
        Assert::assertNotEmpty($matches, sprintf('Path %s did not match any value in response', $jsonPath));
        $array = $matches[0];
        Assert::assertIsArray($array, sprintf('Value at %s is not an array', $jsonPath));
        Assert::assertCount((int)$length, $array, sprintf('Array at %s does not have expected length %d, actual %d', $jsonPath, (int)$length, is_array($array) ? count($array) : -1));
    }

    /**
     * @Then the api response in json path :jsonPath is empty
     */
    public function apiResponseJsonPathIsEmpty(string $jsonPath)
    {
        $normalized = $this->normalizeJsonPath($jsonPath);

        // determine parent path
        $parent = '$';
        $path = ltrim($normalized, '$.');
        if (strpos($path, '.') !== false) {
            $parent = '$.' . substr($path, 0, strrpos($path, '.'));
        }

        $parentMatches = $this->grabDataFromResponseByJsonPath($parent);
        Assert::assertNotEmpty($parentMatches, sprintf('Parent path %s does not exist', $parent));

        $matches = $this->grabDataFromResponseByJsonPath($normalized);
        if (empty($matches)) {
            // treat non-existing as empty when parent exists
            Assert::assertTrue(true);
            return;
        }
        $value = $matches[0];
        $isEmpty = ($value === null) || ($value === '') || (is_array($value) && count($value) === 0);
        Assert::assertTrue($isEmpty, sprintf('Failed asserting that path "%s" is empty. Actual: %s', $normalized, is_array($value) ? 'Array[' . count($value) . ']' : var_export($value, true)));
    }

    /**
     * Helper to assert item with subpath/value exists or not in array at jsonPath
     */
    protected function assertArrayHasItemWithSubpathValue(string $jsonPath, string $subPath, $expectedValue, bool $shouldExist)
    {
        $jsonPath = $this->normalizeJsonPath($jsonPath);
        $matches = $this->grabDataFromResponseByJsonPath($jsonPath);
        Assert::assertNotEmpty($matches, sprintf('Path %s did not match any value in response', $jsonPath));
        $array = $matches[0];
        Assert::assertIsArray($array, sprintf('Value at %s is not an array', $jsonPath));

        $found = false;
        foreach ($array as $item) {
            if (is_array($item) && array_key_exists($subPath, $item) && $item[$subPath] == $expectedValue) {
                $found = true;
                break;
            }
        }

        if ($shouldExist) {
            Assert::assertTrue($found, sprintf('Array at %s does not contain an item with %s == %s', $jsonPath, $subPath, var_export($expectedValue, true)));
        } else {
            Assert::assertFalse($found, sprintf('Array at %s unexpectedly contains an item with %s == %s', $jsonPath, $subPath, var_export($expectedValue, true)));
        }
    }

}
