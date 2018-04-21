module.exports = function(grunt) {
	var bowerPath = grunt.file.readJSON('./.bowerrc').directory;

	grunt.initConfig({
		bower: {install: { options: { verbose: true }}},
		modernizr: {
			dist: {
				dest: bowerPath + '/modernizr/dist/modernizr-build.min.js',
				tests: [
					[
						"todataurljpeg",
						"todataurlpng",
						"todataurlwebp"
					],
					"cssanimations",
					"bgpositionshorthand",
					"bgpositionxy",
					[
						"bgrepeatspace",
						"bgrepeatround"
					],
					"backgroundsize",
					"bgsizecover",
					"borderradius",
					"csscalc",
					"flexboxtweener",
					"fontface",
					"multiplebgs",
					"csstransforms",
					"csstransforms3d",
					"preserve3d",
					"csstransitions",
					"localstorage",
					"sessionstorage",
					"svgclippaths",
					"svgfilters",
					"svgforeignobject",
					"inlinesvg",
					"smil"
				],
				options: [
					"setClasses"
				],
				parseFiles: false,
				crawl: false
			}
		}
	});

	grunt.registerTask('prefix-css', function() {
		var cb = this.async(), fs = require('fs');

		console.info('Working...');

		var prefix = '#app',
			prefixedPath = __dirname + '/web/assets/theme_prefixed/';
		require('async').each([
			{
				from: __dirname + '/web/vendor/bootstrap//dist/css/bootstrap.css',
				to: prefixedPath + 'bootstrap.css'
			},
			{
				from: __dirname + '/web/assets/theme/default/css/app.css',
				to: prefixedPath + 'default/css/app.css'
			},
			{
				from: __dirname + '/web/assets/theme/default/css/proxy.css',
				to: prefixedPath + 'default/css/proxy.css'
			},
			{
				from: __dirname + '/web/assets/theme/centric/css/app.css',
				to: prefixedPath + 'centric/css/app.css'
			}
		], function(path, cb) {
			console.info(path.from + ' processing...');

			// Preprocessing
			var content = fs.readFileSync(path.from).toString();
			content = content
				// workaround
				.replace(/,\s*\n/g, ', ');

			// Push content to file
			var directory = require('path').dirname(path.to);
			if (!fs.existsSync(directory)) {
				require('fs-extra').ensureDirSync(directory);
			}
			fs.writeFileSync(path.to, content);

			require('child_process').exec(
				[__dirname + '/node_modules/.bin/prefix-css', prefix, path.to].join(' '),
				{ maxBuffer: Infinity },
				function (err, stdout, stderr) {
					if (stderr) {
						return cb(stderr);
					}

					console.info(path.from + ' has been processed');
					fs.writeFileSync(path.to, stdout);

					// Post processing
					var content = fs.readFileSync(path.to).toString();
					content = content
						// shift html context
						.replace(/\s+(html)/g, '')
						.replace(/\s+(body)/g, ' .content-wrapper')
					;
					fs.writeFileSync(path.to, content);

					cb();
				});
		}, function(err) {
			cb(err ? new Error(err) : true);
		});
	});

	grunt.loadNpmTasks('grunt-bower-task');
	grunt.loadNpmTasks("grunt-modernizr");
	grunt.loadNpmTasks('grunt-exec');

	grunt.registerTask('default', ['bower:install', 'modernizr:dist', 'prefix-css']);
};