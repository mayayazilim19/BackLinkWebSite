HOSTINGER DEPLOYMENT

1. Open hPanel > Websites > File Manager.
2. Go to public_html.
3. Upload all files/folders from this package into public_html.
4. Make sure PHP is enabled. PHP 8.x is recommended.
5. Ensure the cache folder is writable. If needed, set permissions to 755 or 775.
6. Visit https://yourdomain.com/index.php.

CUSTOMIZATION

- Edit data/sources.json to add/remove RSS feeds.
- Edit data/sponsors.json to add portfolio companies.
- Edit header.php to change site name, title metadata, and canonical URL.
- Edit assets/css/style.css for colors/layout.

SEO / LEGAL NOTES

- Show only headlines, short snippets, source name, date, and outbound link.
- Do not copy full articles without permission.
- Paid/sponsor portfolio links are marked rel="sponsored".
- News outbound links are marked rel="nofollow" by default.
- Add original descriptions for portfolio companies; avoid keyword stuffing.
