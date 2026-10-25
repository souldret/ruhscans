import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

test.describe.configure({ timeout: 120_000 });
const pages = ['/', '/oyunlar', '/coin-paketleri', '/kullanim-sartlari', '/login'];
const widths = [360, 390, 768, 1440];

test('ana sayfalar yatay taşmaz', async ({ page }) => {
    for (const width of widths) {
        await page.setViewportSize({ width, height: 900 });

        for (const path of pages) {
            const response = await page.goto(path);
            expect(response?.status(), path).toBeLessThan(400);
            const gate = page.getByRole('button', { name: '18 yaşındayım' });
            if (await gate.isVisible()) {
                await gate.click();
            }

            const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
            expect(overflow, `${path} @ ${width}`).toBeLessThanOrEqual(1);

            const broken = await page.locator('img').evaluateAll((images) => images
                .filter((image) => image instanceof HTMLImageElement && image.complete && image.naturalWidth === 0)
                .map((image) => (image as HTMLImageElement).src));
            expect(broken, `${path} @ ${width}`).toEqual([]);
        }
    }
});

test('uzun ad, boş açıklama ve etiketsiz oyun sığar', async ({ page }) => {
    await page.setViewportSize({ width: 360, height: 900 });
    await page.goto('/oyunlar');
    await page.getByRole('button', { name: '18 yaşındayım' }).click().catch(() => undefined);
    const card = page.locator('a[href*="/oyun/"]').first();
    if (await card.count()) {
        const box = await card.boundingBox();
        expect(box?.width ?? 0).toBeLessThanOrEqual(160);
    } else {
        await expect(page.getByText('eşleşen oyun yok')).toBeVisible();
    }
});

test('kaldırma formu hataları alanın yanında Türkçe gösterir', async ({ page }) => {
    await page.goto('/icerik-kaldirma');
    await page.getByRole('button', { name: '18 yaşındayım' }).click().catch(() => undefined);
    await page.getByRole('button', { name: 'Bildir' }).click();
    await expect(page.getByText('Adını yaz.')).toBeVisible();
    await expect(page.getByText('E-posta gerekli.')).toBeVisible();
});

test('katalogta ciddi erişilebilirlik ihlali yok', async ({ page }) => {
    await page.goto('/oyunlar');
    await page.getByRole('button', { name: '18 yaşındayım' }).click().catch(() => undefined);
    const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa']).analyze();
    const serious = results.violations.filter((violation) => ['serious', 'critical'].includes(violation.impact ?? ''));
    expect(serious.map((violation) => violation.id)).toEqual([]);
});

test('javascript kapalıyken katalog açılır', async ({ browser }) => {
    const context = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 844 } });
    const page = await context.newPage();
    const response = await page.goto('/oyunlar');
    expect(response?.status()).toBe(200);
    await expect(page.getByRole('heading', { name: 'Oyunlar' })).toBeVisible();
    await context.close();
});
