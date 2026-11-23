const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests/e2e',
    timeout: 30_000,
    expect: { timeout: 5_000 },
    fullyParallel: false,
    retries: 0,
    reporter: 'list',
    use: {
        baseURL: process.env.E2E_BASE_URL || 'http://127.0.0.1:8123',
        browserName: 'chromium',
        viewport: { width: 390, height: 844 },
        colorScheme: 'dark',
        extraHTTPHeaders: {
            'X-Forwarded-For': '127.0.0.99',
            'CF-Connecting-IP': '127.0.0.99',
        },
        trace: 'retain-on-failure',
    },
});
