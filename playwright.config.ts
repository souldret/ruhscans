import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    timeout: 60_000,
    expect: { timeout: 10_000 },
    use: {
        baseURL: process.env.APP_URL ?? 'http://127.0.0.1:8888',
        trace: 'retain-on-failure',
    },
    webServer: {
        command: '"C:\\Users\\tbagt\\AppData\\Local\\Microsoft\\WinGet\\Packages\\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\\php.exe" artisan serve --host=127.0.0.1 --port=8888',
        url: 'http://127.0.0.1:8888/up',
        reuseExistingServer: !process.env.CI,
        timeout: 120_000,
    },
    projects: [
        { name: 'mobile', use: { ...devices['Desktop Chrome'], viewport: { width: 390, height: 844 } } },
        { name: 'desktop', use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } } },
    ],
});
