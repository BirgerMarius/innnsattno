const { chromium } = require('playwright');

const baseUrl = process.env.THEME_PREVIEW_BASE_URL ?? 'http://innsatt-local:8080';
const themes = [
    'sensommer', 'tidlig-host', 'host', 'senhost', 'forjul', 'advent', 'jul', 'nyttar',
    'mork-vinter', 'vinter', 'tidlig-var', 'var', 'paske', '17-mai', 'forsommer', 'sommer', 'midtsommer',
];

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage({ viewport: { width: 1200, height: 750 }, deviceScaleFactor: 1 });

    for (const theme of themes) {
        await page.goto(`${baseUrl}/design-test/${theme}`, { waitUntil: 'networkidle' });
        await page.addStyleTag({ content: '#phpdebugbar, .phpdebugbar { display: none !important; }' });
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.screenshot({
            path: `public/img/theme-previews/${theme}.png`,
            clip: { x: 0, y: 0, width: 1200, height: 750 },
        });
    }

    await browser.close();
})().catch((error) => {
    console.error(error);
    process.exit(1);
});
