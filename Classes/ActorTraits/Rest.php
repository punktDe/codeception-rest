<?php

namespace PunktDe\Codeception\Rest\ActorTraits;

use Behat\Gherkin\Node\TableNode;

trait Rest
{
    /**
     * @Given I do a :requestType request on :url
     */
    public function iDoARequestOn(string $requestType, string $url)
    {
        $availableTypes = [
            'get',
            'post'
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
            'post'
        ];
        
        if (!in_array($requestType, $availableTypes)) {
            throw new \Exception('Request type "' . $requestType . '" not yet implemented', 1693489230);
        }

        $parameterArray = [];
        foreach ($parameters->getRows() as $index => $row) {
            $parameterArray[] = [$row[0] => $row[1]];
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
            $this->seeResponseContainsJson([$row[0] => $row[1]]);
        }
    }

    /**
     * @Given the HTTP status code should be :statusCode
     */
    public function theApiResponseStatusCodeShouldBe(int $statusCode)
    {
        $this->seeResponseCodeIs($statusCode);
    }
}