namespace Spatie\LaravelAnalytics;

use Illuminate\Support\ServiceProvider;
use Google_Client;
use Config;

class LaravelAnalyticsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/laravel-analytics.php' => config_path('laravel-analytics.php'),
        ]);
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->bind('Spatie\LaravelAnalytics\LaravelAnalytics', function ($app) {

            $googleApiHelper = $this->getGoogleApiHelperClient();

            $laravelAnalytics = new LaravelAnalytics($googleApiHelper, Config::get('laravel-analytics.siteId'));

            return $laravelAnalytics;
        });

        $this->app->alias('Spatie\LaravelAnalytics\LaravelAnalytics', 'laravelAnalytics');
    }

    /**
     * Return a GoogleApiHelper with given configuration.
     *
     * @return GoogleApiHelper
     *
     * @throws \Exception
     */
    protected function getGoogleApiHelperClient()
    {
        //$this->guardAgainstMissingP12();

        $client = $this->getGoogleClient();

        $googleApiHelper = (new GoogleApiHelper($client, app()->make('Illuminate\Contracts\Cache\Repository')))
            ->setCacheLifeTimeInMinutes(Config::get('laravel-analytics.cacheLifetime'))
            ->setRealTimeCacheLifeTimeInMinutes(Config::get('laravel-analytics.realTimeCacheLifetimeInSeconds'));

        return $googleApiHelper;
    }

    /**
     * Throw exception if .p12 file is not present in specified folder.
     *
     * @throws \Exception
     */
    protected function guardAgainstMissingP12()
    {
        if (!\File::exists(Config::get('laravel-analytics.certificatePath'))) {
            throw new \Exception("Can't find the .p12 certificate in: ".Config::get('laravel-analytics.certificatePath'));
        }
    }

    /**
     * Get a configured GoogleClient.
     *
     * @return Google_Client
     */
    protected function getGoogleClient2()
    {

        $client = new Google_Client(
            [
                'oauth2_client_id' => Config::get('laravel-analytics.clientId'),
                'use_objects' => true,
            ]
        );

        $client->setClassConfig('Google_Cache_File', 'directory', storage_path('app/laravel-analytics-cache'));

        $client->setAccessType('offline');

        $client->setAssertionCredentials(
            new \Google_Auth_AssertionCredentials(
                Config::get('laravel-analytics.serviceEmail'),
                ['https://www.googleapis.com/auth/analytics.readonly'],
                file_get_contents(Config::get('laravel-analytics.certificatePath'))
            )
        );

        return $client;
    }

    protected function getGoogleClient(){
        // Create the client object and set the authorization configuration
        // from the client_secretes.json you downloaded from the developer console.
        $client = new Google_Client();

        $client->setAuthConfigFile(app_path('Lib/client_secret_140806458938-1hjhqlqq2cqle15fj3l531v71le1icli.apps.googleusercontent.com.json'));

        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);

        // If the user has already authorized this app then get an access token
        // else redirect to ask the user to authorize access to Google Analytics.
        if(isset($token) && $token != null){
            $_SESSION['access_token'] = $token;
        }
        if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
            echo $_SESSION['access_token'];
//        if ($token != null) {
            $client->setAccessToken($_SESSION['access_token']);
            return $client;
        } else {
            $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback';
            header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
            exit;
        }

    }


}
