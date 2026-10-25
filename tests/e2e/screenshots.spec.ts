import { expect, test } from '@playwright/test';

const pages = ['/', '/oyunlar', '/coin-paketleri'];

for (const [name, width] of [['mobil', 390], ['masaustu', 1440]] as const) {
  test(`${name} ekranında sayfalar taşmaz`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });

    for (const path of pages) {
      await page.goto(path);
      await page.getByRole('button', { name: '18 yaşındayım' }).click().catch(() => undefined);

      const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
      expect(overflow, `${path} @ ${width}`).toBeLessThanOrEqual(1);

      await page.screenshot({
        path: `tests/e2e/screenshots/${name}-${path.replace(/\//g, '') || 'anasayfa'}.png`,
        fullPage: true,
      });
    }
  });
}
