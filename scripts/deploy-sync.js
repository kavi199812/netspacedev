// NetSpace Dev - Deploy Sync Helper
// Copies compiled Astro static artifacts from dist/ to repository root
// This enables seamless one-click Git deployment directly into Hostinger public_html

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..');
const distDir = path.resolve(rootDir, 'dist');

if (!fs.existsSync(distDir)) {
    console.error('Error: dist/ directory not found. Please run astro build first.');
    process.exit(1);
}

// Folders and files to sync from dist/ to root
const itemsToSync = [
    '_astro',
    'about',
    'projects',
    'services',
    'blog',
    'contact',
    'images',
    'videos',
    'index.html',
    '.htaccess',
    'favicon.svg',
    'logo.svg',
    'logo.png',
    'logo-dark.png',
    'logo-white.png'
];

console.log('🔄 Syncing built files from dist/ to repository root for Hostinger Git Deployment...');

for (const item of itemsToSync) {
    const srcPath = path.join(distDir, item);
    const destPath = path.join(rootDir, item);

    if (fs.existsSync(srcPath)) {
        const stat = fs.statSync(srcPath);
        if (stat.isDirectory()) {
            fs.cpSync(srcPath, destPath, { recursive: true });
            console.log(`  ✓ Copied directory: ${item}/`);
        } else {
            fs.copyFileSync(srcPath, destPath);
            console.log(`  ✓ Copied file: ${item}`);
        }
    }
}

console.log('✅ Hostinger Git Deployment files ready in repository root!');
