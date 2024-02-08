<?php

namespace PunktDe\Codeception\Rest\ActorTraits;

use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\Assert;

trait Rest
{
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

        $parameterArray = [];
        foreach ($parameters->getRows() as $index => $row) {
            $row[1] = $this->convertStringToValue($row[1]);
            $parameterArray[$row[0]] = $row[1];
        }

        $this->send($requestType, $url, $parameterArray);
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
                sprintf('Value of json path %s is not equal expected %s actual %s', $row[0], $row[1], $data[0])
            );
        }
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
        return $value;
    }
}
