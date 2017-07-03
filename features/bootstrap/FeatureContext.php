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
    /*-----------------------
      ---------VARS----------
      -----------------------*/
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
     * @var array
     */
    protected $table = null;


    /*------------------------
      ---------FUNCS----------
      ------------------------*/
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

        $this->iExpectedASuccessfulRequest();
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

        if (!isset($repositories['name'])) {
            foreach ($repositories as $repository) {
                if ($repository['name'] == $arg1) {
                    return true;
                }
            }
        } else if ($repositories['name'] == $arg1) {
            return true;
        }


        throw new Exception("The repo $arg1 didn't find in my repositories.");
    }

    /**
     * @When I create the :arg1 repository
     */
    public function iCreateTheRepository($arg1)
    {
        $parameters = json_encode(['name' => $arg1]);

        $this->response = $this->client->post('/user/repos', ['body' => $parameters]);

        $this->iExpectedASuccessfulRequest();
    }

    /**
     * @Given I have a repository called :arg1
     */
    public function iHaveARepositoryCalled($arg1)
    {
        $this->iCreateTheRepository($arg1);
        $this->theResultShouldIncludeARepositoryName($arg1);
    }

    /**
     * @When I watch the :arg1 repository
     */
    public function iWatchTheRepository($arg1)
    {
        $watch_url = '/repos/' . $this->username . '/' . $arg1 . '/subscription';
        $parameters = json_encode(['subscribed' => 'true']);

        $this->response = $this->client->put($watch_url, ['body' => $parameters]);

        $this->iExpectedASuccessfulRequest();
    }

    /**
     * @Then The :arg1 repository will list me as a watcher
     */
    public function theRepositoryWillListMeAsAWatcher($arg1)
    {
        // TODO: need refactor for this as we have some duplicate code with other function
        $watch_url = '/repos/' . $this->username . '/' . $arg1 . '/subscribers';
        $this->response = $this->client->get($watch_url);

        $subscribers = $this->getBodyAsArrayFromJson();

        foreach ($subscribers as $subscriber) {
            if (strcmp($subscriber['login'], $this->username) == 0) {
                return true;
            }
        }

        throw new Exception("Did not find '{$this->username}' in watcher list");
    }

    /**
     * @Then I delete repository called :arg1
     */
    public function iDeleteRepositoryCalled($arg1)
    {
        $delete_url = '/repos/' . $this->username . '/' . $arg1;
        $this->response = $this->client->delete($delete_url);

        $this->iExpectedASuccessfulRequest();
    }

    /**
     * @Given I have a following repositories:
     */
    public function iHaveAFollowingRepositories(TableNode $table)
    {
        $this->table = $table->getTable();

        array_shift($this->table);

        foreach ($this->table as $id => $row) {
            $this->table[$id]['name'] = $row[0] . '/' . $row[1];

            $this->response = $this->client->get('/repos/' . $row[0] . '/' . $row[1]);

            $this->iExpectedASuccessfulRequest();
        }
    }

    /**
     * @When I watch this repositories
     */
    public function iWatchThisRepositories()
    {
        $parameters = json_encode(['subscribed' => 'true']);

        foreach ($this->table as $row) {
            $watch_url = '/repos/' . $row['name'] . '/subscription';
            $this->client->put($watch_url, ['body' => $parameters]);
        }
    }

    /**
     * @Then My watch list includes those repositories
     */
    public function myWatchListIncludesThoseRepositories()
    {
        $watch_url = '/repos/' . $this->username . '/subscription';
        $this->response = $this->client->get($watch_url);

        $watches = $this->getBodyAsArrayFromJson();

        foreach ($this->table as $row) {
            foreach ($watches as $watch) {
                if ($row['name'] == $watch['full_name']) {
                    break 2;
                }
            }

            throw new Exception('Error! ' . $this->username . ' is not watchig ' . $row['name']);
        }
    }



    /*-------------------------
      ---------HIDDEN----------
      -------------------------*/
    /**
     * @return mixed Returns body of response decoded
     */
    protected function getBodyAsArrayFromJson()
    {
        return json_decode($this->response->getBody(), true);
    }

    protected function iExpectedASuccessfulRequest()
    {
        $c = $this->response->getStatusCode();
        switch ($c) {
            case 200 :
            case 201 :
            case 204 : {
                return true;
            }
        }

        throw new Exception("Didn't receive a success, but $c");
    }

    protected function iExpectedAFailedRequest()
    {
        $c = $this->response->getStatusCode();
        switch ($c) {
            case 400 :
            case 401 :
            case 403 :
            case 404 :
            case 405 : {
                return true;
            }
        }

        throw new Exception("Didn't receive a fail, but $c");
    }
}
