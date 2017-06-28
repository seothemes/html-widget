// Require our dependencies
var autoprefixer = require('autoprefixer');
var browserSync  = require('browser-sync');
var cheerio      = require('gulp-cheerio');
var concat       = require('gulp-concat');
var cssnano      = require('gulp-cssnano');
var del          = require('del');
var gulp         = require('gulp');
var gutil        = require('gulp-util');
var mqpacker     = require('css-mqpacker');
var notify       = require('gulp-notify');
var plumber      = require('gulp-plumber');
var postcss      = require('gulp-postcss');
var reload       = browserSync.reload;
var rename       = require('gulp-rename');
var sass         = require('gulp-sass');
var sassLint     = require('gulp-sass-lint');
var sort         = require('gulp-sort');
var sourcemaps   = require('gulp-sourcemaps');
var uglify       = require('gulp-uglify');
var wpPot        = require('gulp-wp-pot');

// Set assets paths.
var paths = {
    css:     './assets/styles/min/',
    sass:    './assets/styles/*.scss',
    concat:  './assets/scripts/*.js',
    scripts: './assets/scripts/min/',
    php:     './*.php'
};

/**
 * Handle errors and alert the user.
 */
function handleErrors() {
    var args = Array.prototype.slice.call(arguments);

    notify.onError({
        title: 'Task Failed [<%= error.message %>',
        message: 'See console.',
        sound: 'Sosumi' // See: https://github.com/mikaelbr/node-notifier#all-notification-options-with-their-defaults
    }).apply(this, args);

    gutil.beep(); // Beep 'sosumi' again

    // Prevent the 'watch' task from stopping
    this.emit('end');
}

/**
 * Delete style.css and style.min.css before we minify and optimize
 */
gulp.task('clean:styles', function() {
    return del('./assets/styles/min/styles.min.css')
});

/**
 * Compile Sass and run stylesheet through PostCSS.
 *
 * https://www.npmjs.com/package/gulp-sass
 * https://www.npmjs.com/package/gulp-postcss
 * https://www.npmjs.com/package/gulp-autoprefixer
 * https://www.npmjs.com/package/css-mqpacker
 */
gulp.task('styles', ['clean:styles'], function() {
    return gulp.src(paths.sass)

        // Deal with errors.
        .pipe(plumber({
            errorHandler: handleErrors
        }))

        // Wrap tasks in a sourcemap.
        .pipe(sourcemaps.init())

        // Compile Sass using LibSass.
        .pipe(sass({
            errLogToConsole: true,
            outputStyle: 'expanded' // Options: nested, expanded, compact, compressed
        }))

        // Parse with PostCSS plugins.
        .pipe(postcss([
            autoprefixer({
                browsers: ['last 2 version']
            }),
            mqpacker({
                sort: true
            }),
        ]))

		// Minify and optimize.
        .pipe(cssnano({
            safe: true // Use safe optimizations.
        }))
        .pipe(rename('styles.min.css'))

        // Create sourcemap.
        .pipe(sourcemaps.write())

        // Create style.css.
        .pipe(gulp.dest(paths.css))
        .pipe(browserSync.stream());
});

/**
 * Sass linting
 *
 * https://www.npmjs.com/package/sass-lint
 */
gulp.task('sass:lint', ['postcss'], function() {
    gulp.src(paths.sass)
        .pipe(sassLint())
        .pipe(sassLint.format())
        .pipe(sassLint.failOnError());
});

/**
 * Delete scripts before we concat and minify
 */
gulp.task('clean:scripts', function() {
    return del('./assets/scripts/min/scripts.min.js');
});

/**
 * Concatenate javascripts after they're clobbered.
 * https://www.npmjs.com/package/gulp-concat
 */
gulp.task('scripts', ['clean:scripts'], function() {
    return gulp.src(paths.concat)
        .pipe(plumber({
            errorHandler: handleErrors
        }))
        .pipe(sourcemaps.init())
        .pipe(concat('scripts.min.js'))
		.pipe(uglify({
            mangle: false
        }))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest(paths.scripts))
        .pipe(browserSync.stream());
});

/**
 * Delete the theme's .pot before we create a new one
 */
gulp.task('clean:pot', function() {
    return del(['languages/html-widget.pot']);
});

/**
 * Scan the theme and create a POT file.
 *
 * https://www.npmjs.com/package/gulp-wp-pot
 */
gulp.task('i18n', ['clean:pot'], function() {
    return gulp.src(paths.php)
        .pipe(plumber({
            errorHandler: handleErrors
        }))
        .pipe(sort())
        .pipe(wpPot({
            domain: 'html-widget',
            destFile: 'html-widget.pot',
            package: 'html-widget',
            bugReport: 'http://github.com/seothemes/html-widget',
            lastTranslator: 'Lee Anthony <seothemeswp@gmail.com>',
            team: 'SEO Themes <seothemeswp@gmail.com>'
        }))
        .pipe(gulp.dest('languages/'));
});

/**
 * Process tasks and reload browsers on file changes.
 *
 * https://www.npmjs.com/package/browser-sync
 */
gulp.task('watch', function() {

    // Kick off BrowserSync.
    browserSync({
        open: false, // Open project in a new tab?
        injectChanges: true, // Auto inject changes instead of full reload
        proxy: "html-widget.dev", // Use http://html-widget.dev:3000 to use BrowserSync
        watchOptions: {
            debounceDelay: 1000 // Wait 1 second before injecting
        }
    });

    // Run tasks when files change.
    gulp.watch(paths.sass, ['styles']);
    gulp.watch(paths.scripts, ['scripts']);
    gulp.watch(paths.concat, ['scripts']);
});

/**
 * Create indivdual tasks.
 */
gulp.task('default', ['i18n', 'styles', 'scripts']);
