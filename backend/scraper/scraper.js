import puppeteer from 'puppeteer';
import crypto from 'crypto';

function generateStableId(author, text, date) {
    const data = `${author}_${text}_${date}`;
    return crypto.createHash('md5').update(data).digest('hex');
}

async function scrapeYandexReviews(url, maxReviews = 600) {
    const browser = await puppeteer.launch({
        headless: 'shell',
        executablePath: process.env.PUPPETEER_EXECUTABLE_PATH || undefined,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--single-process',
            '--disable-gpu',
        ]
    });

    try {
        const page = await browser.newPage();
        
        await page.setViewport({ width: 1280, height: 800 });
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        await page.setExtraHTTPHeaders({
            'Accept-Language': 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
        });

        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
        await new Promise(resolve => setTimeout(resolve, 3000));

        const orgInfo = await page.evaluate(() => {
            let name = null;
            const nameEl = document.querySelector('.card-title-view__title, h1, .orgpage-header-view__header');
            if (nameEl) {
                name = nameEl.textContent.trim();
            }

            let rating = null;
            const ratingTextEl = document.querySelector('.business-summary-rating-badge-view__rating-text, .business-rating-badge-view__rating');
            if (ratingTextEl) {
                const cleaned = ratingTextEl.textContent.replace(',', '.').trim();
                rating = parseFloat(cleaned) || null;
            }

            let reviewCount = 0;
            let ratingCount = 0;

            const ratingAmountEl = document.querySelector('.business-rating-amount-view, .orgpage-header-view__rating-count');
            if (ratingAmountEl) {
                const text = ratingAmountEl.textContent.trim();
                const match = text.match(/(\d[\d\s]*)/);
                if (match) {
                    reviewCount = parseInt(match[1].replace(/\s/g, ''), 10) || 0;
                    ratingCount = reviewCount;
                }
            }

            const reviewsCountEl = document.querySelector('div[data-tab-key="reviews"] .tabs-menu-tab-view__count');
            if (reviewsCountEl) {
                reviewCount = parseInt(reviewsCountEl.textContent.trim().replace(/\s/g, ''), 10) || reviewCount;
            }

            return { name, rating, reviewCount, ratingCount };
        });

        await page.evaluate(async (targetLimit) => {
            const findScrollable = () => {
                let el = document.querySelector('.scroll__container');
                if (el) return el;
                el = document.querySelector('div[class*="scroll__container"]');
                if (el) return el;
                el = document.querySelector('div[class*="scrollable"]');
                if (el) return el;
                el = document.querySelector('.reviews-view__content');
                if (el) return el;
                const review = document.querySelector('.business-review-view');
                if (review) return review.parentElement;
                return document.body;
            };

            const container = findScrollable();
            if (!container) return;

            let reviewsCount = document.querySelectorAll('.business-review-view').length;
            let noChangeCount = 0;

            while (reviewsCount < targetLimit && noChangeCount < 15) {
                if (container === document.body) {
                    window.scrollTo(0, document.body.scrollHeight);
                } else {
                    container.scrollTop = container.scrollHeight;
                }

                await new Promise(resolve => setTimeout(resolve, 1000));

                const newReviewsCount = document.querySelectorAll('.business-review-view').length;
                if (newReviewsCount === reviewsCount) {
                    noChangeCount++;
                } else {
                    noChangeCount = 0;
                    reviewsCount = newReviewsCount;
                }
            }
        }, maxReviews);

        const reviews = await page.evaluate(() => {
            const items = [];
            const nodes = document.querySelectorAll('.business-review-view');

            nodes.forEach(node => {
                let author = 'Аноним';
                const authorEl = node.querySelector('span[itemprop="name"], .business-review-view__author-name, .business-review-view__author span');
                if (authorEl) {
                    author = authorEl.textContent.trim();
                }

                let rating = 5;
                const ratingMeta = node.querySelector('meta[itemprop="ratingValue"]');
                if (ratingMeta) {
                    rating = parseInt(ratingMeta.getAttribute('content'), 10) || 5;
                } else {
                    const fullStars = node.querySelectorAll('.business-rating-badge-view__star._full, .business-review-view__star._full').length;
                    if (fullStars > 0) rating = fullStars;
                }

                let datePublished = null;
                const dateMeta = node.querySelector('meta[itemprop="datePublished"]');
                if (dateMeta) {
                    datePublished = dateMeta.getAttribute('content');
                } else {
                    const dateEl = node.querySelector('.business-review-view__date');
                    if (dateEl) datePublished = dateEl.textContent.trim();
                }

                let text = '';
                const reviewBody = node.querySelector('[itemprop="reviewBody"], .business-review-view__body-text');
                if (reviewBody) {
                    const spoilerText = reviewBody.querySelector('.spoiler-view__text-container, .business-review-view__body-text');
                    text = spoilerText ? spoilerText.textContent.trim() : reviewBody.textContent.trim();
                    text = text.replace(/\s+/g, ' ').trim();
                }

                let yandexId = null;
                const linkEl = node.querySelector('a[href*="reviews"]');
                if (linkEl) {
                    const href = linkEl.getAttribute('href');
                    const match = href.match(/reviewId=([^&]+)/) || href.match(/\/review\/([^/?]+)/);
                    if (match) yandexId = match[1];
                }

                if (text) {
                    items.push({
                        yandexId: yandexId,
                        author,
                        rating,
                        text,
                        datePublished
                    });
                }
            });

            return items;
        });

        const processedReviews = reviews.map(item => {
            if (!item.yandexId) {
                item.yandexId = generateStableId(item.author, item.text, item.datePublished);
            }
            return item;
        });

        console.log(JSON.stringify({
            success: true,
            orgInfo,
            reviews: processedReviews
        }));

    } catch (error) {
        console.log(JSON.stringify({
            success: false,
            error: error.message
        }));
    } finally {
        await browser.close();
    }
}

const url = process.argv[2];
const limit = process.argv[3] ? parseInt(process.argv[3], 10) : 600;

if (url) {
    scrapeYandexReviews(url, limit);
} else {
    process.exit(1);
}
