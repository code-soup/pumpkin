/**
 * Webpack development server configuration
 *
 * Environment variables
 * ---------------------
 * WP_DEV_URL     → URL of the WordPress instance that webpack-dev-server should proxy requests TO.
 * DEV_PROXY_URL  → URL (protocol://host:port) where webpack-dev-server itself should listen.
 * DEV_PROXY_PORT → Optional convenience override for the port portion of DEV_PROXY_URL.
 */
import { URL } from "url";
import { parseUrl } from "../util/url.js";


// -----------------------------------------------------------------------------
// Resolve target WordPress URL (proxy target)
// -----------------------------------------------------------------------------
const wpTargetInfo = process.env.WP_DEV_URL
	? parseUrl(process.env.WP_DEV_URL)
	: null;

// -----------------------------------------------------------------------------
// Resolve webpack dev-server host / port
// -----------------------------------------------------------------------------
const DEFAULT_DEV_SERVER = "http://localhost:8080";

let rawDevServerUrl = process.env.DEV_PROXY_URL || DEFAULT_DEV_SERVER;

// (9) Allow DEV_PROXY_PORT to override the port portion *before* parsing
if (process.env.DEV_PROXY_PORT) {
	try {
		const tmp = new URL(rawDevServerUrl);
		tmp.port = String(process.env.DEV_PROXY_PORT);
		rawDevServerUrl = tmp.toString();
	} catch {
		// The parseUrl() call below will handle the invalid URL and exit.
	}
}

const devServerInfo = parseUrl(rawDevServerUrl);

export default (config, env) => {
	// (7) Compute whether SSL verification should be ignored for the proxy target
	const ignoreSSLErrors = wpTargetInfo?.isHttps && env.isDevelopment;

	// (3) Correct WebSocket scheme based on the dev-server protocol
	const wsProtocol = devServerInfo.isHttps ? "wss" : "ws";
	const devServerPort = devServerInfo.port || (devServerInfo.isHttps ? 443 : 80);

	return {
		devServer: {
			host: devServerInfo.host,
			port: devServerPort,
			hot: true,
			compress: true,
			allowedHosts: [devServerInfo.host, 'localhost'],
			watchFiles: {
				paths: [
					`${config.paths.root}/templates/**/*.php`,
					`${config.paths.root}/includes/**/*.php`,
					`${config.paths.root}/src/**/*.scss`,
					`${config.paths.root}/src/**/*.sass`,
					`${config.paths.root}/src/**/*.css`,
					`${config.paths.root}/templates/**/*.scss`,
					`${config.paths.root}/templates/**/*.sass`,
				],
				options: {
					usePolling: false,
				},
			},
			client: {
				logging: "info",
				overlay: {
					errors: true,
					warnings: false,
				},
				webSocketURL: {
					protocol: wsProtocol,
					hostname: devServerInfo.host,
					port: devServerPort,
					pathname: '/ws'
				},
			},
			static: {
				directory: config.paths.dist,
				publicPath: config.publicPath,
				serveIndex: false,
				watch: false,
			},
			proxy: wpTargetInfo
				? [
					{
						context: ["/"],
						target: process.env.WP_DEV_URL,
						changeOrigin: true,
						secure: !ignoreSSLErrors, // (7) inverse of ignoreSSLErrors
						headers: { "X-Webpack-Dev-Server": "true" },
						onProxyRes: (proxyRes, req, res) => {
							// Only process HTML responses
							const contentType = proxyRes.headers['content-type'];
							if (!contentType || !contentType.includes('text/html')) {
								return;
							}

							// Remove content-length header as we'll be modifying the body
							delete proxyRes.headers['content-length'];

							let body = Buffer.alloc(0);
							let hasError = false;

							// Collect response data
							proxyRes.on('data', (chunk) => {
								try {
									body = Buffer.concat([body, chunk]);
								} catch (error) {
									console.error('[webpack-dev-server] Error collecting response data:', error.message);
									hasError = true;
								}
							});

							proxyRes.on('end', () => {
								// If error occurred during data collection, send original response
								if (hasError) {
									try {
										res.writeHead(proxyRes.statusCode, proxyRes.headers);
										res.end(body);
									} catch (error) {
										console.error('[webpack-dev-server] Error sending error response:', error.message);
										res.end();
									}
									return;
								}

								try {
									let bodyString = body.toString('utf8');

									// Perform URL rewriting (excluding script tags)
									if (bodyString && process.env.WP_DEV_URL && rawDevServerUrl) {
										try {
											const wpUrl = process.env.WP_DEV_URL.replace(/\/$/, '');
											const proxyUrl = rawDevServerUrl.replace(/\/$/, '');

											// Escape special regex characters
											const escapedWpUrl = wpUrl.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

											// Split content to preserve script tags
											const scriptRegex = /(<script[^>]*>)([\s\S]*?)(<\/script>)/gi;
											const parts = [];
											let lastIndex = 0;
											let match;

											// Extract script tags and their content
											while ((match = scriptRegex.exec(bodyString)) !== null) {
												// Add content before script tag (with URL rewriting)
												const beforeScript = bodyString.slice(lastIndex, match.index);
												parts.push({ content: beforeScript, rewrite: true });

												// Add script tag content (without URL rewriting)
												parts.push({ content: match[0], rewrite: false });

												lastIndex = match.index + match[0].length;
											}

											// Add remaining content after last script tag
											const remaining = bodyString.slice(lastIndex);
											parts.push({ content: remaining, rewrite: true });

											// Process each part
											bodyString = parts.map(part => {
												if (!part.rewrite || !part.content) {
													return part.content;
												}

												try {
													return part.content
														// Replace only href attributes (but not in link tags) - force double quotes
														.replace(new RegExp(`(<(?!link)[^>]+href)=['"]${escapedWpUrl}([^'"]*['"])`, 'gi'), `$1="${proxyUrl}$2`);
												} catch (error) {
													console.error('[webpack-dev-server] Error in regex replacement:', error.message);
													return part.content;
												}
											}).join('');
										} catch (error) {
											console.error('[webpack-dev-server] Error in URL rewriting logic:', error.message);
											// Continue with original bodyString
										}
									}

									// Write the modified response
									res.writeHead(proxyRes.statusCode, proxyRes.headers);
									res.end(bodyString);
								} catch (error) {
									console.error('[webpack-dev-server] Critical error in proxy response handler:', error.message);
									try {
										res.writeHead(proxyRes.statusCode || 500, proxyRes.headers || {});
										res.end(body);
									} catch (finalError) {
										console.error('[webpack-dev-server] Failed to send error response:', finalError.message);
										res.end();
									}
								}
							});

							proxyRes.on('error', (error) => {
								console.error('[webpack-dev-server] Proxy response error:', error.message);
								try {
									if (!res.headersSent) {
										res.writeHead(500, { 'Content-Type': 'text/plain' });
									}
									res.end('Proxy error occurred');
								} catch (finalError) {
									console.error('[webpack-dev-server] Failed to handle proxy error:', finalError.message);
								}
							});

							// Prevent the default response handling
							proxyRes.pipe = () => {};
						},
					},
				]
				: undefined,
			devMiddleware: {
				publicPath: config.publicPath,
                writeToDisk: true,
				serverSideRender: false,
			},
			setupMiddlewares: (middlewares, devServer) => {
				// Add middleware to conditionally enable HMR based on request origin
				devServer.app.use((req, res, next) => {
					const requestHost = req.get('host');
					const proxyHost = `${devServerInfo.host}:${devServerPort}`;
					
					// Only enable HMR for requests coming to the proxy URL
					if (requestHost !== proxyHost) {
						// Disable HMR for direct WordPress URL access
						req.disableHMR = true;
					}
					
					next();
				});
				
				return middlewares;
			},
		},
	};
};
