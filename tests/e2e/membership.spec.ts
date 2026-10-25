import { expect, test } from '@playwright/test';

const password = 'Gizli-Parola-12';

async function adult(page) {
    const gate = page.getByRole('button', { name: '18 yaşındayım' });
    if (await gate.isVisible().catch(() => false)) {
        await gate.click();
    }
}

test('üyelik, indirme, coin kodu ve yaş onayı', async ({ page }, testInfo) => {
    const stamp = `${testInfo.project.name}-${Date.now()}`;
    const email = `${stamp}@uye.test`;
    const code = testInfo.project.name === 'mobile' ? 'MOBL-E2E2-CODE-0003' : 'DESK-E2E2-CODE-0003';

    await page.goto('/');
    await expect(page.getByRole('dialog')).toBeVisible();
    await page.getByRole('button', { name: '18 yaşındayım' }).click();
    await expect(page.getByRole('dialog')).toBeHidden();

    await page.goto('/register');
    await page.locator('#name').fill('Uçtan Uca');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.locator('#password_confirmation').fill(password);
    await page.getByRole('button', { name: 'Kayıt ol' }).click();
    await expect(page).toHaveURL(/\/hesap$/);

    await page.goto('/logout', { waitUntil: 'commit' }).catch(() => undefined);
    await page.context().clearCookies();
    await page.goto('/login');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL(/\/hesap$/);

    await page.goto('/oyunlar?erisim=ucretsiz');
    await adult(page);
    await page.locator('a[href*="/oyun/"]').first().click();
    const freeDownload = page.getByRole('link', { name: 'İndir' }).first();
    await expect(freeDownload).toBeVisible();
    await freeDownload.click();
    await expect(page).not.toHaveURL(/\/login$/);

    await page.goto('/oyunlar?erisim=ucretli');
    await adult(page);
    await page.locator('a[href*="/oyun/"]').first().click();
  await expect(page.getByRole('button', { name: /coin ile aç/ })).toBeVisible();

    await page.goto('/hesap');
    await page.locator('#code').fill(code);
    await page.getByRole('button', { name: 'Kodu tanımla' }).click();
    await expect(page.getByText('Bakiyen 500 coin oldu.')).toBeVisible();

    await page.goto('/oyun/gece-vapuru');
    await adult(page);
    await page.getByRole('button', { name: '100 coin ile aç' }).click();
    await page.getByRole('button', { name: 'Onayla' }).click();
    await expect(page.getByRole('link', { name: 'İndir' }).first()).toBeVisible();
    await page.getByRole('link', { name: 'İndir' }).first().click();
    await expect(page).not.toHaveURL(/\/login$/);
});

