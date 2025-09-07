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

							// Collect response data
							proxyRes.on('data', (chunk) => {
								body = Buffer.concat([body, chunk]);
							});

							proxyRes.on('end', () => {
								try {
									let bodyString = body.toString('utf8');

									// Perform URL rewriting (excluding script tags)
									if (bodyString && process.env.WP_DEV_URL && rawDevServerUrl) {
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

											return part.content
												// Replace only href attributes (but not in link tags) - force double quotes
												.replace(new RegExp(`(<(?!link)[^>]+href)=['"]${escapedWpUrl}([^'"]*['"])`, 'gi'), `$1="${proxyUrl}$2`);
										}).join('');
									}

									// Write the modified response
									res.writeHead(proxyRes.statusCode, proxyRes.headers);
									res.end(bodyString);
								} catch (error) {
									console.error('Error in URL rewriting:', error);
									res.writeHead(proxyRes.statusCode, proxyRes.headers);
									res.end(body);
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
