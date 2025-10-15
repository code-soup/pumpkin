/**
 * Conditional HMR Client
 * Only enables hot module replacement when accessing through the webpack dev server port
 */

// Check if we're on the development port
function isOnDevPort() 
{
    const currentPort = window.location.port;
    const devPort = process.env.DEV_PROXY_PORT || '8080';
    
    return currentPort === devPort;
}

// Check for server-side HMR disable flag
function isHMRDisabled() 
{
    const metaTag = document.querySelector('meta[name="x-disable-hmr"]');
    return metaTag && metaTag.getAttribute('content') === 'true';
}

// Only enable HMR if we're on the dev port and it's not disabled server-side
if (isOnDevPort() && !isHMRDisabled()) {
    
    if (module.hot) {
        // Accept hot updates for this module
        module.hot.accept();
        
        // Optional: Add custom HMR logic
        module.hot.addStatusHandler((status) => {
            if (status === 'prepare') {
                console.log('[HMR] Preparing update...');
            }
            if (status === 'ready') {
                console.log('[HMR] Update ready, applying...');
            }
        });
        
        // Handle CSS updates
        if (module.hot) {
            const link = document.querySelector('link[data-webpack]');
            if (link) {
                module.hot.accept(['**/*.scss', '**/*.css'], () => {
                    // Force reload stylesheets
                    const links = document.querySelectorAll('link[rel="stylesheet"]');
                    links.forEach(linkEl => {
                        const href = linkEl.href;
                        linkEl.href = href + (href.includes('?') ? '&' : '?') + 'v=' + Date.now();
                    });
                });
            }
        }
    }
    
    console.log('[DEV] Hot module replacement enabled on port', window.location.port);
    
} else {
    
    console.log('[DEV] Hot module replacement disabled - not on development port');
    
}

export default isOnDevPort(); 