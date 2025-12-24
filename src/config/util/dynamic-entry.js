import fs from 'fs';
import path from 'path';

/**
 * Recursively find entry point files (index.js/scss/sass and index-async.js/scss/sass)
 *
 * @param {string} folderPath - The directory path to search
 * @param {string} basePath - The base path to calculate relative paths from
 * @param {Object} indexFiles - Accumulated entry points object
 * @param {string} prefix - Optional prefix to prepend to entry keys
 * @returns {Object} Object with entry names as keys and file paths as values
 */
function findEntryPoints(folderPath, basePath = '', indexFiles = {}, prefix = '') {
    // Set basePath to folderPath on first call
    if (!basePath) {
        basePath = folderPath;
    }

    // Validate input
    if (!folderPath || typeof folderPath !== 'string') {
        console.warn('[dynamic-entry] Invalid folder path provided:', folderPath);
        return indexFiles;
    }

    // Check if directory exists
    if (!fs.existsSync(folderPath)) {
        console.warn('[dynamic-entry] Directory does not exist:', folderPath);
        return indexFiles;
    }

    // Check if path is actually a directory
    let stats;
    try {
        stats = fs.statSync(folderPath);
        if (!stats.isDirectory()) {
            console.warn('[dynamic-entry] Path is not a directory:', folderPath);
            return indexFiles;
        }
    } catch (error) {
        console.error('[dynamic-entry] Error accessing path:', folderPath, error.message);
        return indexFiles;
    }

    // Read directory contents
    let files;
    try {
        files = fs.readdirSync(folderPath);
    } catch (error) {
        console.error('[dynamic-entry] Error reading directory:', folderPath, error.message);
        return indexFiles;
    }

    // Process each file/directory
    files.forEach((file) => {
        try {
            const filePath = path.join(folderPath, file);
            const fileStats = fs.statSync(filePath);

            if (fileStats.isDirectory()) {
                // Recurse into subdirectory
                findEntryPoints(filePath, basePath, indexFiles, prefix);
            } else {
                // Check if file is an index file
                const ext = path.extname(file);
                if (ext === '.js' || ext === '.scss' || ext === '.sass') {
                    const fileName = path.basename(file, ext);

                    // Sync loading scripts
                    if (fileName === 'index') {
                        // Calculate relative path from base and extract folder structure
                        const relativePath = path.relative(basePath, folderPath);
                        const pathParts = relativePath.split(path.sep).filter(Boolean);

                        // Remove asset type folders (js, scss, sass, styles, scripts)
                        const assetFolders = ['js', 'scss', 'sass', 'styles', 'scripts'];
                        const keyParts = pathParts.filter(part => !assetFolders.includes(part));

                        const key = prefix + keyParts.join('-');

                        if (!indexFiles[key]) {
                            indexFiles[key] = [];
                        }
                        indexFiles[key].push(filePath);
                    }
                    // Async loading scripts
                    else if (fileName === 'index-async') {
                        // Calculate relative path from base and extract folder structure
                        const relativePath = path.relative(basePath, folderPath);
                        const pathParts = relativePath.split(path.sep).filter(Boolean);

                        // Remove asset type folders (js, scss, sass, styles, scripts)
                        const assetFolders = ['js', 'scss', 'sass', 'styles', 'scripts'];
                        const keyParts = pathParts.filter(part => !assetFolders.includes(part));

                        const key = prefix + keyParts.join('-') + '-async';

                        if (!indexFiles[key]) {
                            indexFiles[key] = [];
                        }
                        indexFiles[key].push(filePath);
                    }
                }
            }
        } catch (error) {
            console.error('[dynamic-entry] Error processing file:', file, error.message);
            // Continue processing other files
        }
    });

    return indexFiles;
}

export default findEntryPoints;