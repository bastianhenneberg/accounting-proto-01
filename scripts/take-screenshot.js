const puppeteer = require('puppeteer');

async function takeScreenshot(url, outputPath) {
    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    try {
        const page = await browser.newPage();

        // Set viewport and user agent
        await page.setViewport({ width: 1200, height: 800 });
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

        // Navigate and wait for content
        await page.goto(url, { waitUntil: 'networkidle2', timeout: 30000 });

        // Wait a bit more for JavaScript to load prices
        await page.waitForTimeout(3000);

        // Take screenshot
        await page.screenshot({
            path: outputPath,
            fullPage: false,
            type: 'png',
            quality: 90
        });

        console.log('Screenshot saved:', outputPath);

    } catch (error) {
        console.error('Puppeteer error:', error);
        process.exit(1);
    } finally {
        await browser.close();
    }
}

// Get arguments
const url = process.argv[2];
const outputPath = process.argv[3];

if (!url || !outputPath) {
    console.error('Usage: node take-screenshot.js <url> <output-path>');
    process.exit(1);
}

takeScreenshot(url, outputPath);