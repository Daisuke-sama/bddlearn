<?php

use Behat\ {
    Behat\Context\Context,
    Gherkin\Node\PyStringNode,
    Gherkin\Node\TableNode
};

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    /**
     * @var
     */
    protected $response = null;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @Given I am an anonymous user
     */
    public function iAmAnAnonymousUser()
    {
        return true;
    }

    /**
     * @When I search for :arg1
     */
    public function iSearchFor($arg1)
    {
        $client = new GuzzleHttp\Client(['base_uri' => 'https://api.github.com']);
        $this->response = $client->get('/search/repositories?q=' . $arg1);
    }

    /**
     * @Then I expect :arg1 response code
     */
    public function iExpectResponseCode($arg1)
    {
        $response_code = $this->response->getStatusCode();
        if ($response_code <> $arg1) {
            throw new Exception("It didn't work. We expected a $arg1 response, but got a " . $response_code);
        }
    }

    /**
     * @Then I expect at least :arg1 result
     */
    public function iExpectAtLeastResult($arg1)
    {
        $data = json_decode($this->response->getBody(), true);
        if ($data['total_count'] < $arg1) {
            throw new \Exception("We expected $arg1 result(s), but found {$data['total_count']} results.");
        }
    }

}
