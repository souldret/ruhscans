const { test, expect } = require('@playwright/test');

async function confirmAge(page) {
    await page.goto('/', { waitUntil: 'domcontentloaded' });
    const button = page.locator('form[action$="/18-onay"] button[type="submit"]');

    if (await button.count()) {
        await button.first().click();
        await page.waitForLoadState('domcontentloaded');
    }
}

test('yaş kapısı onaysız ziyaretçide tam ekran açılır ve onay sonrası ana sayfaya geçer', async ({ page }) => {
    await page.setViewportSize({ width: 360, height: 800 });
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('button', { name: '18 yaşından büyüğüm' })).toBeVisible();
    await page.getByRole('button', { name: '18 yaşından büyüğüm' }).click();
    await page.waitForLoadState('domcontentloaded');

    await expect(page.getByRole('heading', { name: /Bu gece rafta ne var/ })).toBeVisible();
});

test('ana sayfa mobilde taşmadan açılır, alt menü ve panik tuşu görünür', async ({ page }) => {
    await page.setViewportSize({ width: 360, height: 800 });
    await confirmAge(page);

    const nav = page.getByRole('navigation', { name: 'Mobil menü' });
    await expect(nav).toBeVisible();

    for (const label of ['Keşfet', 'Kütüphane', 'Ara', 'Profil']) {
        await expect(nav.getByRole('link', { name: label })).toBeVisible();
    }

    await expect(page.getByRole('button', { name: /Panik tuşu/ })).toBeVisible();

    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    expect(overflow).toBeLessThanOrEqual(1);
});

test('keşfet araması ve alttan açılan filtre sheet çalışır', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await confirmAge(page);
    await page.goto('/kesfet', { waitUntil: 'networkidle' });

    const input = page.locator('#kesfet-ara');
    await expect(input).toBeVisible();
    await input.fill('gece');
    await expect(input).toHaveValue('gece');

    await page.getByRole('button', { name: 'Filtreleri aç' }).click();
    await expect(page.getByRole('dialog', { name: 'Filtreler' })).toBeVisible();
    await page.getByRole('button', { name: 'Filtreleri kapat' }).click();
});

test('seri sayfasında sabit okuma butonu ve kilit rozetleri görünür', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await confirmAge(page);
    await page.goto('/seri/gece-yarisi-sozlesmesi', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('link', { name: /Okumaya başla|Kaldığın yerden devam et/ }).first()).toBeVisible();
    await expect(page.locator('.badge-lock').first()).toBeVisible();
});

test('okuyucu araç çubuğu ve ayarlar çekmecesi mobilde çalışır', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await confirmAge(page);
    await page.goto('/seri/gece-yarisi-sozlesmesi/bolum/1', { waitUntil: 'networkidle' });

    await expect(page.getByRole('button', { name: 'Odak' })).toBeVisible();
    await page.getByRole('button', { name: 'Okuma ayarları' }).click();
    await expect(page.getByRole('dialog', { name: 'Okuma ayarları' })).toBeVisible();
});

test('giriş ve kayıt sayfaları büyük alanlarla (44px+ ve 16px+) açılır', async ({ page }) => {
    await page.setViewportSize({ width: 360, height: 800 });
    await page.goto('/login', { waitUntil: 'domcontentloaded' });
    await expect(page.getByRole('heading', { name: 'Tekrar hoş geldin' })).toBeVisible();

    const email = page.locator('#email');
    const box = await email.boundingBox();
    expect(box.height).toBeGreaterThanOrEqual(44);

    const fontSize = await email.evaluate((el) => parseFloat(getComputedStyle(el).fontSize));
    expect(fontSize).toBeGreaterThanOrEqual(16);

    await page.goto('/register', { waitUntil: 'domcontentloaded' });
    await expect(page.getByRole('heading', { name: 'Ruhunu kaydet' })).toBeVisible();
});

test('PWA kabuğu manifest, service worker ve nötr çevrimdışı sayfa sunar', async ({ request }) => {
    const manifestResponse = await request.get('/manifest.json');
    expect(manifestResponse.ok()).toBeTruthy();

    const manifest = await manifestResponse.json();
    expect(manifest.short_name).toBe('Ruhscans');
    expect(manifest.icons.length).toBeGreaterThanOrEqual(3);

    const swResponse = await request.get('/sw.js');
    expect(swResponse.ok()).toBeTruthy();
    const swText = await swResponse.text();
    expect(swText).toContain('ruhscans-shell-v1');
    expect(swText).toContain('/offline.html');

    const offlineResponse = await request.get('/offline.html');
    expect(offlineResponse.ok()).toBeTruthy();
    expect(await offlineResponse.text()).toContain('Bağlantı yok');
});
