<?php namespace Illuminate\Routing;

use Illuminate\Support\ServiceProvider;

class RoutingServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerRouter();

		$this->registerUrlGenerator();

		$this->registerRedirector();

		$this->registerResponseFactory();
	}

	/**
	 * Register the router instance.
	 *
	 * @return void
	 */
	protected function registerRouter()
	{
		$this->app['router'] = $this->app->share(function($app)
		{
			$router = new Router($app['events'], $app);

			// If the current application environment is "testing", we will disable the
			// routing filters, since they can be tested independently of the routes
			// and just get in the way of our typical controller testing concerns.
			if ($app['env'] == 'testing')
			{
				$router->disableFilters();
			}

			return $router;
		});
	}

	/**
	 * Register the URL generator service.
	 *
	 * @return void
	 */
	protected function registerUrlGenerator()
	{
		$this->app['url'] = $this->app->share(function($app)
		{
			// The URL generator needs the route collection that exists on the router.
			// Keep in mind this is an object, so we're passing by references here
			// and all the registered routes will be available to the generator.
			$routes = $app['router']->getRoutes();

			$app->instance('routes', $routes);

			$url = new UrlGenerator($routes, $app->rebinding('request', function($app, $request)
			{
				$app['url']->setRequest($request);
			}));

			// If the route collection is "rebound", for example, when the routes stay
			// cached for the application, we will need to rebind the routes on the
			// URL generator instance so it has the latest version of the routes.
			$app->rebinding('routes', function($app, $routes)
			{
				$app['url']->setRoutes($routes);
			});

			return $url;
		});
	}

	/**
	 * Register the Redirector service.
	 *
	 * @return void
	 */
	protected function registerRedirector()
	{
		$this->app['redirect'] = $this->app->share(function($app)
		{
			$redirector = new Redirector($app['url']);

			// If the session is set on the application instance, we'll inject it into
			// the redirector instance. This allows the redirect responses to allow
			// for the quite convenient "with" methods that flash to the session.
			if (isset($app['session.store']))
			{
				$redirector->setSession($app['session.store']);
			}

			return $redirector;
		});
	}

	/**
	 * Register the response factory implementation.
	 *
	 * @return void
	 */
	protected function registerResponseFactory()
	{
		$this->app->bindShared('Illuminate\Contracts\Routing\ResponseFactory', function($app)
		{
			return new ResponseFactory($app['Illuminate\Contracts\View\Factory'], $app['redirect']);
		});
	}

}
