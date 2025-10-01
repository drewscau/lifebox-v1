const mix = require('laravel-mix')
const path = require('path')

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix
    .setPublicPath('public')
    .sass(
        path.resolve(__dirname, 'resources/sass/app.scss'),
        path.resolve(__dirname, 'public/css/app.css')
    )
    .scripts([
        'node_modules/jquery/dist/jquery.min.js',
        'node_modules/bootstrap/dist/js/bootstrap.min.js',
        'node_modules/datatables.net/js/jquery.dataTables.min.js',
        'node_modules/datatables.net-buttons/js/dataTables.buttons.min.js',
        'node_modules/datatables.net-responsive/js/dataTables.responsive.min.js',
        'node_modules/sweetalert/dist/sweetalert.min.js',
        // Add new vendor js here
    ], 'public/js/vendor.js')
    .copyDirectory(
        path.resolve(__dirname, 'resources/assets'),
        path.resolve(__dirname, 'public/assets')
    )
    .webpackConfig({
        devServer: {
            watchOptions: {
                poll: true
            },
        },
        output: { chunkFilename: 'js/[name].js?id=[chunkhash]' },
        resolve: {
            alias: {
                ['@assets']: path.resolve(__dirname, 'resources/assets')
            },
        },
    })
    .disableNotifications()

if (process.env.NODE_ENV === 'production') {
    mix.version()
}

mix.options({
    hmrOptions: {
        host: 'localhost',
        port: 8080
    }
})


