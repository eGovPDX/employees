/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your application. See https://github.com/JeffreyWay/laravel-mix.
 |
 */
var proxy = require('./config/proxy.example.js');
 try {
    proxy = require('./config/proxy.js');
 } catch (ex) {

 }
const mix = require('laravel-mix');
const isDev = !mix.inProduction();

/*
 |--------------------------------------------------------------------------
 | Configuration
 |--------------------------------------------------------------------------
 */
mix
	.webpackConfig({
		// Use the jQuery shipped with Drupal to avoid conflicts.
		externals: {
			jquery: 'jQuery',
		},
		devtool: isDev ? 'source-map' : false
	})
	.setPublicPath('assets')
	.disableNotifications()
	.options({
		processCssUrls: false,
	}).sourceMaps(isDev);

/*
 |--------------------------------------------------------------------------
 | Browsersync
 |--------------------------------------------------------------------------
 */
mix.browserSync({
	proxy: proxy.proxy,
	files: ['assets/js/**/*.js', 'assets/css/**/*.css'],
	stream: true,
   socket: {
      domain: proxy.domain,
      port: proxy.port
   },
   open: false
});

/*
 |--------------------------------------------------------------------------
 | SASS
 |--------------------------------------------------------------------------
 */
mix.sass('src/scss/westy.style.scss', 'css', {
  sassOptions: {
    quietDeps: true,
  },
});

/*
 |--------------------------------------------------------------------------
 | JS
 |--------------------------------------------------------------------------
 */
mix.js('src/js/westy.script.js', 'js');
