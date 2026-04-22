import { expect, test } from '@playwright/test';
import path from 'node:path';

const corsHeaders = {
  'access-control-allow-origin': '*',
  'access-control-allow-methods': 'GET, POST, OPTIONS',
  'access-control-allow-headers': 'content-type',
};

test('uploads an xlsx file and shows import statistics', async ({ page }) => {
  await page.route('**/api/import/products', async (route) => {
    if (route.request().method() === 'OPTIONS') {
      await route.fulfill({ status: 204, headers: corsHeaders });
      return;
    }

    await route.fulfill({
      contentType: 'application/json',
      headers: corsHeaders,
      body: JSON.stringify({
        created: 40,
        updated: 0,
        skipped: 0,
        errorCount: 0,
        errors: [],
      }),
    });
  });

  await page.goto('/import');
  await page
    .getByLabel('Excel-файл')
    .setInputFiles(path.resolve(__dirname, '../../docs/import example (2).xlsx'));
  await page.getByRole('button', { name: 'Импортировать товары' }).click();

  await expect(page.getByText('40')).toBeVisible();
  await expect(page.getByText('Создано')).toBeVisible();
  await expect(page.getByText('Ошибки', { exact: true })).toBeVisible();
});

test('opens product list and product details', async ({ page }) => {
  await page.route('**/api/products', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      headers: corsHeaders,
      body: JSON.stringify({
        items: [
          {
            id: 1,
            externalCode: 'external-1',
            name: 'Imported product',
            price: '1200.00',
            purchasePrice: '800.00',
            discountPercent: '50.00',
            imageCount: 1,
            firstImage: '/uploads/products/external-1/image.jpg',
          },
        ],
      }),
    });
  });

  await page.route('**/api/products/1', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      headers: corsHeaders,
      body: JSON.stringify({
        id: 1,
        externalCode: 'external-1',
        name: 'Imported product',
        description: 'Imported product description.',
        price: '1200.00',
        purchasePrice: '800.00',
        discountPercent: '50.00',
        createdAt: '2026-04-21T00:00:00+00:00',
        updatedAt: '2026-04-21T00:00:00+00:00',
        attributes: [{ key: 'Бренд', value: 'MINIMI' }],
        images: [
          {
            id: 1,
            sourceUrl: 'http://example.test/image.jpg',
            localPath: '/uploads/products/external-1/image.jpg',
          },
        ],
      }),
    });
  });

  await page.goto('/products');
  await expect(page.getByRole('heading', { name: 'Импортированные товары' })).toBeVisible();
  await page.getByRole('link', { name: /Imported product/ }).click();

  await expect(page.getByRole('heading', { name: 'Imported product' })).toBeVisible();
  await expect(page.getByText('MINIMI')).toBeVisible();
});
