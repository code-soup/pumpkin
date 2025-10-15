import fs from 'fs';
import path from 'path';

/**
 * Recursively find entry point files (index.js/scss/sass and index-async.js/scss/sass)
 *
 * @param {string} folderPath - The directory path to search
 * @param {string} parent1 - The grandparent folder name (used as entry key)
 * @param {string} parent2 - The parent folder name
 * @param {Object} indexFiles - Accumulated entry points object
 * @returns {Object} Object with entry names as keys and file paths as values
 */
function findEntryPoints(folderPath, parent1 = '', parent2 = '', indexFiles = {}) {
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
                findEntryPoints(filePath, parent2, file, indexFiles);
            } else {
                // Check if file is an index file
                const ext = path.extname(file);
                if (ext === '.js' || ext === '.scss' || ext === '.sass') {
                    const fileName = path.basename(file, ext);

                    // Sync loading scripts
                    if (fileName === 'index') {
                        // Store file in object with keys based on parent folders
                        const key = parent1;

                        if (!indexFiles[key]) {
                            indexFiles[key] = [];
                        }
                        indexFiles[key].push(filePath);
                    }
                    // Async loading scripts
                    else if (fileName === 'index-async') {
                        // Store file in object with keys based on parent folders
                        const key = parent1 + '-async';

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