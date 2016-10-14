<?php
// Routes

$app->post('/', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("'/' route");

    $params = $request->getParsedBody();

    $scraper = new Scraper($params['rootUrl'], $params['sitemapUrl']);

    // Render index view
    return $response->withJson($scraper);
});

$app->post('/scrape', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("'/scrape' route");

    $params = $request->getParsedBody();

    $scraper = new Scraper($params['rootUrl'], $params['sitemapUrl'], false, false, $this->logger);
    $scraper->scrape();

    // Render index view
    return $response->withJson($scraper);
});

$app->post('/commit', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("'/commit' route");

    $params = $request->getParsedBody();

	$repo = false;

	try {
		$repo = Git::open($params['path']);
	} catch (Exception $e) {
		$this->logger->addInfo('Not a repo');
	}

	if (!$repo) {
		$repo = Git::create($params['path']);
	}

	$repo->add('.');

	try {
		$repo->commit(time());
	} catch (Exception $e) {
		return $response->withJson(array('error'=>"Failed to commit"));
	}

    // Render index view
    return $response->withJson($repo->status());
});

$app->post('/checkout', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("'/checkout' route");

    $params = $request->getParsedBody();

	$repo = false;

	try {
		$repo = Git::open($params['path']);
	} catch (Exception $e) {
		$this->logger->addInfo('Not a repo');
	}

	if (!$repo) {
		$repo = Git::create($params['path']);
	}

	try {
		$repo->checkout($params['branch']);
	} catch (Exception $e) {
		$repo->create_branch($params['branch']);
		$repo->checkout($params['branch']);
	}

    // Render index view
    return $response->withJson($repo->status());
});