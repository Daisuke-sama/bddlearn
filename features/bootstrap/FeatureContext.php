<?php

use Behat\ {
    Behat\Context\Context,
    Gherkin\Node\PyStringNode,
    Gherkin\Node\TableNode
};

/**
 * Defines some of Github API features and testing these.
 */
class FeatureContext implements Context
{
    /**
     * @var Psr\Http\Message\ResponseInterface
     */
    protected $response = null;

    /**
     * @var string
     */
    protected $username = null;

    /**
     * @var string
     */
    protected $password = null;

    /**
     * @var GuzzleHttp\Client
     */
    protected $client = null;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct($github_username, $github_password)
    {
        $this->username = $github_username;
        $this->password = $github_password;
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
        $data = $this->getBodyAsArrayFromJson();
        if ($data['total_count'] < $arg1) {
            throw new \Exception("We expected $arg1 result(s), but found {$data['total_count']} results.");
        }
    }


    /**
     * @Given I am an authenticated user
     */
    public function iAmAnAuthenticatedUser()
    {
        $this->client = new GuzzleHttp\Client([
            'base_uri' => 'https://api.github.com',
            'auth' => [$this->username, $this->password]
        ]);
        $this->response = $this->client->get('/');

        $this->iExpectResponseCode(200);
    }

    /**
     * @When I request a list of my repositories
     */
    public function iRequestAListOfMyRepositories()
    {
        $this->response = $this->client->get('user/repos');

        $this->iExpectResponseCode(200);
    }

    /**
     * @Then The result should include a repository name :arg1
     */
    public function theResultShouldIncludeARepositoryName($arg1)
    {
        $repositories = $this->getBodyAsArrayFromJson();

        foreach ($repositories as $repository) {
            if (($repository['name'] <=> $arg1) == 0) {
                return true;
            }
        }

        throw new Exception("The repo $arg1 didn't find in my repositories.");
    }

    /**
     * @return mixed Returns body of response decoded
     */
    protected function getBodyAsArrayFromJson()
    {
        return json_decode($this->response->getBody(), true);
    }
}
