# Kupiana SEO Readiness Checklist

Kupiana now ships with the technical SEO layer needed for launch. Before putting a
real domain behind the site, complete the content/provider items below.

## Technical SEO included

- Public storefront pages render title, description, canonical, robots, Open Graph,
  Twitter card, hreflang and JSON-LD metadata.
- Organization and WebSite schema are emitted site-wide.
- Product detail pages emit Product, Offer, AggregateRating when available and
  BreadcrumbList schema.
- Shop/category/brand/deals listing pages emit ItemList and breadcrumb schema.
- CMS pages, contact page and blog posts emit page/article schema.
- Admin, account, cart, checkout, payment, webhook, API and search/filter surfaces
  are protected with noindex/robots rules.
- Sitemap includes homepage, static discovery pages, categories, brands, products,
  product images, CMS pages and blog posts.
- `robots.txt` points crawlers to the sitemap and keeps private/transactional routes
  out of crawl focus.
- Admin `seo_meta` rows can override entity meta and optional custom JSON-LD without
  code changes.

## Launch content checklist

- Set `APP_BASE_URL` to the final HTTPS domain in `.env`.
- Replace placeholder logo/favicon/social image assets with production brand assets.
- Fill production `site_name`, support email and support phone.
- Write unique `meta_title` and `meta_description` values for top categories, brands,
  CMS pages and best-selling products.
- Add real product images with descriptive alt text.
- Add real blog posts or disable blog navigation until content exists.
- Configure analytics and search tooling outside the codebase:
  - Google Search Console / Bing Webmaster Tools
  - Sitemap submission: `/sitemap.xml`
  - GA4 or another analytics tool if required
- After launch, inspect a sample of product/category/blog URLs in rich-result testing
  tools and monitor crawl/index coverage.
