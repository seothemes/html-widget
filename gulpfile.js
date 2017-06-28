// Require our dependencies.
var	gulp    = require( 'gulp' ),
	notify  = require( 'gulp-notify' ),
	plumber = require( 'gulp-plumber' ),
	sort    = require( 'gulp-sort' ),
	wpPot   = require( 'gulp-wp-pot' );

/**
 * Scan the plugin and create a POT file.
 *
 * https://www.npmjs.com/package/gulp-wp-pot
 */
gulp.task( 'i18n', function() {

	return gulp.src( './*.php' )

	.pipe( plumber( { errorHandler: notify.onError( "Error: <%= error.message %>" ) } ) )

	.pipe( sort() )

	.pipe( wpPot( {
		domain: 'html-widget',
		destFile:'html-widget.pot',
		package: 'HTML Widget',
		bugReport: 'http://github.com/seothemes/html-widget/issues',
		lastTranslator: 'Lee Anthony <seothemeswp@gmail.com>',
		team: 'Seo Themes <seothemeswp@gmail.com>'
	} ) )

	.pipe( gulp.dest( './languages/' ) );

} );
